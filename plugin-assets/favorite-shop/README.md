# Favorite Shop — Visual Asset Foundation

Welcome to the visual asset foundation for **Favorite Shop**, the official physical e-commerce, multi-variant inventory, and order fulfillment plugin for the Favorite CMS ecosystem.

---

## 1. Purpose & Ecosystem Architecture

**Favorite Shop** manages the physical goods commerce lifecycle within Favorite CMS:

```text
Favorite CMS Core
        ↓
   Favorite Pay (Financial layer: gateways, BDT base pricing, wallet, checkout)
        ↓
   Favorite Shop (Physical products, variants, stock, shipping, COD)
        ↓
Favorite Web Theme (Visual storefront presentation & templates)
```

### Core Business Concepts & Roles
- **Physical Products & Variations**: Physical retail goods with multi-attribute variations (sizes, colors, materials, SKUs).
- **Stock & Inventory Tracking**: Stock levels, available units, warehouse thresholds, and low-stock alerts.
- **Customer Addresses & Zones**: Delivery destination addresses, regional Bangladesh delivery zones (Inside Dhaka, Outside Dhaka, Sub-Dhaka), and delivery charge calculation rules.
- **Physical Orders & Fulfillment**: Order processing, dispatch, courier transit, delivery verification, and return logistics.
- **Cash on Delivery (COD)**: Doorstep physical cash payment upon order receipt. **COD is exclusively available for physical product orders**; it is never offered for Favorite Digital downloads or digital memberships.

### Architectural Rules
- **Payment Processing Delegated to Favorite Pay**: Favorite Shop does **NOT** implement its own card, mobile banking, or crypto payment gateways. It delegates all online payment transactions, wallet checkouts, and exchange-rate locking directly to **Favorite Pay**.
- **Currency Standards**: Base prices for both products and delivery fees are in Bangladeshi Taka (BDT). Visitor currency conversion is managed by Favorite Pay.
- **Theme Presentation**: **Favorite Web** handles the frontend storefront layout and customer experience via public template APIs without owning shopping logic.

---

## 2. Brand Relationship & Identity Policy

Favorite Shop is an integral part of the Favorite ecosystem:
- **Parent Brand Alignment**: Grounded in the master palette from [`design/design-tokens.md`](../../design/design-tokens.md) (`#2563EB` Favorite Blue, `#0EA5E9` Vivid Sky) with amber warning accents (`#F59E0B`) for stock alerts and emerald (`#10B981`) for completed deliveries.
- **No Independent Identity**: Do **NOT** create a disconnected brand system, separate fonts, or conflicting color schemes.
- **No Standalone Product Logo Yet**: Official sub-brand product logos will be derived directly from the Favorite CMS master mark during a unified ecosystem branding milestone.

---

## 3. Directory Structure & Asset Inventory

```text
plugin-assets/favorite-shop/
├── README.md                  # This foundational guide
├── icons/                     # Domain-specific physical commerce icons (24×24 SVG)
│   ├── address.svg            # Delivery destination / residential address
│   ├── cash-on-delivery.svg   # Doorstep parcel exchange with cash token
│   ├── delivery-zone.svg      # Map destination pin with regional zone radius
│   ├── inventory.svg          # Stacked warehouse crates with package tape
│   ├── order-delivered.svg    # Isometric parcel with verified delivery seal
│   └── product-variation.svg  # Layered attribute cards & variant swatches
├── illustrations/             # Conceptual spot artwork (400×300 SVG)
│   ├── cash-on-delivery.svg   # Doorstep parcel hand-off & verified cash settlement
│   ├── delivery.svg           # Courier van in transit along regional route
│   └── inventory.svg          # Warehouse shelving rack with tiered stock crates
└── placeholders/              # Physical product photo & banner guidelines
    └── README.md              # Aspect ratio standards (no fake stock photos)
```

---

## 4. Reusability & Avoided Duplicate Assets

To keep the repository minimal and maintainable, existing assets across `/icons/`, `/theme-assets/`, `/plugin-assets/favorite-pay/`, and `/plugin-assets/favorite-digital/` were audited. The following assets are **intentionally reused rather than duplicated**:

| Existing Global Asset | Physical Commerce Role | Status |
|---|---|---|
| [`icons/commerce/cart.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/cart.svg) | Shopping cart checkout trigger | Reused globally (not duplicated) |
| [`icons/commerce/product.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/product.svg) | Single physical product box | Reused globally (not duplicated) |
| [`icons/commerce/order.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/order.svg) | Customer invoice / order receipt | Reused globally (not duplicated) |
| [`icons/commerce/shipping.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/shipping.svg) | Standard courier / shipping truck | Reused globally (not duplicated) |
| [`icons/commerce/payment.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/payment.svg) | General credit card payment | Reused globally (not duplicated) |
| [`icons/commerce/money.svg`](file:///e:/Favorite-CMS-Assets/icons/commerce/money.svg) | General cash currency | Reused globally (not duplicated) |
| [`icons/status/warning.svg`](file:///e:/Favorite-CMS-Assets/icons/status/warning.svg) | Low stock alert indicator | Reused globally (not duplicated) |
| [`icons/status/error.svg`](file:///e:/Favorite-CMS-Assets/icons/status/error.svg) | Out of stock badge | Reused globally (not duplicated) |
| [`theme-assets/favorite-web/illustrations/online-shop.svg`](file:///e:/Favorite-CMS-Assets/theme-assets/favorite-web/illustrations/online-shop.svg) | Storefront retail counter hero artwork | Reused in theme layer (not duplicated) |

Only icons and illustrations with **distinct physical retail & fulfillment semantics** (`cash-on-delivery`, `inventory`, `product-variation`, `delivery-zone`, `order-delivered`, `address`, `delivery`) were created here.

---

## 5. Third-Party Payment Branding Policy

- **No Third-Party Marks**: Do **NOT** create, redraw, or commit logos for bKash, Nagad, Visa, Mastercard, Binance, USDT, or banking institutions.
- **Independence**: Favorite Shop remains completely independent of third-party payment marks.
- When merchant provider marks are required for checkout badge displays in production releases, they must be ingested strictly under the verified guidelines defined in [`plugin-assets/favorite-pay/payment-methods/README.md`](../favorite-pay/payment-methods/README.md).

---

## 6. Technical Specifications

Every SVG in this directory complies with strict technical standards:
- **Scalability**: All icons use `viewBox="0 0 24 24"`; all illustrations use `viewBox="0 0 400 300"`.
- **Pure Vector**: 100% vector XML markup. Zero embedded raster images, zero base64 data, zero external fonts.
- **Adaptive Theming**: Icons use `stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`, ensuring automatic color adaptation across light/dark UI themes.
- **Lightweight**: Icons are ~310–560 bytes each; illustrations are ~3.4–4.5 KB each.

---

## 7. Security & Confidentiality Rules

Favorite Shop assets must **NEVER** contain:
- Real customer delivery addresses, phone numbers, or recipient names.
- Merchant transaction exports or live courier API tracking keys.
- Merchant payment credentials or gateway secrets.
- Backend PHP controllers, SQL database dumps, or runtime application files.
