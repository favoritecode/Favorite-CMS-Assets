# Favorite Pay — Production Release Package

This directory contains the authoritative, verified production release archive and checksum verification metadata for **Favorite Pay**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | `v1.0.9` |
| **Package File** | `Favorite-Pay.zip` |
| **Package Size** | 245,621 bytes |
| **SHA-256 Checksum** | `8c4a26ef39e7e1a8baadc126a89f845cf3e5ead1968265d70639bcbc8963c910` |
| **Source Repository** | `favoritecode/Favorite-CMS-Assets` |
| **Target Platform** | Favorite CMS Core (`Favorite-CMS-Universal`) |
| **Plugin Identifier** | `favorite-pay` |
| **PHP Compatibility** | PHP >= 8.1.0 (Tested on PHP 8.2.12) |
| **Test Suite Verification** | 523 tests, 2,684 assertions (0 failures, 0 errors, 0 skipped) |

---

## What's New in v1.0.9

1. **Self-Contained Vector SVG Icons**:
   - Replaced legacy Font Awesome string class literals with inline vector SVG rendering via `FavoriteCMS\Pay\Support\PaymentIcon`.
   - Eliminates visible fallback text (`fas`, `fa-wallet`, `fa-receipt`, `fa-bell`, `fa-exchange-alt`, `fa-credit-card`) across all themes and shared hosting environments.
   - Zero external CDN dependencies (no Font Awesome kit, no Cloudflare, no Google Fonts).
2. **Dedicated Icon Asset Foundation**:
   - Synchronized pure 24×24 SVG vector assets in `plugin-assets/favorite-pay/icons/` and plugin runtime package `assets/icons/`.
3. **Repository Architecture Separation**:
   - Official release home relocated to `favoritecode/Favorite-CMS-Assets`.
   - Core CMS repository (`Favorite-CMS-Universal`) remains pure, fresh, and plugin-independent.
4. **Security & Accessibility Hardening**:
   - Inline SVG attributes sanitized against event-handler injection (`on*`) and protocol exploits (`javascript:`).
   - Non-interactive icons properly labeled with `aria-hidden="true"`.

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

To install into Favorite CMS:
1. Extract `Favorite-Pay.zip` directly into the `plugins/` directory of your Favorite CMS installation.
2. The folder structure should be `plugins/favorite-pay/`.
3. Activate the plugin from the Favorite CMS Admin Panel (**Plugins** &rarr; **Favorite Pay**).
