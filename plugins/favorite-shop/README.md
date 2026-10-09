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

## Variations, offers and shipping
- Color/size variants can each have price, sale price, SKU, stock and optional image. Admin can bulk-set prices across all variants with individual overrides.
- Admin configures inside-Dhaka/outside-Dhaka rates and custom delivery zones. Zones, labels and region options are editable for other countries.
- Region selectors are optional. If a customer skips them, use the admin-configured fallback delivery charge and display it before checkout submission.
- Automatic free-delivery threshold works without coupon entry. Optional coupons support fixed discount, percentage discount or free shipping.
- Invalid coupon attempts preserve cart and entered fields.
- Order items and applied shipping/discount totals are snapshotted so future configuration changes do not rewrite historical orders.

## COD invariant
A COD checkout creates an order with payment status unpaid. An order may be delivered while payment remains unpaid until staff records the collected amount. Only full collection marks payment collected; partial collection remains unpaid.

## Foundation status
Current branch contains plugin metadata, autoloading, plugin-owned schema and domain rules/specifications. Admin CRUD, storefront, cart/checkout routes, delivery-rate engine, permissions/CSRF, Favorite Pay adapter, migration-runner compatibility, and release ZIP still require implementation and testing. This is not yet a production-ready store.
