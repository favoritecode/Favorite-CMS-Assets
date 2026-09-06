# Favorite Digital — Official Production Release Package

This directory contains the official, verified production release package and checksum metadata for **Favorite Digital**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | 1.0.0 |
| **Package File** | Favorite-Digital-v1.0.0.zip |
| **Package Size** | 212,296 bytes |
| **ZIP Entries** | 92 files (Root: avorite-digital/) |
| **SHA-256 Checksum** | 366d81c223bcae4d7debd832f0e34b08732611181ce540aad70f75bf8139f6ef |
| **Source Repository** | avoritecode/Favorite-CMS-Universal |
| **Source Commit** | 6989de7 (eat(favorite-digital): implement customer wallet and recharge) |
| **Target Platform** | Favorite CMS Universal (>= 1.0.0) |
| **Plugin Identifier** | avorite-digital |
| **PHP Compatibility** | PHP >= 8.1.0 (Tested on PHP 8.2.12) |
| **Test Suite Verification** | 490 tests, 1,667 assertions (478 passed, 12 skipped, 0 failures, 0 errors) |

---

## Major Capabilities

1. **Digital Products & Downloads**:
   - Secure digital file storage outside webroot with path-traversal prevention.
   - 64-character unguessable cryptographic download tokens.
   - Dynamic access verification: entitlement required, revoked/expired denied, 3-download direct limits vs unlimited active membership access.

2. **Service & Package Management**:
   - Turnaround times, deliverables, and requirements collection for professional services.
   - Bundled packages containing multiple child products with isolated entitlement tracking.

3. **Membership Lifecycle & Gated Access**:
   - Weekly (7-day) and Monthly (calendar month with deterministic month-end clamping) billing.
   - Admin-configurable grace periods, default opt-in auto-renewal, and non-destructive plan upgrades.

4. **Orthogonal Order Lifecycle**:
   - Strict separation of status, payment_status, and ulfillment_status.
   - Immutable historical price snapshots protecting past purchases from future catalog price edits.

5. **Customer Digital Wallet & Recharge Hub**:
   - Platform primary currency (BDT) balance that never expires.
   - WalletService::credit() / debit() as sole mutation authority with integer minor units.
   - Server-enforced limits: ৳50.00 – ৳10,000.00 (Binance Pay dynamic min: 1 USD equivalent).
   - Immutable FX rate snapshotting; fail-closed behavior on stale rates.
   - Manual payment verification (TrxID) lifecycle: pending with zero credit until operator approval.

6. **Order Refunds to Wallet**:
   - 100% of refunds credit to customer digital wallet.
   - Idempotent processing with automatic entitlement revocation.

7. **Customer Storefront & Account Portal**:
   - Responsive, modern discovery storefront (/store, /digital-store).
   - Customer account tabs: Library, Orders, Membership, Refunds, Downloads, Wallet.
   - Strict session authentication, capability authorization, CSRF protection, and IDOR immunity.

---

## Technical Specifications & Requirements

- **PHP Version**: 8.1.0 or higher.
- **PHP Extensions**: pdo, pdo_sqlite or pdo_mysql, cmath, json, ileinfo.
- **Database**: SQLite 3 or MySQL 5.7+ / MariaDB 10.3+. Full database prefix abstraction support.
- **Dependencies**: Integrates seamlessly with avorite-pay via public API contracts without code duplication. Zero Node.js, Redis, or external worker dependencies.

---

## Installation & Verification

### Integrity Check:
`ash
sha256sum -c checksums.sha256
`

### Installation:
1. Extract Favorite-Digital-v1.0.0.zip into the plugins/ directory of your Favorite CMS installation so that it resides at plugins/favorite-digital/.
2. Navigate to **Admin Dashboard > Plugins**.
3. Locate **Favorite Digital** and click **Activate**.
4. All 15 database tables and routes will be registered and initialized automatically.

---

## Known Limitations

- **Initial Release Upgrade**: As 1.0.0 is the initial production release, upgrade paths from prior releases are not applicable.
- **Live MySQL in Local Development**: MySQL compatibility tests are designed to gracefully skip when local MySQL services are offline.
