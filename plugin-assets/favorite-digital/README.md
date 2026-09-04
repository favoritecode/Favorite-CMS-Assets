# Favorite Digital — Visual Asset Foundation

Welcome to the visual asset foundation for **Favorite Digital**, the official digital products, file downloads, and membership management plugin for the Favorite CMS ecosystem.

---

## 1. Purpose & Ecosystem Architecture

**Favorite Digital** powers the digital commerce lifecycle within Favorite CMS:

```text
Favorite CMS Core
        ↓
   Favorite Pay (Financial layer: gateways, BDT base pricing, wallet, checkout)
        ↓
Favorite Digital (Digital products, downloads, memberships, versioning)
        ↓
Favorite Web Theme (Visual presentation & templates)
```

### Core Business Concepts & Roles
- **Digital Products & Files**: Free and paid downloadable goods (e-books, code templates, audio packs, design assets, and digital services).
- **Product Versions**: Incremental release management (v1.0, v2.0), change tracking, and file update notifications.
- **Download System & History**: Protected download access tokens, link expiration, rate limiting, and customer download logs.
- **Memberships & Category Access**: Recurring or tier-based membership passes that unlock **unlimited eligible downloads** within designated product categories while the membership remains active.
- **Membership Lifecycle**: When a membership expires, new downloads for membership-gated products are blocked; renewal immediately restores access; previously downloaded files remain usable by the customer.

### Architectural Rules
- **Zero Payment Logic**: Favorite Digital does **NOT** implement custom payment gateways. All payment handling, wallet debiting, and transactional settlement are delegated to **Favorite Pay**.
- **Currency Standards**: Base prices are in Bangladeshi Taka (BDT). Currency display conversions and exchange-rate locking are strictly managed by Favorite Pay.
- **Theme Presentation**: **Favorite Web** provides the visual presentation layer through template views and public APIs without owning business logic.

---

## 2. Brand Relationship & Identity Policy

Favorite Digital is a key branch of the Favorite CMS ecosystem:
- **Ecosystem Cohesion**: Follows the core visual palette established in [`design/design-tokens.md`](../../design/design-tokens.md) (`#2563EB` Favorite Blue, `#0EA5E9` Vivid Sky, and `#10B981` Emerald for active/unlocked states).
- **No Independent Identity**: Do **NOT** create a disconnected brand identity, separate color schemes, or custom font stacks.
- **No Standalone Product Logo Yet**: Official sub-brand product logos will be derived directly from the Favorite CMS master mark during a unified ecosystem branding milestone.

---

## 3. Directory Structure & Asset Inventory

```text
plugin-assets/favorite-digital/
├── README.md                  # This foundational guide
├── icons/                     # Domain-specific digital commerce icons (24×24 SVG)
│   ├── category-access.svg    # Category folder with authorized access badge
│   ├── digital-library.svg    # Customer owned digital asset collection / folios
│   ├── download-access.svg    # Download entitlement pass / credential token
│   ├── download-history.svg   # Chronological download archive & log
│   ├── membership-access.svg  # Active membership card with ribbon pass
│   └── product-version.svg    # Software/asset version release node tree
├── illustrations/             # Conceptual spot artwork (400×300 SVG)
│   ├── digital-delivery.svg   # High-speed digital fulfillment & version delivery
│   ├── digital-library.svg    # Customer asset vault screen with categorized media
│   └── membership-access.svg  # VIP pass unlocking categorized product packages
└── placeholders/              # Product cover art & media guidelines
    └── README.md              # Sizing specs (no fake product covers or mockups)
```

---

## 4. Reusability & Avoided Duplicate Assets

To maintain repository discipline and avoid clutter, existing assets across `/icons/`, `/theme-assets/`, and `/plugin-assets/favorite-pay/` were audited. The following assets are **intentionally reused rather than duplicated**:

| Existing Global Asset | Digital Commerce Context | Decision |
|---|---|---|
| [`icons/actions/download.svg`](file:///e:/Favorite-CMS-Assets/icons/actions/download.svg) | Generic download trigger | Reused globally (not duplicated) |
| [`icons/commerce/download-product.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/download-product.svg) | Cloud product download indicator | Reused globally (not duplicated) |
| [`icons/commerce/membership.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/membership.svg) | Generic VIP / star badge | Reused globally (not duplicated) |
| [`icons/commerce/product.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/product.svg) | General product box | Reused globally (not duplicated) |
| [`icons/commerce/order.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/order.svg) | Digital order receipt | Reused globally (not duplicated) |
| [`icons/content/file.svg`](file:///e:/Favorite-CMS-Assets/icons/content/file.svg) | Single file attachment | Reused globally (not duplicated) |
| [`icons/content/folder.svg`](file:///e:/Favorite-CMS-Assets/icons/content/folder.svg) | Directory grouping | Reused globally (not duplicated) |
| [`icons/content/category.svg`](file:///e:/Favorite-CMS-Assets/icons/content/category.svg) | General category grid | Reused globally (not duplicated) |
| [`theme-assets/favorite-web/illustrations/digital-products.svg`](file:///e:/Favorite-CMS-Assets/theme-assets/favorite-web/illustrations/digital-products.svg) | Marketing landing hero graphic | Reused in theme layer (not duplicated) |

Only icons and illustrations with **distinct digital-product semantics** (`digital-library`, `download-access`, `product-version`, `membership-access`, `category-access`, `download-history`, `digital-delivery`) were created here.

---

## 5. Technical Specifications

Every SVG in this directory complies with strict technical standards:
- **Scalability**: All icons use `viewBox="0 0 24 24"`; all illustrations use `viewBox="0 0 400 300"`.
- **Pure Vector**: 100% vector XML markup. Zero embedded raster images, zero base64 data, zero external fonts.
- **Adaptive Theming**: Icons use `stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"` for flawless light and dark mode rendering.
- **Lightweight**: Icons are ~330–390 bytes each; illustrations are ~3.5–4.6 KB each.

---

## 6. Security & Prohibited Content

Favorite Digital assets must **NEVER** contain:
- Actual downloadable product archives, ZIP bundles, or binary files.
- Customer license keys, purchase codes, or private download tokens.
- Personally identifiable customer data (PII) or transaction records.
- Backend PHP code, Blade templates, database schemas, or API keys.
