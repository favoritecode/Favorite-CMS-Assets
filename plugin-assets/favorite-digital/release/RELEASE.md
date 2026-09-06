# Favorite Digital — Official Production Release Package

This directory contains the official, verified production release package and checksum metadata for **Favorite Digital**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | 1.0.1 |
| **Package File** | Favorite-Digital-v1.0.1.zip |
| **Package Size** | 222,009 bytes |
| **ZIP Entries** | 93 files (Root: avorite-digital/) |
| **SHA-256 Checksum** | 48284213b87743258d4a16ed6dde8b1f1a6bbb7697d5a736795024ba9a6c91e4 |
| **Source Repository** | avoritecode/Favorite-CMS-Universal |
| **Source Commit** | 34fb0139fd130b900c0a2e879384a9c4be1b959 (ix(favorite-digital): improve manual payments and digital resources) |
| **Target Platform** | Favorite CMS Universal (>= 1.0.0) |
| **Plugin Identifier** | avorite-digital |
| **PHP Compatibility** | PHP >= 8.1.0 (Tested on PHP 8.2.12) |
| **Test Suite Verification** | 533 tests, 1,839 assertions (532 passed, 1 skipped, 0 failures, 0 errors) |

---

## Major Capabilities (v1.0.1)

1. **Manual Bangladesh Payment Flow Improvements**:
   - Customer checkout displays actual configured manual payment instructions for bKash, Nagad, Rocket, and Bank Transfer.
   - Dynamic configuration binding: receiver account number, account name, account type, bank branch, routing number, and custom instructions.
   - Secure customer verification inputs: sender phone/account, transaction reference / TrxID (mandatory), and optional payment proof.
   - Payment proof file upload with strict MIME verification, randomized filenames, traversal protection, and isolated private storage.
   - Admin order verification flow showing full manual transaction details and customer-provided payment proofs.

2. **Digital Resource & Media Assets**:
   - Product & service cover image support: file upload or direct HTTPS image URL.
   - Flexible digital product resources: downloadable file, external secured resource URL, or hybrid both.
   - Secure external URL redirection with customer entitlement enforcement and access logging.
   - Broad safe digital formats (archives, images, videos, audio, documents, code assets, datasets) with strict blocking of server-side executable scripts.
   - Public storefront integration with 'View' modal and direct resource delivery.

3. **Core Digital Commerce Platform**:
   - Digital Products & Downloads with 64-character unguessable cryptographic tokens.
   - Service & Package Management with isolated entitlement tracking.
   - Membership Lifecycle & Gated Access (Weekly & Monthly billing with deterministic month-end clamping).
   - Customer Digital Wallet & Recharge Hub with immutable FX snapshots and minor integer units.
   - Order Refunds to Wallet with automated entitlement revocation.
   - Storefront & Account Portal with CSRF protection and IDOR immunity.

---

## Technical Specifications & Requirements

- **PHP Version**: 8.1.0 or higher.
- **PHP Extensions**: pdo, pdo_sqlite or pdo_mysql, cmath, json, ileinfo.
- **Database**: SQLite 3 or MySQL 5.7+ / MariaDB 10.3+. Full database prefix abstraction support (15 tables, Migration 016 applied).
- **Dependencies**: Integrates seamlessly with avorite-pay via public API contracts without code duplication. Zero Node.js, Redis, or external worker dependencies.

---

## Installation & Verification

### Integrity Check:
\\ash
sha256sum -c checksums.sha256
\
### Installation / Upgrade:
1. Extract Favorite-Digital-v1.0.1.zip into the plugins/ directory of your Favorite CMS installation so that it resides at plugins/favorite-digital/.
2. Navigate to **Admin Dashboard > Plugins**.
3. Locate **Favorite Digital** and click **Activate** (or reload if upgrading).
4. Migration 016 will automatically apply new media and resource fields to products and product details.
