# Payment Provider Branding Guidelines

This directory is reserved for external payment provider branding, gateway badges, and merchant acceptance marks utilized by **Favorite Pay**.

---

## Strict Policy on Third-Party Brand Assets

To protect intellectual property, maintain legal compliance, and prevent unauthorized trademark usage:

- **Do NOT commit or redraw unofficial provider logos** (e.g., bKash, Nagad, Visa, Mastercard, Binance, USDT, or banking institutions) without verified licensing or direct partnership authorization.
- **Do NOT commit third-party provider logos during initial repository setup**.
- This directory remains clean until official, authorized vector assets are curated from verified partner brand portals.

---

## Guidelines for Future Ingestion

When official payment method badges or logos are added to this directory in future production releases, the following rules must be strictly adhered to:

### 1. Adherence to Provider Brand Guidelines
- Every payment provider (e.g., Visa, Mastercard, bKash, Nagad, Binance) maintains strict brand, color, safe-zone, and aspect-ratio specifications.
- Acceptance marks must be acquired directly from the provider's official developer or merchant portal (e.g., Visa Brand Center, Mastercard Brand Center).

### 2. Trademark & Integrity Preservation
- Provider logos and trademarks must **never** be altered, recolored, tilted, cropped, overlaid with unapproved effects, or combined directly with the Favorite CMS logo into a single glyph.
- Maintain required clear space around all provider marks as specified by their brand rules.

### 3. Verification & Documentation
- Before committing any provider asset, the following information must be documented:
  1. Official source URL from the provider's verified portal.
  2. The terms of use or merchant license agreement governing the asset.
  3. Date of acquisition and version of the asset.

### 4. Visual & Architectural Isolation
- Payment provider assets must remain strictly segregated within this `payment-methods/` directory.
- They must **never** be merged with Favorite's core brand identity, master logo, or proprietary icon sets.
- In UI implementations (such as checkout selectors), provider logos must be displayed inside standardized badge containers with proper padding and neutral backdrops.
