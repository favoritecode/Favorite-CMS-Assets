# Favorite Digital — Official Production Release Package

This directory contains the official, verified production release package and checksum metadata for **Favorite Digital**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | 1.0.8 |
| **Package File** | Favorite-Digital-v1.0.8.zip / Favorite-Digital.zip |
| **Package Size** | 253,462 bytes |
| **ZIP Entries** | 123 entries (Root: `favorite-digital/`) |
| **SHA-256 Checksum** | `ede1fa7429b4b87cb0fdd815aab19a41efab2ba3b255a15875e76dde6bb9626f` |
| **Source Repository** | `favoritecode/Favorite-CMS-Assets` |
| **Target Platform** | Favorite CMS Universal (>= 1.0.0) |
| **Plugin Identifier** | `favorite-digital` |
| **PHP Compatibility** | PHP >= 8.1.0 (Tested on PHP 8.2.12) |

---

## What's New in v1.0.8

1. **[Membership & Wallet Accounting Separation]**:
   - Complete architectural separation of membership status/entitlements from spendable wallet balance.
   - Membership purchases via Wallet Balance cleanly deduct funds and activate membership without duplicate credit back.
   - Membership purchases via external gateways/manual payments activate membership with 0.00 wallet balance impact.
   - Introduced `FavoritePayWalletInterceptor` decorating `WalletServiceInterface` to bypass spendable wallet credits for digital purchases while delegating genuine wallet recharges (`wrc_*`).
   - Integrated idempotent `WalletReconciliationService` to automatically compensate prior erroneous membership purchase credits.

2. **[Customer Membership UX & Profile Navigation]**:
   - Registered dedicated "Membership" (`digital_membership`) navigation item in customer profile dropdown at `order: 12` (between Profile at 10 and Wallet at 14).
   - Redesigned `/account/membership` using `CustomerThemeShell` native card layout without standalone HTML wrappers.
   - Displays ACTIVE badge, plan title, purchase/start/expiry dates in Core site timezone, remaining days countdown, and auto-renewal toggle with CSRF protection (`POST /account/membership/toggle-auto-renew`).
   - Provides clear empty state with CTA button linking directly to membership plans when inactive.

---

## What's New in v1.0.7

1. **[Site Timezone Alignment]**:
   - Dynamic conversion of stored UTC timestamps into Core's configured Site Timezone across all customer and admin views via `fdig_format_datetime()` and `fdig_format_date()`.
   - Non-destructive display conversion delegating directly to Core `format_date()` / `\FavoriteCMS\Core\DateTime::format()`, reading from `Setting::get('general', 'timezone')`.
   - Stored database values and schema remain in strict UTC with zero database mutations.

2. **[Orders Admin UI Polish]**:
   - Modernized and polished administrative Orders list (`views/admin/orders/index.php`).
   - Human-readable timestamp presentation (`d M Y, h:i A` e.g., `18 Sep 2026, 01:43 PM`), elegant status chips, clean tabular numbers, and dark-mode compatible layout.
   - Preserves 100% of search, filter parameters, pagination, bulk actions, and CSRF protection.

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
