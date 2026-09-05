# Favorite Pay — Production Release Package

This directory contains the authoritative, verified production release archive and checksum verification metadata for **Favorite Pay**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Package File** | \Favorite-Pay.zip\ |
| **Package Size** | 112,489 bytes |
| **SHA-256 Checksum** | .c294a56d0f878108f9adbf5e1768e4b66f5f9be1c8479f83f8c1fa6c0bdcd6\ |
| **Source Repository** | \avoritecode/Favorite-CMS-Universal\ |
| **Locked Source Commit** | \b27754341a93a2f31dbbe02f6bcb0281e958d58\ |
| **Test Suite Verification** | 298 tests, 1,219 assertions (0 failures, 0 errors) |
| **Target Platform** | Favorite CMS Core (\Favorite-CMS-Universal\) |
| **Plugin Identifier** | \avorite-pay\ |

---

## Supported Payment Gateways

### Manual Payment Methods (Local Bangladesh)
- \manual_bkash\ (bKash Manual Send Money / Personal)
- \manual_nagad\ (Nagad Manual Send Money)
- \manual_rocket\ (Rocket Manual Send Money)
- \manual_bank\ (Direct Bank Transfer)

### Automatic Payment Gateways
- **bKash Merchant Checkout**:
  - Primary ID: \kash_direct  - Aliases: \kash_auto\, \kash_merchant  - Features: Token grant & refresh, create payment, execute payment, status query, refunds
- **Binance Pay OpenAPI v3**:
  - Primary ID: \inance_pay  - Alias: \inance  - Features: Multi-currency conversion, cryptographic SHA512-HMAC signing, certificate lookup, order creation, query, refunds

### Removed Gateways
- **City Bank**: Completely removed from the implementation, registry, admin configuration, and test suites.

---

## Architectural Guarantees & Verification

- **Currency Separation**: Original commercial order amount and currency are strictly decoupled from gateway acquiring charge amounts.
- **Authoritative FX**: Conversions use live rate feeds with fail-closed behavior on stale rates.
- **Conversion Snapshotting**: Conversion rates and acquired amounts are snapshotted immutably upon PaymentIntent/PaymentAttempt creation.
- **Financial Precision**: All calculations use integer minor units (no floating-point drift).
- **Core Currency Immutability**: Core primary currency cannot be changed once financial records exist.
- **Package Cleanliness**: Zero \.git/\, zero development secrets, zero \.env\, zero tests, and zero unrelated Core files in the release archive.

---

## Verification & Installation

To verify archive integrity:
\\ash
sha256sum -c checksums.sha256
\
To install into Favorite CMS:
1. Extract \Favorite-Pay.zip\ directly into the \plugins/\ directory of your Favorite CMS installation.
2. The folder structure should be \plugins/favorite-pay/\.
3. Activate the plugin from the Favorite CMS Admin Panel (**Plugins** &rarr; **Favorite Pay**).
