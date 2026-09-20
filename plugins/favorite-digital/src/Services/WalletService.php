<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Services;

use FavoriteCMS\Core\Currency;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Digital\Exceptions\WalletException;
use FavoriteCMS\Digital\Repositories\WalletRepository;
use FavoriteCMS\Pay\Contracts\WalletServiceInterface as FavoritePayWalletServiceInterface;
use FavoriteCMS\Pay\Domain\Money as FavoritePayMoney;
use Throwable;

class WalletService
{
    protected WalletRepository $walletRepo;
    protected ?Database $db;
    protected ?FavoritePayWalletServiceInterface $favPayWalletService;

    public function __construct(
        WalletRepository $walletRepo,
        ?Database $db = null,
        ?FavoritePayWalletServiceInterface $favPayWalletService = null
    ) {
        $this->walletRepo = $walletRepo;
        $this->db = $db ?? $walletRepo->getDatabase();
        $this->favPayWalletService = $favPayWalletService;
    }

    public function getWalletRepository(): WalletRepository
    {
        return $this->walletRepo;
    }

    public function getFavoritePayWalletService(): ?FavoritePayWalletServiceInterface
    {
        if ($this->favPayWalletService === null) {
            if (function_exists('app')) {
                try {
                    $app = app();
                    if ($app !== null && method_exists($app, 'has') && $app->has(FavoritePayWalletServiceInterface::class)) {
                        $this->favPayWalletService = $app->make(FavoritePayWalletServiceInterface::class);
                    }
                } catch (Throwable) {
                }
            }
            if ($this->favPayWalletService === null && class_exists(\FavoriteCMS\Core\Container::class)) {
                try {
                    $c = \FavoriteCMS\Core\Container::getInstance();
                    if ($c !== null && method_exists($c, 'has') && $c->has(FavoritePayWalletServiceInterface::class)) {
                        $this->favPayWalletService = $c->make(FavoritePayWalletServiceInterface::class);
                    }
                } catch (Throwable) {
                }
            }
        }
        return $this->favPayWalletService;
    }

    public function setFavoritePayWalletService(?FavoritePayWalletServiceInterface $service): void
    {
        $this->favPayWalletService = $service;
    }

    public function getBalance(int $userId): string
    {
        if ($userId <= 0) {
            return '0.00';
        }

        $favPayWallet = $this->getFavoritePayWalletService();
        if ($favPayWallet !== null) {
            try {
                $favPayBal = $favPayWallet->getAvailableBalance($userId);
                return number_format($favPayBal->getAmount() / 100, 2, '.', '');
            } catch (Throwable) {
            }
        }

        if ($this->db !== null && $this->db->tableExists('favorite_pay_wallets')) {
            try {
                $row = $this->db->selectOne(
                    "SELECT balance FROM favorite_pay_wallets WHERE user_id = ?",
                    [$userId]
                );
                if ($row !== null && isset($row->balance)) {
                    return number_format((int)$row->balance / 100, 2, '.', '');
                }
            } catch (Throwable) {
            }
        }

        $wallet = $this->walletRepo->getOrCreateWallet($userId);
        return number_format((float)$wallet->balance_amount, 2, '.', '');
    }

    public function credit(
        int $userId,
        string $amount,
        string $referenceId,
        string $description = '',
        ?int $orderId = null,
        string $type = 'credit'
    ): object {
        if ($userId <= 0) {
            throw WalletException::walletNotFound($userId);
        }

        $amountMinor = $this->parseAmountMinor($amount);
        if ($amountMinor <= 0) {
            throw WalletException::invalidAmount($amount);
        }

        // Idempotency: return existing transaction if reference was already logged
        $existing = $this->walletRepo->getTransactionByReference($referenceId);
        if ($existing !== null) {
            return $existing;
        }

        // Check if Favorite Pay ledger entry already exists for this reference
        $fpAlreadyCredited = false;
        if ($this->db !== null && $this->db->tableExists('favorite_pay_wallet_entries')) {
            try {
                $fpRow = $this->db->selectOne(
                    "SELECT id FROM favorite_pay_wallet_entries WHERE reference_id = ? LIMIT 1",
                    [$referenceId]
                );
                if ($fpRow !== null) {
                    $fpAlreadyCredited = true;
                }
            } catch (Throwable) {
            }
        }

        $favPayWallet = $this->getFavoritePayWalletService();
        $curr = class_exists(Currency::class) ? Currency::getPrimaryCurrency() : 'BDT';
        $favPayEntryId = null;

        // Synchronize deposit with Favorite Pay wallet if available (except recharges which are settled directly by Favorite Pay)
        if ($favPayWallet !== null && $type !== 'recharge' && !$fpAlreadyCredited) {
            $inTransaction = false;
            if ($this->db !== null) {
                try {
                    $pdo = $this->db->getConnection();
                    $inTransaction = $pdo !== null && $pdo->inTransaction();
                } catch (Throwable) {
                }
            }

            if ($inTransaction) {
                // If caller is already inside a PDO transaction, calling deposit() on Favorite Pay would fail with
                // "There is already an active transaction" because Favorite Pay calls $this->db->transaction().
                // We execute direct SQL synchronization within this active transaction.
                $this->directSyncFavoritePayDeposit($userId, $amountMinor, $curr, $referenceId, $description);
            } else {
                // PRIMARY PATH: Official Favorite Pay WalletService deposit API
                try {
                    $favEntry = $favPayWallet->deposit(
                        $userId,
                        new FavoritePayMoney($amountMinor, $curr),
                        $referenceId,
                        $description
                    );
                    if ($favEntry !== null && method_exists($favEntry, 'getId')) {
                        $favPayEntryId = $favEntry->getId();
                    }
                } catch (Throwable $e) {
                    // NEVER SILENTLY SWALLOW!
                    throw WalletException::depositFailed($e->getMessage());
                }
            }
        } elseif ($favPayWallet === null && $type !== 'recharge' && !$fpAlreadyCredited &&
                  $this->db !== null && $this->db->tableExists('favorite_pay_wallets') && $this->db->tableExists('favorite_pay_wallet_entries')) {
            // Fallback: when Favorite Pay service is genuinely unavailable in container but tables exist in DB
            $this->directSyncFavoritePayDeposit($userId, $amountMinor, $curr, $referenceId, $description);
        }

        return $this->executeInTransaction(function () use ($userId, $amountMinor, $referenceId, $description, $orderId, $type, $favPayEntryId) {
            $wallet = $this->walletRepo->lockWalletForUpdate($userId);
            if (!$wallet) {
                $wallet = $this->walletRepo->getOrCreateWallet($userId);
                $wallet = $this->walletRepo->lockWalletForUpdate($userId);
            }

            if ($wallet->status !== 'active') {
                throw WalletException::walletInactive($userId, $wallet->status);
            }

            $currMinor = $this->parseAmountMinor($wallet->balance_amount);
            $afterMinor = $currMinor + $amountMinor;
            $newBalance = $this->minorToDecimal($afterMinor);
            $amountDec  = $this->minorToDecimal($amountMinor);

            $this->walletRepo->updateBalance((int)$wallet->id, $newBalance);

            $txId = $this->walletRepo->createTransaction([
                'wallet_id'     => (int)$wallet->id,
                'type'          => $type,
                'amount'        => $amountDec,
                'balance_after' => $newBalance,
                'order_id'      => $orderId,
                'reference_id'  => $referenceId,
                'description'   => $description,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            return (object)[
                'id'                 => $txId,
                'wallet_id'          => (int)$wallet->id,
                'type'               => $type,
                'amount'             => $amountDec,
                'balance_after'      => $newBalance,
                'order_id'           => $orderId,
                'reference_id'       => $referenceId,
                'fav_pay_entry_id'   => $favPayEntryId,
                'description'        => $description,
            ];
        });
    }

    public function debit(
        int $userId,
        string $amount,
        string $referenceId,
        string $description = '',
        ?int $orderId = null
    ): object {
        $amountMinor = $this->parseAmountMinor($amount);
        if ($amountMinor <= 0) {
            throw WalletException::invalidAmount($amount);
        }

        // Idempotency check: duplicate call returns previous debit cleanly
        $existing = $this->walletRepo->getTransactionByReference($referenceId);
        if ($existing !== null) {
            return $existing;
        }

        // Synchronize debit with Favorite Pay wallet if available
        $favPayWallet = $this->getFavoritePayWalletService();
        if ($favPayWallet !== null) {
            $favPayBal = $favPayWallet->getAvailableBalance($userId);
            if ($favPayBal->getAmount() < $amountMinor) {
                throw WalletException::insufficientBalance(
                    $this->minorToDecimal($favPayBal->getAmount()),
                    $this->minorToDecimal($amountMinor),
                    $favPayBal->getCurrency()
                );
            }
            $curr = $favPayBal->getCurrency() ?: (class_exists(Currency::class) ? Currency::getPrimaryCurrency() : 'BDT');
            $favPayWallet->debit(
                $userId,
                new FavoritePayMoney($amountMinor, $curr),
                $referenceId,
                $description
            );
        }

        return $this->executeInTransaction(function () use ($userId, $amountMinor, $referenceId, $description, $orderId, $favPayWallet) {
            $wallet = $this->walletRepo->lockWalletForUpdate($userId);
            if (!$wallet) {
                $wallet = $this->walletRepo->getOrCreateWallet($userId);
                $wallet = $this->walletRepo->lockWalletForUpdate($userId);
            }

            if ($wallet->status !== 'active') {
                throw WalletException::walletInactive($userId, $wallet->status);
            }

            $currMinor = $this->parseAmountMinor($wallet->balance_amount);
            if ($currMinor < $amountMinor && $favPayWallet === null) {
                throw WalletException::insufficientBalance(
                    $this->minorToDecimal($currMinor),
                    $this->minorToDecimal($amountMinor)
                );
            }

            $afterMinor = max(0, $currMinor - $amountMinor);
            $newBalance = $this->minorToDecimal($afterMinor);
            $amountDec  = $this->minorToDecimal($amountMinor);

            $this->walletRepo->updateBalance((int)$wallet->id, $newBalance);

            $txId = $this->walletRepo->createTransaction([
                'wallet_id'     => (int)$wallet->id,
                'type'          => 'debit',
                'amount'        => $amountDec,
                'balance_after' => $newBalance,
                'order_id'      => $orderId,
                'reference_id'  => $referenceId,
                'description'   => $description,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            return (object)[
                'id'            => $txId,
                'wallet_id'     => (int)$wallet->id,
                'type'          => 'debit',
                'amount'        => $amountDec,
                'balance_after' => $newBalance,
                'order_id'      => $orderId,
                'reference_id'  => $referenceId,
                'description'   => $description,
            ];
        });
    }

    public function reverseDebit(
        int $userId,
        string $amount,
        string $originalReferenceId,
        string $description = '',
        ?int $orderId = null
    ): object {
        $amountMinor = $this->parseAmountMinor($amount);
        if ($amountMinor <= 0) {
            throw WalletException::invalidAmount($amount);
        }

        $reversalRef = 'rev_' . $originalReferenceId;
        $existing = $this->walletRepo->getTransactionByReference($reversalRef);
        if ($existing !== null) {
            return $existing;
        }

        // Synchronize refund deposit with Favorite Pay wallet if available
        $favPayWallet = $this->getFavoritePayWalletService();
        if ($favPayWallet !== null) {
            try {
                $curr = class_exists(Currency::class) ? Currency::getPrimaryCurrency() : 'BDT';
                $favPayWallet->deposit(
                    $userId,
                    new FavoritePayMoney($amountMinor, $curr),
                    $reversalRef,
                    $description !== '' ? $description : "Reversal for {$originalReferenceId}"
                );
            } catch (Throwable $e) {
                throw WalletException::depositFailed($e->getMessage());
            }
        }

        return $this->executeInTransaction(function () use ($userId, $amountMinor, $reversalRef, $originalReferenceId, $description, $orderId) {
            $wallet = $this->walletRepo->lockWalletForUpdate($userId);
            if (!$wallet) {
                $wallet = $this->walletRepo->getOrCreateWallet($userId);
                $wallet = $this->walletRepo->lockWalletForUpdate($userId);
            }

            $currMinor = $this->parseAmountMinor($wallet->balance_amount);
            $afterMinor = $currMinor + $amountMinor;
            $newBalance = $this->minorToDecimal($afterMinor);
            $amountDec  = $this->minorToDecimal($amountMinor);

            $this->walletRepo->updateBalance((int)$wallet->id, $newBalance);

            $desc = $description !== '' ? $description : "Reversal for {$originalReferenceId}";

            $txId = $this->walletRepo->createTransaction([
                'wallet_id'     => (int)$wallet->id,
                'type'          => 'reversal',
                'amount'        => $amountDec,
                'balance_after' => $newBalance,
                'order_id'      => $orderId,
                'reference_id'  => $reversalRef,
                'description'   => $desc,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            return (object)[
                'id'            => $txId,
                'wallet_id'     => (int)$wallet->id,
                'type'          => 'reversal',
                'amount'        => $amountDec,
                'balance_after' => $newBalance,
                'order_id'      => $orderId,
                'reference_id'  => $reversalRef,
                'description'   => $desc,
            ];
        });
    }

    /**
     * Fallback helper to directly synchronize a deposit to Favorite Pay's authoritative
     * wallet and ledger tables within the active transaction or when the service is unbound.
     */
    protected function directSyncFavoritePayDeposit(
        int $userId,
        int $amountMinor,
        string $currency,
        string $referenceId,
        string $description
    ): void {
        if ($this->db === null || !$this->db->tableExists('favorite_pay_wallets') || !$this->db->tableExists('favorite_pay_wallet_entries')) {
            return;
        }

        // 1. Ensure customer wallet exists in favorite_pay_wallets
        $wallet = $this->db->selectOne("SELECT * FROM favorite_pay_wallets WHERE user_id = ?", [$userId]);
        if (!$wallet) {
            $this->db->insert('favorite_pay_wallets', [
                'user_id'    => $userId,
                'balance'    => 0,
                'currency'   => $currency,
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $wallet = $this->db->selectOne("SELECT * FROM favorite_pay_wallets WHERE user_id = ?", [$userId]);
        }

        if (!$wallet) {
            throw WalletException::walletNotFound($userId);
        }

        $newBalance = (int)$wallet->balance + $amountMinor;
        $entryId = 'led_' . bin2hex(random_bytes(8));
        $now = date('Y-m-d H:i:s');

        // 2. Insert ledger entry into favorite_pay_wallet_entries
        $this->db->insert('favorite_pay_wallet_entries', [
            'entry_id'        => $entryId,
            'wallet_id'       => $wallet->id,
            'user_id'         => $userId,
            'type'            => 'credit',
            'amount'          => $amountMinor,
            'balance_after'   => $newBalance,
            'reference_type'  => 'refund',
            'reference_id'    => $referenceId,
            'idempotency_key' => 'op:' . bin2hex(random_bytes(12)),
            'description'     => $description !== '' ? $description : "Deposit to wallet",
            'metadata'        => json_encode(['currency' => $currency]),
            'created_at'      => $now,
        ]);

        // 3. Update favorite_pay_wallets balance
        $this->db->update('favorite_pay_wallets', [
            'balance'    => $newBalance,
            'updated_at' => $now,
        ], ['id' => $wallet->id]);

        if (function_exists('do_action')) {
            do_action('favorite.pay.wallet.credited', [
                'user_id'  => $userId,
                'amount'   => $amountMinor,
                'currency' => $currency,
                'balance'  => $newBalance,
            ]);
        }
    }

    protected function executeInTransaction(callable $callback): mixed
    {
        if ($this->db === null) {
            return $callback();
        }

        $pdo = null;
        try {
            $pdo = $this->db->getConnection();
        } catch (Throwable) {
        }

        if ($pdo !== null && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            try {
                $result = $callback();
                $pdo->commit();
                return $result;
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        return $callback();
    }

    protected function parseAmountMinor(string $amount): int
    {
        $clean = trim($amount);
        if ($clean === '' || !is_numeric($clean)) {
            return 0;
        }

        return (int)round((float)$clean * 100);
    }

    protected function minorToDecimal(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }
}

