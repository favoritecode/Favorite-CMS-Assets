# Favorite Web Official Theme

Favorite Web Official is the flagship business and digital storefront theme for Favorite CMS. It presents Favorite Web services, learning platforms, digital products, memberships, and editorial content in one responsive experience.

## Installation

1. Upload `Favorite-Web-Official.zip` from **Admin → Themes**.
2. Activate **Favorite Web Official**.
3. Open **Customize Theme** to select the accent color, logo, favicon, sidebar layout, footer copyright, and homepage section order.
4. Assign Primary and Footer menus and configure the provided widget regions.

The ZIP must retain its single `favorite-web/` root directory.

## Favorite Digital integration

When Favorite Digital is active, the homepage automatically reads published digital products and membership plans through the plugin's registered `StorefrontService`. Product titles, images, prices, discounts, descriptions, URLs, and membership periods remain authoritative plugin data.

Without Favorite Digital, the theme remains fully usable and shows clear category introductions instead of invented products or prices. The section manager can also disable or reorder these blocks.

## Customization & Advanced Theme Customizer

Favorite Web 1.1.1 includes an Advanced Theme Customizer accessible via **Admin → Appearance → Customize**:

- **Site Identity**: Site title, tagline, custom logo (with adjustable width), and favicon using Core Media Library.
- **Header & Navigation**: Sticky header toggle, search bar toggle, store action button toggle, dark mode toggle.
- **Homepage Sections & Ordering**: Reorder homepage sections via drag-and-drop or Up/Down buttons, and toggle individual section visibility.
- **Hero Banner**: Eyebrow, headline, lead text, call-to-action buttons, and rich media support (Platform SVG, Custom Image, MP4 Video, YouTube, or Vimeo embed).
- **Trust & Performance Metrics**: Dynamic repeatable metric cards (Value, Label, Description).
- **About & Value Narrative**: Mission statement, capability highlights, call to action, and media options.
- **Professional Services**: Dynamic services manager with icon selection, service titles, descriptions, and links.
- **Digital Products & Storefront**: Eyebrow, heading, product limits, and category links (seamless integration with Favorite Digital).
- **Packages & Solutions**: Custom heading, package limit, and link configuration.
- **Membership Plans**: Subscription tier highlights, custom CTA, and media illustration.
- **Latest Articles**: Editorial section title and heading visibility toggle.
- **Call to Action**: High-conversion bottom banner with dual action buttons.
- **Footer**: Brand summary and copyright notice.
- **Design Tokens & Colors**: Full color palette customization for both Light Mode and Dark Mode with real-time preview.
- **Typography & Layout**: Base font sizing, heading weights, line heights, container widths, border radii, and sidebar orientation.
- **Custom CSS**: Live-injected sanitized custom CSS editor.

All settings maintain a **1:1 default state** matching the initial design when unconfigured. Administrators can reset to default settings at any time without impacting posts, menus, or installed plugins.

## Customization contracts

The theme supports the Core Customizer, menus, widget regions, SEO head output, base-path/subdirectory installs, comments, search, archives, and all standard Favorite CMS content templates. Extensions may customize homepage data through:

- `favorite_web_official_services`
- `favorite_web_platform_links`
- `favorite_web_home_products`
- `theme_customizer_view` (Core filter for theme customizer delegation)

## Source

Theme version: `1.1.1`

Official website: <https://www.favoriteweb.net/>

