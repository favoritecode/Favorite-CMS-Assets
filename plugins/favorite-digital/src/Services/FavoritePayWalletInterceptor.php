<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Services;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Pay\Contracts\PaymentServiceInterface;
use FavoriteCMS\Pay\Contracts\WalletServiceInterface;
use FavoriteCMS\Pay\Domain\Money;
use FavoriteCMS\Pay\Domain\WalletLedgerEntry;
use InvalidArgumentException;
use Throwable;

/**
 * FavoritePayWalletInterceptor
 *
 * Decorator for Favorite Pay's WalletServiceInterface.
 * Prevents erroneous customer wallet deposits when a customer purchases a digital product,
 * service, package, or membership via external gateway or direct checkout.
 *
 * Genuine wallet recharges (source_reference starting with 'wrc_') and other non-store
 * payments pass through directly to Favorite Pay's wallet ledger settlement.
 */
class FavoritePayWalletInterceptor implements WalletServiceInterface
{
    protected WalletServiceInterface $inner;
    protected Application $app;

    public function __construct(WalletServiceInterface $inner, Application $app)
    {
        $this->inner = $inner;
        $this->app = $app;
    }

    public function getInnerService(): WalletServiceInterface
    {
        return $this->inner;
    }

    public function getBalance(int $userId): Money
    {
        return $this->inner->getBalance($userId);
    }

    public function getAvailableBalance(int $userId): Money
    {
        return $this->inner->getAvailableBalance($userId);
    }

    public function deposit(
        int $userId,
        Money $amount,
        string $referenceId,
        string $description = ''
    ): WalletLedgerEntry {
        return $this->inner->deposit($userId, $amount, $referenceId, $description);
    }

    public function debit(
        int $userId,
        Money $amount,
        string $referenceId,
        string $description = ''
    ): WalletLedgerEntry {
        return $this->inner->debit($userId, $amount, $referenceId, $description);
    }

    public function hold(
        int $userId,
        Money $amount,
        string $referenceId
    ): WalletLedgerEntry {
        return $this->inner->hold($userId, $amount, $referenceId);
    }

    public function releaseHold(
        int $userId,
        Money $amount,
        string $referenceId
    ): WalletLedgerEntry {
        return $this->inner->releaseHold($userId, $amount, $referenceId);
    }

    public function finalizeHold(
        int $userId,
        Money $amount,
        string $referenceId,
        string $description = 'Withdrawal payout completed'
    ): WalletLedgerEntry {
        return $this->inner->finalizeHold($userId, $amount, $referenceId, $description);
    }

    /**
     * Intercepts payment settlement.
     * If the payment originates from Favorite Digital for an order purchase (product, service,
     * package, membership), bypasses wallet crediting so the customer does NOT receive an erroneous
     * spendable wallet credit for spent checkout funds.
     */
    public function settleSuccessfulPayment(string $transactionId): WalletLedgerEntry
    {
        $trimmedId = trim($transactionId);
        if ($trimmedId === '') {
            throw new InvalidArgumentException("Transaction ID cannot be empty.");
        }

        $intentInfo = $this->resolvePaymentIntentInfo($trimmedId);
        if ($intentInfo !== null) {
            $sourcePlugin = (string)($intentInfo['source_plugin'] ?? '');
            $sourceRef = (string)($intentInfo['source_reference'] ?? '');

            // Check if this is a store order purchase from favorite-digital (not a wallet recharge)
            if ($sourcePlugin === 'favorite-digital' && !str_starts_with($sourceRef, 'wrc_')) {
                $userId = (int)($intentInfo['user_id'] ?? 0);
                $currBalance = $userId > 0 ? $this->inner->getBalance($userId) : new Money(0, 'BDT');

                return new WalletLedgerEntry(
                    'led_settle_' . bin2hex(random_bytes(8)),
                    $userId,
                    'release',
                    new Money(0, $currBalance->getCurrency()),
                    $currBalance,
                    'order_settlement',
                    $trimmedId,
                    "Order checkout payment {$trimmedId} (external payment settled without wallet credit)"
                );
            }
        }

        // Delegate legitimate recharges and standard payments to Favorite Pay's inner service
        return $this->inner->settleSuccessfulPayment($trimmedId);
    }

    public function getLedgerHistory(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->inner->getLedgerHistory($userId, $limit, $offset);
    }

    public function getHeldBalance(int $userId): Money
    {
        return $this->inner->getHeldBalance($userId);
    }

    public function getTotalBalance(int $userId): Money
    {
        return $this->inner->getTotalBalance($userId);
    }

    public function getFilteredLedgerHistory(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->inner->getFilteredLedgerHistory($userId, $filters, $limit, $offset);
    }

    public function getFilteredLedgerCount(int $userId, array $filters = []): int
    {
        return $this->inner->getFilteredLedgerCount($userId, $filters);
    }

    public function getLedgerEntry(string $entryId, ?int $userId = null): ?WalletLedgerEntry
    {
        return $this->inner->getLedgerEntry($entryId, $userId);
    }

    public function getGlobalWalletOverview(): array
    {
        return $this->inner->getGlobalWalletOverview();
    }

    public function searchCustomerWallets(string $query, int $limit = 20): array
    {
        return $this->inner->searchCustomerWallets($query, $limit);
    }

    public function getGlobalRecentActivity(int $limit = 15): array
    {
        return $this->inner->getGlobalRecentActivity($limit);
    }

    /**
     * @return array{source_plugin: string, source_reference: string, user_id: int}|null
     */
    protected function resolvePaymentIntentInfo(string $transactionId): ?array
    {
        if ($this->app->has(PaymentServiceInterface::class)) {
            try {
                $ps = $this->app->make(PaymentServiceInterface::class);
                if (method_exists($ps, 'getIntent')) {
                    $intent = $ps->getIntent($transactionId);
                    if ($intent !== null) {
                        return [
                            'source_plugin'    => (string)$intent->getSourcePlugin(),
                            'source_reference' => (string)$intent->getSourceReference(),
                            'user_id'          => (int)($intent->getUserId() ?? $intent->getCustomerId() ?? 0),
                        ];
                    }
                }
            } catch (Throwable) {
            }
        }

        if ($this->app->has(Database::class)) {
            try {
                $db = $this->app->make(Database::class);
                if ($db->tableExists('favorite_pay_transactions')) {
                    $row = $db->selectOne(
                        "SELECT source_plugin, source_reference, user_id FROM favorite_pay_transactions WHERE transaction_id = ? LIMIT 1",
                        [$transactionId]
                    );
                    if ($row) {
                        return [
                            'source_plugin'    => (string)($row->source_plugin ?? ''),
                            'source_reference' => (string)($row->source_reference ?? ''),
                            'user_id'          => (int)($row->user_id ?? 0),
                        ];
                    }
                }
            } catch (Throwable) {
            }
        }

        return null;
    }
}
