# Favorite Shop: simple physical-order UX

## Non-negotiable customer experience
- Guest checkout by default; account creation/login is optional, never a purchase blocker.
- Keep checkout to one page and ask only for recipient name, phone, delivery address, and optional note.
- Division/State/Province and District/City selectors are optional and configurable per country. If not selected, allow the customer to proceed with address text and use the configured fallback delivery zone.
- Show items, selected color/size, quantities, subtotal, delivery charge, discount, and final total in one clear summary.
- COD is the default selected payment method when enabled. Prepaid methods are optional and discovered from enabled Favorite Pay configuration through a verified adapter.
- Coupons are optional and collapsed behind a small "Have a coupon?" field; customers can checkout without one.
- Show automatic free-delivery eligibility when cart reaches the configured threshold. No coupon code required for an automatic threshold offer.
- If coupon validation fails, explain briefly and preserve cart and all entered checkout fields.

## Product variations and bulk pricing
- Product can enable color, size, both, or neither.
- Each purchasable variant has its own price, optional sale price, SKU, stock, and optional image.
- Admin can set a shared base price for all selected variants in one action, then optionally edit any variant's price independently.
- Bulk edit actions clearly identify whether they update regular price, sale price, or stock; ask for confirmation before overwriting many variant prices.
- A product may have a default price; variant-specific price overrides it only when explicitly configured.

## Offers and delivery zones
- Admin can configure inside-Dhaka/outside-Dhaka rates and custom zones, with a default fallback rate.
- Delivery zones are country-aware and editable; labels and region lists support countries other than Bangladesh.
- Region selection is optional; if omitted, use the configured fallback rate and show the charge before order submission.
- Automatic free delivery: administrator sets minimum eligible cart subtotal; qualifying carts receive free delivery automatically.
- Coupons support fixed discount, percentage discount, or free delivery; allow minimum subtotal, schedule, usage limit, and optional maximum discount.
- One coupon per order by default. Do not stack coupons and automatic discounts unless admin explicitly enables stacking.
- Order stores immutable snapshots of chosen variant, price, shipping zone/rate, applied coupon/offer, discount and final total.

## COD and stock safety
- Placing a COD order does not mean money has been received; payment remains unpaid until collection is recorded.
- Reserve/check stock atomically when placing an order; reject overselling and restore reserved stock on a valid cancellation/expiry.
- Admin can update order status with a short, simple workflow and add courier/tracking details when needed.
