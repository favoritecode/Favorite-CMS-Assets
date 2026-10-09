# Favorite Shop v1.0.0

Favorite Shop is the physical-goods commerce plugin for Favorite CMS Universal, separate from Favorite Digital.

## Boundaries
- Favorite Shop: physical product catalog, SKUs/variants, stock, cart, shipping addresses, delivery/tracking, order lifecycle, and COD collection.
- Favorite Pay: shared payment/rate integration. Prepaid integration must use the installed Favorite Pay contracts after compatibility verification.
- Favorite Digital: unchanged; this plugin does not read or modify its products, orders, digital delivery, wallet, or refund tables.
- Favorite CMS Universal core: unchanged.

## COD invariant
A COD checkout creates an order with payment status unpaid. Delivery/order status and payment status are separate. An order may be delivered while payment remains unpaid until staff records the collected amount. Only full collection marks payment collected; partial collection remains unpaid.

## Foundation scope
This initial foundation includes metadata, autoloading, plugin-owned schema, and COD lifecycle rules. Admin CRUD, storefront/catalog, cart/checkout, permissions/CSRF, Favorite Pay adapter, migration-runner compatibility, and release ZIP packaging still need implementation and testing. This is not yet a production-ready store.

Money is stored in integer minor units (BDT poisha). Order items snapshot SKU/name/variant/price so later product edits cannot rewrite historical orders. Inventory movement ledger supports traceable stock changes.
