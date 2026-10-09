# Product selling units and optional shipping weight

Selling unit and shipping weight are separate concepts.

- Selling unit defaults to `piece`; supported units are `piece`, `kg`, `g`, `litre`, `ml`, and `custom`.
- Unit quantity defaults to `1`. Examples: 1 kg rice, 500 g honey, 1 piece shirt, 1 litre oil.
- A custom unit (for example, pack or bottle) requires an admin-provided label.
- Shipping weight in grams is optional for every physical product. Missing weight never blocks product creation or checkout.
- A variant may override the product's selling unit/quantity and shipping weight. A missing variant weight falls back to product weight; if both are missing, shipping weight remains unknown.
- Never infer shipping weight from the selling unit. For example, 1 kg of rice may have a 1,050 g packaged shipping weight.
- Any future weight-based delivery rule must explicitly define its behavior for unknown weights; it must not silently treat unknown weight as zero.
- Orders should snapshot unit labels, quantities, and any known effective shipping weight at order placement so edits to a product do not rewrite historical orders.
