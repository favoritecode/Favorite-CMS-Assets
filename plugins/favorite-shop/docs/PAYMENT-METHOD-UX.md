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

## Current Favorite Shop adapter behavior
- Checkout offers prepaid only when Favorite Pay returns at least one enabled/configured BDT method; COD remains available without Favorite Pay.
- The payment page validates the selected gateway against that live method list. Manual methods collect sender account/number and transaction reference and leave the order in `awaiting_verification` until an operator verifies the payment in Favorite Pay.
- Automatic methods are initiated through `PaymentServiceInterface`. Provider checkout URLs must be valid HTTP(S) URLs. The configured CMS site URL is required for absolute return/callback URLs.
- bKash return parameters are not trusted by themselves: the return is matched to the stored attempt and the gateway driver executes provider-side verification before the intent can be marked successful.
- Favorite Pay intent status events are matched against both order number and the stored intent ID. Success marks the order paid and moves a pending order to processing; failure allows a retry; refund status is synchronized separately.
- A prepaid order with a pending or awaiting-verification payment cannot be cancelled/returned until the payment is resolved in Favorite Pay. A paid or partially refunded prepaid order cannot be cancelled/returned until Favorite Pay records a full refund. This prevents late payment approvals or silent unpaid refunds from corrupting the Shop order/inventory lifecycle.
- These are integration-level safeguards, not a substitute for testing each configured gateway with sandbox credentials and real callbacks/webhooks on an installed CMS.
