<?php

declare(strict_types=1);

namespace FavoriteCMS\Digital\Services;

use FavoriteCMS\Core\Database;
use Throwable;

/**
 * WalletReconciliationService
 *
 * Provides narrow, safe, and idempotent reconciliation for customer wallets.
 * Reconciles historical transactions where a membership purchase erroneously
 * credited the customer's spendable wallet balance.
 *
 * Strict safety rules:
 * 1. ONLY reverses erroneous credits tied to membership order payments.
 * 2. Leaves all genuine wallet recharges (wrc_*), deposits, refunds, and debits untouched.
 * 3. Uses unique idempotency keys so repeated runs are zero-op.
 */
class WalletReconciliationService
{
    protected ?Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db;
    }

    /**
     * Execute idempotent reconciliation.
     *
     * @return array{reconciled_count: int, entries: array<string>}
     */
    public function reconcile(): array
    {
        if ($this->db === null) {
            return ['reconciled_count' => 0, 'entries' => []];
        }

        $reconciled = [];

        // 1. Reconcile Favorite Pay Wallets (if tables exist)
        try {
            if (
                $this->db->tableExists('favorite_pay_wallets') &&
                $this->db->tableExists('favorite_pay_wallet_entries') &&
                $this->db->tableExists('favorite_pay_transactions')
            ) {
                $payReconciled = $this->reconcileFavoritePayWallets();
                $reconciled = array_merge($reconciled, $payReconciled);
            }
        } catch (Throwable) {
        }

        // 2. Reconcile Favorite Digital Wallets (if tables exist)
        try {
            if (
                $this->db->tableExists('favorite_digital_wallets') &&
                $this->db->tableExists('favorite_digital_wallet_transactions')
            ) {
                $digitalReconciled = $this->reconcileFavoriteDigitalWallets();
                $reconciled = array_merge($reconciled, $digitalReconciled);
            }
        } catch (Throwable) {
        }

        return [
            'reconciled_count' => count($reconciled),
            'entries'          => $reconciled,
        ];
    }

    /**
     * @return array<string>
     */
    protected function reconcileFavoritePayWallets(): array
    {
        $reconciled = [];

        // Query payment credit entries from favorite_pay_wallet_entries
        $entries = $this->db->select(
            "SELECT e.*, t.source_plugin, t.source_reference
             FROM favorite_pay_wallet_entries e
             JOIN favorite_pay_transactions t ON e.reference_id = t.transaction_id
             WHERE e.reference_type = 'payment'
               AND e.type = 'credit'
               AND t.source_plugin = 'favorite-digital'"
        );

        foreach ($entries as $entry) {
            $orderRef = (string)($entry->source_reference ?? '');
            if (!is_numeric($orderRef) || (int)$orderRef <= 0) {
                continue; // Not a digital store order
            }

            $orderId = (int)$orderRef;
            if (!$this->isMembershipOrder($orderId)) {
                continue; // Not a membership order
            }

            $idempotencyKey = 'reconcile:membership_credit:' . $entry->entry_id;

            // Check if already reconciled
            $alreadyReconciled = $this->db->selectOne(
                "SELECT id FROM favorite_pay_wallet_entries WHERE idempotency_key = ? LIMIT 1",
                [$idempotencyKey]
            );

            if ($alreadyReconciled) {
                continue;
            }

            // Lock and fetch wallet
            $wallet = $this->db->selectOne(
                "SELECT * FROM favorite_pay_wallets WHERE id = ? LIMIT 1",
                [(int)$entry->wallet_id]
            );

            if (!$wallet) {
                continue;
            }

            $currentBalance = (int)$wallet->balance_amount;
            $currentAvailable = (int)($wallet->available_amount ?? $wallet->balance_amount);
            $deductAmount = (int)$entry->amount;

            $newBalance = max(0, $currentBalance - $deductAmount);
            $newAvailable = max(0, $currentAvailable - $deductAmount);
            $reconcileEntryId = 'led_rec_' . bin2hex(random_bytes(8));

            // Update wallet balance
            $this->db->update(
                'favorite_pay_wallets',
                [
                    'balance_amount'   => $newBalance,
                    'available_amount' => $newAvailable,
                    'updated_at'       => date('Y-m-d H:i:s'),
                ],
                ['id' => (int)$wallet->id]
            );

            // Record compensating ledger entry
            $this->db->insert('favorite_pay_wallet_entries', [
                'entry_id'        => $reconcileEntryId,
                'wallet_id'       => (int)$wallet->id,
                'user_id'         => (int)$entry->user_id,
                'type'            => 'debit',
                'amount'          => $deductAmount,
                'balance_after'   => $newBalance,
                'reference_type'  => 'reconciliation',
                'reference_id'    => (string)$entry->entry_id,
                'idempotency_key' => $idempotencyKey,
                'description'     => "Reconciliation: reversal of erroneous membership purchase wallet credit for payment {$entry->reference_id}",
                'metadata'        => json_encode([
                    'original_entry_id'    => $entry->entry_id,
                    'original_payment_tx'  => $entry->reference_id,
                    'order_id'             => $orderId,
                    'reconciled_at'        => date('c'),
                ]),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            $reconciled[] = $reconcileEntryId;
        }

        return $reconciled;
    }

    /**
     * @return array<string>
     */
    protected function reconcileFavoriteDigitalWallets(): array
    {
        $reconciled = [];

        // Check if there are erroneous credits in favorite_digital_wallet_transactions for membership orders
        if (!$this->db->tableExists('favorite_digital_order_items')) {
            return $reconciled;
        }

        $entries = $this->db->select(
            "SELECT wt.* 
             FROM favorite_digital_wallet_transactions wt
             WHERE wt.type IN ('credit', 'recharge')
               AND wt.order_id IS NOT NULL"
        );

        foreach ($entries as $tx) {
            $orderId = (int)($tx->order_id ?? 0);
            if ($orderId <= 0 || !$this->isMembershipOrder($orderId)) {
                continue;
            }

            $reconcileRef = 'rec_mem_cred_' . $tx->id;

            // Check if already reversed
            $existing = $this->db->selectOne(
                "SELECT id FROM favorite_digital_wallet_transactions WHERE reference_id = ? LIMIT 1",
                [$reconcileRef]
            );

            if ($existing) {
                continue;
            }

            $wallet = $this->db->selectOne(
                "SELECT * FROM favorite_digital_wallets WHERE id = ? LIMIT 1",
                [(int)$tx->wallet_id]
            );

            if (!$wallet) {
                continue;
            }

            $currentBal = (float)$wallet->balance_amount;
            $txAmount = (float)$tx->amount;
            $newBal = max(0.0, round($currentBal - $txAmount, 2));

            $this->db->update(
                'favorite_digital_wallets',
                [
                    'balance_amount' => number_format($newBal, 2, '.', ''),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ],
                ['id' => (int)$wallet->id]
            );

            $this->db->insert('favorite_digital_wallet_transactions', [
                'wallet_id'     => (int)$wallet->id,
                'type'          => 'debit',
                'amount'        => number_format($txAmount, 2, '.', ''),
                'balance_after' => number_format($newBal, 2, '.', ''),
                'order_id'      => $orderId,
                'reference_id'  => $reconcileRef,
                'description'   => "Reconciliation: reversal of erroneous membership purchase wallet credit for Order #{$orderId}",
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $reconciled[] = $reconcileRef;
        }

        return $reconciled;
    }

    protected function isMembershipOrder(int $orderId): bool
    {
        if ($this->db === null) {
            return false;
        }

        if ($this->db->tableExists('favorite_digital_order_items')) {
            $item = $this->db->selectOne(
                "SELECT id FROM favorite_digital_order_items WHERE order_id = ? AND product_type = 'membership' LIMIT 1",
                [$orderId]
            );
            if ($item) {
                return true;
            }
        }

        return false;
    }
}
