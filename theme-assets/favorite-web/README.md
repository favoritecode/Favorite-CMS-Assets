# Favorite Web — Theme Asset Foundation

Welcome to the visual asset foundation for **Favorite Web**, the official flagship business theme for the Favorite CMS ecosystem.

---

## 1. Theme Purpose & Identity

**Favorite Web** is designed as the premier business, commerce, and portfolio showcase theme for Favorite CMS. It provides an elegant, modern, high-performance presentation layer designed to showcase the full capabilities of the CMS core and official ecosystem plugins.

### Architectural Separation
- **Favorite CMS Core**: Content management architecture, routing, user roles, security, and schema definitions.
- **Favorite Web (Theme)**: Pure visual presentation, semantic page layouts, typography rhythm, and aesthetic design components.
- **Ecosystem Plugins (`Favorite Pay`, `Favorite Digital`, `Favorite Shop`)**: Transaction handling, payment gateways, product modeling, digital fulfillment, and checkout flows. Favorite Web simply styles their UI output.

---

## 2. Visual Direction & Aesthetic Language

Favorite Web follows the core design philosophy outlined in the Favorite CMS brand identity and design tokens:
- **Clean & Modern**: Uncluttered whitespace, clear typographic hierarchy, and purposeful subtle shadows.
- **Trustworthy & Professional**: Deep slate neutrals (`#0F172A`, `#1E293B`) paired with high-clarity electric blues (`#2563EB`, `#0EA5E9`).
- **Lightweight & High Performance**: 100% vector-based SVGs (< 3 KB each) ensuring instantaneous render times, zero pixelation on retina screens, and minimal bundle weight.

---

## 3. Asset Inventory

### A. Backgrounds (`backgrounds/`)
Full-bleed and section background patterns designed for CSS background or SVG image embedding:
- `hero-abstract.svg` (1440×900, 2.0 KB): Modern geometric polygonal backdrop with radiant brand gradients for primary landing page heroes.
- `hero-grid.svg` (1440×900, 0.9 KB): Technical grid matrix with subtle perspective accent lines, ideal for developer and SaaS feature banners.
- `section-soft.svg` (1440×300, 0.8 KB): Gentle wave contour transition with light gradient fills for separating content blocks.

### B. Conceptual Illustrations (`illustrations/`)
Isometric and clean line-and-fill spot illustrations (400×300 `viewBox`) designed for feature callouts, value propositions, and marketing sections:
- `platform.svg` (2.8 KB): Central modular CMS block connected to responsive client nodes, representing the universal extensibility of Favorite CMS.
- `digital-products.svg` (2.3 KB): Floating package card with download badges and file streams, illustrating instant digital downloads for `Favorite Digital`.
- `online-shop.svg` (2.8 KB): Modern storefront with display counter, shopping cart, and verified badge, representing `Favorite Shop` e-commerce.
- `support.svg` (2.4 KB): Conversational messaging bubbles with a support headset badge, representing customer service, docs, and community assistance.

### C. Decorative Accents (`decorative/`)
Subtle modular graphic elements used to add visual interest behind cards, headers, and section edges:
- `dots.svg` (160×160, 1.6 KB): 8×8 matrix of micro-dots for corner accents.
- `grid.svg` (200×200, 0.8 KB): Seamless square grid pattern tile for tech accents.
- `glow.svg` (600×600, 0.5 KB): Radial blurred gradient light-burst for ambient dark or light mode glow backdrops.

### D. Placeholders (`placeholders/`)
- `README.md`: Specifications, standard aspect ratios (`16:9`, `4:3`, `1:1`), and naming conventions for future photography and product media without committing fake stock assets.

---

## 4. Usage in Theme Development

When building out the actual theme in the `Favorite-Web` repository:
1. **Backgrounds**: Embed via CSS `background-image: url('...')` or inline SVG containers with `preserveAspectRatio="xMidYMid slice"`.
2. **Illustrations**: Place directly into feature grids, onboarding flows, or empty state dialogs.
3. **Decorative Elements**: Position absolutely with low opacity (`opacity: 0.1` to `0.4`) and `pointer-events: none`.
4. **Color Tokens**: All assets use the standard hex codes defined in [`design/design-tokens.md`](../../design/design-tokens.md), guaranteeing visual cohesion across the platform.
