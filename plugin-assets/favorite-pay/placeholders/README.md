# Favorite Pay — Media Placeholders & Screenshot Guidelines

This directory governs guidelines and conventions for future production imagery, merchant receipt previews, payment gateway previews, and verification media for **Favorite Pay**.

---

## Strict Policy: No Fake Payment Data or Screenshots

To protect privacy, security, and repository cleanliness:

- **Do NOT commit fake payment screenshots, mock bank statements, or simulated transaction receipts**.
- **Do NOT generate mockups containing fake credit card numbers**, simulated bank accounts, customer personally identifiable information (PII), or fictitious billing addresses.
- In local development, automated testing, and CI pipelines, use CSS placeholder skeletons, programmatic canvas mocks, or sanitized mock data fixtures rather than static image dumps.

---

## Standards for Future Production Imagery

When legitimate screenshots, documentation previews, or tutorial visuals are curated for Favorite Pay, they must adhere to the following specifications:

### 1. Mandatory Data Sanitization
- All transaction identifiers, merchant accounts, customer names, wallet addresses, and monetary amounts must be fully sanitized, anonymized, or use well-known RFC test values (e.g., test card numbers from official gateway sandbox docs).
- QR codes for mobile payments must link strictly to test sandbox environments or RFC documentation endpoints, never to live personal wallets.

### 2. Standard Aspect Ratios & Resolutions

| Asset Type | Standard Ratio | Recommended Dimensions | Primary Format | Purpose |
|---|---|---|---|---|
| **Gateway Preview Card** | `16:9` | 1280×720, 800×450 | WebP, Progressive JPEG | Documentation, admin gateway settings preview |
| **Receipt / Invoice Preview** | `4:3` | 1200×900, 800×600 | WebP, Progressive JPEG | Checkout flow docs, customer portal previews |
| **Payment Modal Mock** | `1:1` or `4:3` | 800×800, 800×600 | WebP, SVG | Onboarding guide, feature announcements |
| **Vector Fallbacks** | Scalable `viewBox` | Dynamic | SVG | Empty transaction state, offline payment note |

### 3. File Naming Conventions

All media files must follow lowercase kebab-case naming indicating role and context:
```text
[type]-[context]-[ratio].[ext]
```

Examples:
- `preview-manual-checkout-16x9.webp`
- `modal-wallet-topup-4x3.webp`
- `receipt-summary-spec-4x3.webp`
- `placeholder-empty-ledger.svg`

### 4. Licensing & Legitimate Sourcing
- Any asset committed to this directory must have a clearly documented open-source, corporate-owned, or partner-authorized usage right.
