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

## Promotion and coupon builder

The admin builder supports category and product-label targeting for both offers and coupons. Category-only and label-only targeting work independently; when both are selected, a product must match both. Leaving both empty means all products.

Offer types available in the builder:
- Percentage discount: calculate the discount automatically from each eligible item's price.
- Fixed amount off and sale price.
- Free shipping.
- Buy X Get Y: set the qualifying quantity and free quantity.
- Bundle price: set the bundle quantity and the bundle's total price in minor currency units.

Coupons support fixed amount, percentage, or free-shipping benefits, plus code normalization, date window, minimum subtotal, maximum discount, total/per-customer usage limits, stacking preference, and category/label targeting. The cart-level `PromotionEngine` domain calculator evaluates product offers and coupon eligibility in server-side code. Prices and quantities must come from trusted server-side product/cart records; do not trust browser-calculated totals.

Important release boundary: the admin builder and deterministic domain calculator are implemented, but the current Favorite Shop branch still does not have the complete storefront/cart/checkout and order-redemption pipeline wired to this engine. Do not enable these offers on a live shop until that integration and end-to-end database tests are complete.

## Current release status
This is still a foundation release, not a production-ready store. Product/admin CRUD, a fully wired management dashboard, storefront, cart/checkout routes, delivery-rate engine, permission/CSRF integration, Favorite Pay adapter, migration-runner compatibility, and end-to-end database tests remain to be implemented. The CI workflow packages a clearly labelled foundation ZIP; do not use it as a live store.


## Scheduled offers, coupons and stock rules
- Sale offers have UTC start/end timestamps. They evaluate as scheduled, active, expired, draft or paused; expired offers must stop applying without overwriting the product's regular price.
- Coupons normalize codes, validate date windows and usage limits, support fixed/percentage/free-shipping discounts, minimum subtotal, maximum discount, per-customer limits and stacking preference.
- Coupon redemptions are stored in a plugin-owned table with an order-level uniqueness constraint to avoid duplicate redemption rows.
- Stock status rules distinguish in stock, low stock, out of stock and explicitly enabled backorders. Product and variant low-stock thresholds are configurable; zero stock does not silently allow orders.
- The new domain tests cover schedule boundaries, discount caps, free shipping, expiry and stock/overselling rules.
- Admin navigation now includes Products, Offers & Sales, and Coupons. Basic offer/coupon create, list and edit screens are included, along with product low-stock threshold and explicit backorder controls. These screens persist schedules and policy settings; they do not yet make the frontend checkout automatically apply promotions. Checkout integration, atomic stock reservation/decrement, restoration on cancellation/return, per-customer redemption enforcement, variant-level stock UI, and full end-to-end verification remain required before production use.


## Category/label-targeted scheduled offers
- Admin can create product categories under Favorite Shop → Categories, assign one or more categories to each product, and add comma-separated product labels such as `summer`, `clearance` or `featured`.
- Each scheduled offer can target multiple categories, multiple product labels, or all products. If both category and label filters are selected, a product must match both filters; each selected filter group accepts any match within that group.
- Percentage offers are calculated against each matched product's own regular price in integer minor currency units. For example, a 15% offer on ৳100 calculates a ৳15 discount and a ৳85 offer price. The stored regular price is not overwritten.
- The `OfferPricing::bestPriceForProduct()` domain function selects the lowest eligible scheduled price and uses priority to break ties. Tests cover scope matching, expiry, percent math and discount totals.
- Storefront/cart/checkout integration must call this pricing function and snapshot the final applied offer/price on order items before percentage offers are considered fully live for customers.
