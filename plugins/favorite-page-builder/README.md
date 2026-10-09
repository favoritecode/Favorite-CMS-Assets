# Favorite Page Builder

Elementor-style visual page builder for Favorite CMS Universal. This is a standalone plugin; it does not modify the Favorite CMS Universal core.

## Current v1.0.0 scope
- Visual editor shell with element palette, canvas, click-to-edit inspector, responsive canvas widths, JSON import/export, save and publish.
- Elements: heading, text, image, button, divider, spacer, recent/category/tag post grid, Favorite Digital product grid, checkout CTA, order-confirmation block, sanitized custom HTML.
- Separate JSON document storage in the plugin-owned `favorite_page_builder_pages` table.
- Published pages are served at `/builder/{slug}`.
- Starter template definitions for product landing, product showcase and thank-you pages.
- PHP-only runtime; no Node.js server or external page-builder dependency.

## Install
Upload or copy `plugins/favorite-page-builder` into either the CMS plugins directory or the sibling `Favorite-CMS-Assets/plugins` directory supported by the CMS plugin manager. Activate **Favorite Page Builder** from Admin → Plugins.

## Important integration notes
- Checkout CTAs link to an explicitly configured checkout URL, or use the generic `/checkout?product_id=ID` fallback. A real checkout requires an active compatible commerce plugin and its actual checkout URL/workflow.
- Product grid is best-effort compatible with Favorite Digital's product table. It does not create orders, change prices, or process payments.
- Builder pages currently use a dedicated `/builder/{slug}` route and independent JSON storage. They do not replace existing CMS Pages automatically.
- JSON import validates the document shape and opens it in the editor; saving is a separate action.
