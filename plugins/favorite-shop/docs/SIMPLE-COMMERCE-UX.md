# Favorite Shop: simple physical-order UX

## Non-negotiable customer experience
- Guest checkout by default; account creation/login is optional, never a purchase blocker.
- Keep checkout to one page and ask only for recipient name, phone, delivery address, and optional note.
- Show items, selected color/size, quantities, subtotal, delivery charge, discount, and final total in one clear summary.
- COD is an obvious default when enabled. Coupons are optional and collapsed behind a small "Have a coupon?" field; customers can checkout without one.
- Do not force customers to configure variations on checkout: color/size are selected on the product page, with the final variation price shown before adding to cart.
- Show delivery-free eligibility automatically when the cart reaches the configured threshold. No coupon code required for an automatic threshold offer.
- If coupon validation fails, explain briefly and preserve the cart and all entered checkout fields.

## Product variations and bulk pricing
- Product can enable color, size, both, or neither.
- Each purchasable variant has its own price, optional sale price, SKU, stock, and optional image.
- Admin can set a shared base price for all selected variants in one action, then optionally edit any variant's price independently.
- Bulk edit actions must clearly say whether they update regular price, sale price, or stock. Ask for confirmation before overwriting many variant prices.
- A product may have a default price; variant-specific price overrides it only when explicitly configured.

## Offers
- Automatic free delivery: administrator sets minimum eligible cart subtotal; qualifying carts receive free delivery automatically.
- Coupons: optional fixed discount, percentage discount, or free delivery; allow minimum subtotal, schedule, usage limit, and optional maximum discount.
- One coupon per order by default for predictable checkout. Do not stack coupons and automatic discounts unless the administrator explicitly enables stacking.
- Order stores immutable snapshots of chosen variant, price, applied coupon/offer, shipping fee, discount and final total, so later edits do not rewrite old orders.

## COD and stock safety
- Placing a COD order does not mean money has been received; payment remains unpaid until collection is recorded.
- Reserve/check stock atomically when placing an order; reject overselling and restore reserved stock on a valid cancellation/expiry.
- Admin can update order status with a short, simple workflow and add courier/tracking details when needed.
