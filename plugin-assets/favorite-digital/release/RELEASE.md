# Favorite Digital — Official Production Release Package

This directory contains the official, verified production release package and checksum metadata for **Favorite Digital**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | 1.0.5 |
| **Package File** | Favorite-Digital-v1.0.5.zip / Favorite-Digital.zip |
| **Package Size** | 233,950 bytes |
| **ZIP Entries** | 115 entries (Root: `favorite-digital/`) |
| **SHA-256 Checksum** | `674ea2f97462f00dff95f850f7c92732c9010bbc9f013aa072dc01ab391ee604` |
| **Source Repository** | `favoritecode/Favorite-CMS-Assets` |
| **Target Platform** | Favorite CMS Universal (>= 1.0.0) |
| **Plugin Identifier** | `favorite-digital` |
| **PHP Compatibility** | PHP >= 8.1.0 (Tested on PHP 8.2.12) |

---

## What's New in v1.0.5

1. **[Wallet Recharge Fix] CustomerWalletController Request::post() Zero-Argument Resolution**:
   - Fixed `CustomerWalletController::recharge()` to correctly pass the expected field names: `$amount = (string)$request->post('amount', '')` and `$gatewayId = trim((string)$request->post('gateway_id', ''))`.
   - Resolves the fatal production error: `Too few arguments to function FavoriteCMS\Core\Request::post(), 0 passed ... on line 147`.
   - Safely reads manual payment details: `$trxId`, `$senderAccount`, `$notes`.

2. **[Recharge Security & Flow]**:
   - CSRF token validation and session message handling.
   - Pending state enforcement for manual submissions awaiting administrator verification.

---

## Major Capabilities (v1.0.4)

1. **Shared Customer Theme Shell**:
   - Store, product, checkout, order, download, wallet, refund, library, and membership screens use the active theme's shared header and footer when the theme explicitly supports the customer shell.
   - Existing standalone rendering remains the fallback for themes that do not opt in.
   - Commerce, settlement, authorization, and customer data behavior are unchanged.

2. **Service Order Lifecycle & Dynamic Progress State**:
   - When a customer purchases or pays for a digital service (either automatically via wallet/gateway or manually accepted by admin), the initial status is safely set to **Pending** (`⏳ Pending`).
   - Admin order management screen gives 3 selectable options when confirming or managing service orders: **Pending** (`pending`), **Processing** (`processing`), and **Complete** (`completed`).
   - The admin's chosen status is dynamically synced to the customer across storefront, digital library, and order receipts.

3. **Automated Order Cancellation & Wallet Refund**:
   - If an order with confirmed paid funds is cancelled by an administrator (or through lifecycle cancellation), the authoritative paid amount is **automatically refunded 100% to the customer's Favorite Digital Wallet**.
   - Customer's wallet balance is immediately credited, creating an immutable audit transaction.

---

## Installation & Verification

### Integrity Check:
```bash
sha256sum -c checksums.sha256
```

### Installation / Upgrade:
1. Log in to your Favorite CMS Admin Dashboard (`https://cms.canbangla.net/admin` or local).
2. Navigate to **Plugins** &rarr; **Add New / Upload**.
3. Upload `Favorite-Digital.zip` and click **Install Now**.
4. Activate/reload the plugin.
