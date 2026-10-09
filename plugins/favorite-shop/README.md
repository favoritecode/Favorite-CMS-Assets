# Favorite Shop v1.0.0

Favorite Shop is the physical-goods commerce plugin for Favorite CMS Universal, separate from Favorite Digital.

## Boundaries
- Favorite Shop: physical product catalog, SKUs/variants, stock, cart, shipping addresses, delivery/tracking, order lifecycle, and COD collection.
- Favorite Pay: shared payment/rate integration. COD is the default checkout method; optional prepaid methods come from enabled Favorite Pay configuration through a verified adapter. No gateway is hardcoded.
- Favorite Digital: unchanged; this plugin does not read or modify its products, orders, digital delivery, wallet, or refund tables.
- Favorite CMS Universal core: unchanged.

## Simple checkout and payments
- Guest checkout; login/registration optional.
- One-page checkout asks for recipient name, phone, address, and optional note.
- Country-aware Division/State/Province and District/City selectors are optional and configurable. Missing region data never blocks checkout.
- COD is selected by default. Customer may choose an enabled Favorite Pay prepaid method to pay early.
- A prepaid order is not marked paid until trusted server-side Favorite Pay verification confirms payment.
- If no compatible prepaid methods are available, checkout still works with COD.
- COD orders remain unpaid until staff records money collection; delivery and payment status are separate.

## Product units and optional weight
- Selling units include piece (default), kg, g, litre, ml, and admin-defined custom labels (e.g. pack or bottle).
- Selling quantity/unit is separate from optional shipping weight. Weight is never required to create or sell a product.
- Variant unit settings and optional weight can override product defaults; shipping weight is stored in grams and remains unknown if neither is set.
- See `docs/PRODUCT-MEASUREMENTS.md` for rules and examples.

## Variations, offers and shipping
- Color/size variants can each have price, sale price, SKU, stock and optional image. Admin can bulk-set prices across all variants with individual overrides.
- Admin configures inside-Dhaka/outside-Dhaka rates and custom delivery zones. Zones, labels and region options are editable for other countries.
- Region selectors are optional. If a customer skips them, use the admin-configured fallback delivery charge and display it before checkout submission.
- Automatic free-delivery threshold works without coupon entry. Optional coupons support fixed discount, percentage discount or free shipping.
- Invalid coupon attempts preserve cart and entered fields.
- Order items and applied shipping/discount totals are snapshotted so future configuration changes do not rewrite historical orders.

## COD invariant
A COD checkout creates an order with payment status unpaid. An order may be delivered while payment remains unpaid until staff records the collected amount. Only full collection marks payment collected; partial collection remains unpaid.

## Inventory precision and order history
- Inventory and cart/order quantities use DECIMAL(14,3), allowing 0.5 kg while piece quantities are validated as whole numbers by the domain layer.
- Order items can snapshot the selling unit, package/unit quantity, custom label and known shipping weight.
- Orders have fields for matched shipping-zone, coupon code and discount detail snapshots.
- Installer upgrades are additive and confined to Favorite Shop tables. Existing installations should be backed up before upgrading.

## Current release status
This is still a foundation release, not a production-ready store. Product/admin CRUD, a fully wired management dashboard, storefront, cart/checkout routes, delivery-rate engine, permission/CSRF integration, Favorite Pay adapter, migration-runner compatibility, and end-to-end database tests remain to be implemented. The CI workflow packages a clearly labelled foundation ZIP; do not use it as a live store.


## Scheduled offers, coupons and stock rules
- Sale offers have UTC start/end timestamps. They evaluate as scheduled, active, expired, draft or paused; expired offers must stop applying without overwriting the product's regular price.
- Coupons normalize codes, validate date windows and usage limits, support fixed/percentage/free-shipping discounts, minimum subtotal, maximum discount, per-customer limits and stacking preference.
- Coupon redemptions are stored in a plugin-owned table with an order-level uniqueness constraint to avoid duplicate redemption rows.
- Stock status rules distinguish in stock, low stock, out of stock and explicitly enabled backorders. Product and variant low-stock thresholds are configurable; zero stock does not silently allow orders.
- The new domain tests cover schedule boundaries, discount caps, free shipping, expiry and stock/overselling rules.
- Note: domain rules and schema are added here; admin CRUD screens, checkout integration, transactional stock reservation and full end-to-end verification still need completion before this can be called production-ready.
