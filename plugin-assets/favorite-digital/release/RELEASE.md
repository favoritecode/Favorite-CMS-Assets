# Favorite Digital — Official Production Release Package

This directory contains the official, verified production release package and checksum metadata for **Favorite Digital**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | 1.0.13 |
| **Package File** | Favorite-Digital-v1.0.13.zip / Favorite-Digital.zip |
| **Package Size** | 279,442 bytes |
| **ZIP Entries** | 101 production files (Root: `favorite-digital/`) |
| **SHA-256 Checksum** | `d9a1ebf82447b62f96d19f9fea55a7c703ab7a89d47220908b91c997d4dbca39` |
| **Source Repository** | `favoritecode/Favorite-CMS-Assets` |
| **Target Platform** | Favorite CMS Universal (>= 1.0.0) |
| **Plugin Identifier** | `favorite-digital` |
| **PHP Compatibility** | PHP >= 8.1.0 (Tested on PHP 8.2.12 and PHP 8.3) |

---

## What's New in v1.0.13

1. **Unified Order Status Architecture**:
   - Consolidated administrative order status controls into exactly ONE editable Order Status dropdown containing ONLY: `Processing`, `Partial`, `Complete`, and `Refund`.
   - `Pending` and `Cancel` are enforced as strict payment-controlled states derived from payment lifecycle events (Favorite Pay confirmation or rejection/failure). They are completely excluded from selectable dropdown options.
   - Payment Status and Fulfillment Status are displayed in the Admin Order Overview as clean, read-only audit badges.

2. **Authoritative Refund Engine & Strict Digital Asset Protection**:
   - **Digital Products**: Partial refund is strictly forbidden (`"Partial refund is not allowed for Digital Product orders."`). Full refund credits customer wallet and immediately revokes all entitlements, download tokens, and access (`REFUNDED / REFUNDED / REVOKED`).
   - **Digital Services**: Partial refund prompts for exact refund amount (up to remaining refundable balance), credits customer wallet, preserves delivered deliverables, and leaves order in `Partial` status (`PARTIAL_REFUND / PARTIALLY_DELIVERED`).
   - **Final Service Refund**: When a partial refund reaches the total received amount (or upon full refund), status automatically transitions to `REFUNDED / REFUNDED / REVOKED` and revokes deliverable access.

3. **Nested PDO Transaction Crash Prevention & Refund Wallet-Credit Reliability**:
   - Resolved nested PDO transaction conflict during Favorite Digital wallet checkout (`processWalletPayment()`) and refunds (`executeRefund()`). Wallet operations execute against Favorite Pay `WalletService` outside outer transactions, eliminating `"There is already an active transaction"` crashes and ensuring wallet credits are authoritatively reflected.
   - Swallowed deposit exceptions in `WalletService::credit()` resolved; failures bubble up as `WalletException::depositFailed()`.
   - Dynamic Favorite Pay resolution via container (`getFavoritePayWalletService()`) and deterministic transaction reference tracking.
   - Failed wallet debits or failed gateway verifications cleanly transition orders to `Cancel` (`CANCELLED / FAILED / CANCELLED`) with zero ledger leaks.

4. **Payment Lifecycle Alignment**:
   - Manual Payment: Initiated in `Pending` (`PENDING / UNPAID / UNFULFILLED`).
   - Confirmation: Favorite Pay payment approval transitions Digital Products to `Complete` (`COMPLETED / PAID / FULFILLED`) and Digital Services to `Processing` (`PROCESSING / PAID / PROCESSING`).
   - Rejection/Failure: Favorite Pay rejection hook (`favorite.pay.manual.rejected`, `favorite.pay.payment.failed`) transitions order to `Cancel` (`CANCELLED / FAILED / CANCELLED`).
   - Maintains full backward compatibility with schema migrations 001 through 018.

---

## What's New in v1.0.12

1. **Fixed Duplicate Wallet Balance Indicator**:
   - Fixed duplicate wallet pill rendering by respecting theme-level header balance handling and preventing filter re-injection.

2. **Fixed Duplicate Premium Membership Diamond Indicator**:
   - Added strict string occurrence idempotency checks (`!str_contains($html, 'cms-premium-badge')`) ensuring exactly one diamond icon is rendered.

3. **Idempotent Header Rendering Safeguards**:
   - Preserved theme render state throughout request lifecycle, preventing duplicate pills across repeated menu renders (e.g. desktop header and mobile drawer).

4. **Plugin Bootstrap Singleton Protection**:
   - Added singleton instance check in `FavoriteDigitalPlugin::bootstrap()` to prevent duplicate plugin instantiation and duplicate hook callback registrations.

5. **Profile Dropdown Unchanged**:
   - Profile dropdown options, links, order, and permissions remain 100% untouched.

6. **Favorite Web Theme Untouched**:
   - Zero modifications to `themes/favorite-web` theme source code.

7. **Zero Financial / Database / Business Logic Mutations**:
   - No database migration or financial/business logic changes.

---

## What's New in v1.0.11

1. **Active Premium Membership Indicator**:
   - Added compact gold diamond indicator beside the profile icon when the authenticated user has an active premium membership.
   - Checked server-side and automatically hidden when absent, expired, cancelled, inactive, or for guest visitors.

2. **Favorite Digital Wallet Balance Indicator**:
   - Added user's spendable wallet balance indicator immediately to the left of the profile icon.
   - Follows Core's active Primary Currency formatter (`Currency::format()` / `format_currency()`).
   - Links directly to `/account/wallet` and displays only for authenticated users when Favorite Digital is installed.

3. **Dropdown Menu & Theme Integrity**:
   - Profile dropdown options and behavior remain 100% unchanged.
   - Favorite Web theme source remains 100% untouched via pluggable helper and hook deduplication.
   - Zero database migration, schema, or financial/business logic changes.

---

## What's New in v1.0.10

1. **[Primary Currency Denomination Synchronization]**:
   - Registered listener on `currency.primary_changed` hook to update published product prices and active customer wallets to the new Primary Currency denomination without altering numeric amounts.
   - Newly created products, services, bundles, and membership tiers dynamically inherit the active Primary Currency as their base currency fallback.

2. **[Dynamic Currency Symbols & Elimination of Hardcoded Taka]**:
   - Replaced all hardcoded `৳` and `BDT` symbols across customer views (Storefront, Checkout, Wallet, Manual Recharge, Refund History) and admin management screens (Products, Services, Packages, Orders) with dynamic `Currency::getSymbol()` lookups.

3. **[Preserved Accounting & Membership Separation]**:
   - Membership purchases via Wallet Balance continue to deduct funds cleanly with zero duplicate wallet credits, fully compatible with arbitrary primary currencies (BDT, INR, USD, EUR, GBP).

---

## What's New in v1.0.9

1. **[FavoritePayWalletInterceptor Transparent Proxy Delegation Fix]**:
   - Fixed live HTTP 500 regression on `/account/wallet` caused by `FavoritePayWalletInterceptor` missing concrete `WalletService` methods.
   - Implemented explicit delegation for `getWalletCurrency(int $userId): string`, `getPrimaryCurrency(): string`, `hasActivity(): bool`, `hasWallets(): bool`, and `hasLedgerEntries(): bool`.
   - Implemented dynamic magic forwarding via `__call()`, `__get()`, and `__isset()` to transparently forward any unhandled calls or property accesses directly to the inner `WalletService`.
   - Maintained all intended accounting separation and membership business logic without side effects.

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
