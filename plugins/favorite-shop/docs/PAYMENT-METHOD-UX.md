# Favorite Shop payment method UX

## Customer-facing defaults
1. Cash on Delivery (COD) is the default selected payment method whenever COD is enabled.
2. Show only the prepaid methods that are currently enabled/configured in Favorite Pay and supported by the verified Favorite Shop payment adapter.
3. Customers may optionally choose a prepaid method to pay before delivery; choosing prepaid must never be required.
4. Do not hardcode bKash/Nagad/Rocket/bank/card methods in Favorite Shop. Discover enabled methods through Favorite Pay's supported integration contract. If Favorite Pay cannot be queried safely, show COD and log the integration issue for admin rather than guessing or silently invoking an old gateway.
5. A prepaid checkout creates an unpaid/pending-payment order or payment intent; it must not mark the order paid until Favorite Pay confirms payment through a trusted server-side verification/callback.
6. COD checkout creates an order with payment_method=cash_on_delivery and payment_status=unpaid. Payment collection is recorded separately from delivery status.
7. If a selected prepaid method is unavailable or disabled before payment confirmation, explain briefly and let the customer return to COD without losing cart or entered details.

## Checkout presentation
- First/default option: Cash on Delivery (Pay when delivered).
- Secondary group: Pay Now (optional), listing only enabled Favorite Pay methods with clear names.
- Keep the customer's selection when validation fails; never clear the cart or form because a coupon or payment attempt fails.
- Final summary always shows subtotal, shipping, discount, and total.
