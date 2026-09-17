# Favorite Pay — Production Release Package

This directory contains the authoritative, verified production release archive and checksum verification metadata for **Favorite Pay**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | `v1.0.12` |
| **Package File** | `Favorite-Pay.zip` / `Favorite-Pay-v1.0.12.zip` |
| **Package Size** | 253,598 bytes |
| **SHA-256 Checksum** | `d4936f5162fbf82a439a179e99744099c654f0063f6bd11d6e920e580d9c3b5b` |
| **Source Repository** | `favoritecode/Favorite-CMS-Assets` |
| **Target Platform** | Favorite CMS Core (`Favorite-CMS-Universal`) |
| **Plugin Identifier** | `favorite-pay` |
| **PHP Compatibility** | PHP >= 8.1.0 (Tested on PHP 8.2.12) |
| **Entries** | 129 entries |

---

## What's New in v1.0.12

1. **[Customer Theme Integration & Shell]**:
   - Integrated `CustomerThemeShell` to automatically render within the active frontend theme's customer shell while retaining standalone fallback.
   - Clean dark-mode and responsive styling alignment.

2. **[Wallet Recharge Separation & Financial Safety]**:
   - Strict separation between customer wallet recharge and checkout flows.
   - Wallet balance remains available for Digital Store checkout while being strictly excluded from wallet recharge payment methods.
   - Obsolete `manual_bd` gateway permanently removed; clean concrete gateways: `manual_bkash`, `manual_nagad`, `manual_rocket`, `manual_bank`, `bkash_direct`, `binance_pay`.
   - Mandatory sender account and TrxID validation with payment proof image attachment support.

---

## What's New in v1.0.11

1. **[Recharge] Gateway Availability & Configuration Filtering**:
   - Replaced unconstrained gateway enumeration in `CustomerAccountController::getAvailableGateways()` with authoritative `GatewayRegistry::available($currency)` filtering.
   - Shows **only** gateways that are both enabled and configured (`isConfigured() === true`, e.g. account numbers or credentials saved by administrator) for the wallet currency.
   - Automatically excludes unconfigured gateways (e.g. Nagad, Rocket, Bank Transfer when not set up by admin) server-side without hardcoding.
   - Validates POST submissions in `handleRechargeSubmit()` against available gateways to reject unconfigured or unavailable gateway selections.
   - Retains concrete gateway IDs (`manual_bkash`, `manual_nagad`, `manual_rocket`, `manual_bank`), ensuring configured bKash displays its specific merchant details.
   - Generic `manual_bd` and `wallet` balance remain strictly excluded from recharge methods.

2. **[Payment History & Transactions] 500 Internal Server Error Resolution**:
   - Isolated customer pagination `$page` variable in `views/customer/layout.php` before loading the theme header.
   - Provided `currentPage` in view data alongside `page` for backwards compatibility.
   - Made `fpay_format_money()` type-resilient (`int|float|string`) to avoid strict float/string minor-unit type errors.

3. **[Packaging] Standardized Production ZIP**:
   - Clean 127 entries under `favorite-pay/` root prefix.
   - POSIX/Unix attributes (0755 for directories, 0644 for files).
   - Strict exclusions of tests, git, dev tools, and temporary files.

---

## Supported Payment Gateways

### Manual Payment Methods (Local Bangladesh)
- `manual_bkash` (bKash Manual Send Money / Personal)
- `manual_nagad` (Nagad Manual Send Money)
- `manual_rocket` (Rocket Manual Send Money)
- `manual_bank` (Direct Bank Transfer)

### Automatic Payment Gateways
- **bKash Merchant Checkout**:
  - Primary ID: `bkash_direct` | Aliases: `bkash_auto`, `bkash_merchant` | Features: Token grant & refresh, create payment, execute payment, status query, refunds
- **Binance Pay OpenAPI v3**:
  - Primary ID: `binance_pay` | Alias: `binance` | Features: Multi-currency conversion, cryptographic SHA512-HMAC signing, certificate lookup, order creation, query, refunds

---

## Verification & Installation

To verify archive integrity:
```bash
sha256sum -c checksums.sha256
```

To install or update in Favorite CMS:
1. Log in to your Favorite CMS Admin Dashboard.
2. Navigate to **Plugins** &rarr; **Add New / Upload**.
3. Upload `Favorite-Pay.zip` and click **Install Now**.
4. Activate the plugin.
