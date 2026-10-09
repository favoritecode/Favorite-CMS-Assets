# Favorite Shop v1.0.0

Favorite Shop is the physical-goods commerce plugin for Favorite CMS Universal, separate from Favorite Digital.

## Boundaries
- Favorite Shop: physical product catalog, SKUs/variants, stock, cart, shipping addresses, delivery/tracking, order lifecycle, and COD collection.
- Favorite Pay: shared payment/rate integration. Prepaid integration must use installed Favorite Pay contracts after compatibility verification.
- Favorite Digital: unchanged; this plugin does not read or modify its products, orders, digital delivery, wallet, or refund tables.
- Favorite CMS Universal core: unchanged.

## Required simple customer experience
- Guest checkout is allowed; login/registration is optional.
- One-page checkout asks only for recipient name, phone, delivery address, and optional note.
- Color and size are selected on the product page. Every combination may have its own price, sale price, SKU, stock, and optional image.
- Admin can apply a shared price to all variants in one bulk action, with individual overrides available.
- An automatic free-delivery threshold applies without requiring a coupon.
- Coupons are optional and support fixed discount, percentage discount, or free delivery. Invalid coupon attempts must not clear the cart or checkout fields.
- Show a transparent total: subtotal, delivery, discounts, final amount.

## COD invariant
COD checkout creates an order with payment status unpaid. Delivery/order status and payment status are separate. An order may be delivered while payment remains unpaid until staff records the collected amount. Only full collection marks payment collected; partial collection remains unpaid.

## Foundation scope
The initial foundation includes metadata, autoloading, plugin-owned schema, and COD lifecycle rules. Admin CRUD, storefront, cart/checkout routes, permissions/CSRF, Favorite Pay adapter, migration-runner compatibility, and release ZIP packaging still require implementation and testing. This is not yet a production-ready store.

Money is stored in integer minor units (BDT poisha). Order items must snapshot SKU/name/variant/price so later product edits cannot rewrite historical orders. Inventory movement ledger supports traceable stock changes.
