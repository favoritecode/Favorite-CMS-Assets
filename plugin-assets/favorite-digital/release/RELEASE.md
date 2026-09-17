# Favorite Digital — Official Production Release Package

This directory contains the official, verified production release package and checksum metadata for **Favorite Digital**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | 1.0.6 |
| **Package File** | Favorite-Digital-v1.0.6.zip / Favorite-Digital.zip |
| **Package Size** | 243,455 bytes |
| **ZIP Entries** | 120 entries (Root: `favorite-digital/`) |
| **SHA-256 Checksum** | `1691edac9894390094611f217787e97019083693022ba8216a084e9095c8ec76` |
| **Source Repository** | `favoritecode/Favorite-CMS-Assets` |
| **Target Platform** | Favorite CMS Universal (>= 1.0.0) |
| **Plugin Identifier** | `favorite-digital` |
| **PHP Compatibility** | PHP >= 8.1.0 (Tested on PHP 8.2.12) |

---

## What's New in v1.0.6

1. **[Administrative Bulk Actions]**:
   - Integrated `BulkActionService` across Products, Services, Packages, Memberships, and Orders administrative screens.
   - Comprehensive multi-select actions with permission-consistent authorization checks.
   - Idempotent and transaction-safe batch state transitions.

2. **[Admin Navigation Alignment & Core v1.0.14 Compatibility]**:
   - Clean, standard menu registration structure (`favorite-digital` parent with `favorite-digital` landing submenu "Digital Products").
   - 100% compatible with Favorite CMS Universal v1.0.14 generic duplicate-parent submenu suppression.

3. **[Customer Theme Shell & Dark Mode Polish]**:
   - Enhanced `CustomerThemeShell` integration across all storefront, wallet, and checkout views.
   - High-contrast dark-mode semantic design tokens for optimal readability.

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
