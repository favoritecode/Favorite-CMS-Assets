# Favorite Page Builder

Elementor-style visual page builder for Favorite CMS Universal. This is a standalone plugin; it does not modify the Favorite CMS Universal core.

## Current v1.0.0 scope
- Visual editor shell with element palette, canvas, click-to-edit inspector, responsive canvas widths, JSON import/export, save and publish.
- Elements: heading, text, image, button, divider, spacer, recent/category/tag post grid, Favorite Digital product grid, checkout CTA, order-confirmation block, sanitized custom HTML.
- Separate JSON document storage in the plugin-owned `favorite_page_builder_pages` table.
- Published pages are served at `/builder/{slug}`.
- Starter template library for product landing, product showcase and thank-you pages.
- Favorite Digital order confirmation can show order number, payment/order status and total when the signed-in customer opens the builder page with `?order_number=ORDER_NUMBER`; ownership is checked before details are shown.
- PHP-only runtime; no Node.js server or external page-builder dependency.

## Install
Upload or copy `plugins/favorite-page-builder` into either the CMS plugins directory or the sibling `Favorite-CMS-Assets/plugins` directory supported by the CMS plugin manager. Activate **Favorite Page Builder** from Admin → Plugins.

## Important integration notes
- A checkout CTA with a selected Favorite Digital product submits to the existing `/store/{slug}/buy` endpoint with the session CSRF token; this preserves the commerce plugin's own authentication, order creation, and payment workflow. A custom checkout URL can also be configured.
- Product grid is compatible with Favorite Digital's published product table for recent/specific products. It does not create orders, change prices, or process payments.
- Core Favorite Digital currently has no built-in product category/tag taxonomy. Commerce plugins can supply category/tag filtered products with the `favorite_page_builder_products` filter, returning an array of product rows with `title`, `slug`, `final_price`/`price`, `currency`, and optional `cover_image_url`/`image_url`/`description`.
- Post grids query Favorite CMS posts and support recent, category and tag modes.
- Builder pages currently use a dedicated `/builder/{slug}` route and independent JSON storage. They do not replace existing CMS Pages automatically.
- A confirmation block can securely display a signed-in customer's Favorite Digital order details when the page is opened with `?order_number=...`. Automatically redirecting a payment-success flow to a custom confirmation page requires the active commerce plugin to integrate that destination.
- JSON import validates the document shape and opens it in the editor; saving is a separate action.
