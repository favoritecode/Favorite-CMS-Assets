# Plugin Assets

Visual assets, marketplace media, and feature graphics for official and ecosystem plugins within the Favorite CMS ecosystem.

---

## 1. Purpose & Scope

This directory serves as the centralized visual foundation for all Favorite CMS plugins.

- **Visual Assets & Design Specifications Only**: Stores SVG icons, feature illustrations, promotional badges, payment brand guidelines, and placeholder specifications.
- **Zero Runtime Code**: This directory contains **NO** PHP backend code, controller logic, database migrations, Composer dependencies, or runtime `.zip` distribution packages. Plugin application code resides exclusively in its respective product source repository (e.g., Favorite Pay, Favorite Digital, Favorite Shop, or future plugin repositories).
- **Zero Secrets**: Never store merchant credentials, API keys, test credentials, or private access tokens here.

### Plugin Asset ↔ Source Code Mapping

| Plugin Asset Directory | Target Ecosystem Role | Source-Code Implementation |
| :--- | :--- | :--- |
| `plugin-assets/favorite-pay/` | Financial orchestrator, payment gateways, wallet ledger | Favorite Pay plugin source repository |
| `plugin-assets/favorite-digital/` | Digital products, file delivery, licenses, memberships | Favorite Digital plugin source repository |
| `plugin-assets/favorite-shop/` | Physical commerce, variants, inventory, shipping, COD | Favorite Shop plugin source repository |
| `plugin-assets/<future-plugin>/` | Future ecosystem or community plugins | Respective plugin source repository |

---

## 2. Directory Structure

```text
plugin-assets/
├── README.md                  # This overarching plugin assets guide
├── favorite-pay/              # Financial layer: payments, wallets, transactions, gateways
│   ├── README.md              # Favorite Pay visual foundation guide
│   ├── icons/                 # Domain-specific payment & wallet SVG icons (24×24)
│   ├── illustrations/         # Conceptual spot artwork (400×300 SVG)
│   ├── payment-methods/       # Gateway branding and logo ingestion guidelines
│   └── placeholders/          # Media sizing specs and screenshot rules
├── favorite-digital/          # Digital commerce: downloads, memberships, versioning
│   ├── README.md              # Favorite Digital visual foundation guide
│   ├── icons/                 # Digital library, download access, and membership icons
│   ├── illustrations/         # Digital delivery and membership spot illustrations
│   └── placeholders/          # Product thumbnail dimensions and guidelines
└── favorite-shop/             # Physical retail: products, variants, inventory, shipping
    ├── README.md              # Favorite Shop visual foundation guide
    ├── icons/                 # Inventory, shipping zones, COD, and order icons
    ├── illustrations/         # Order fulfillment, stock, and delivery illustrations
    └── placeholders/          # Product gallery and variant photo sizing guidelines
```

---

## 3. Scalable Pattern for Future Plugins

The repository is designed to scale naturally as new plugins are introduced into the Favorite CMS ecosystem (e.g. `favorite-forms`, `favorite-seo`, `favorite-analytics`, etc.).

### Standard Structure for a Future Plugin
When assets are created for a new plugin, create:
```text
plugin-assets/<future-plugin-name>/
├── README.md                  # Required: Plugin role, architecture, asset inventory
├── icons/                     # Optional: Domain-specific 24×24 vector icons
├── illustrations/             # Optional: Empty state / feature spot illustrations
└── placeholders/              # Optional: Screenshot and media sizing specifications
```

### Requirements for the Plugin `README.md`
Every plugin asset directory must provide a comprehensive `README.md` containing:
1. **Plugin Name & Purpose**: What the plugin does and its role in the ecosystem.
2. **Ecosystem Architecture**: How it interfaces with Favorite CMS Core, Favorite Pay, and the presentation theme.
3. **Asset Inventory**: Table or list of all visual assets stored in the directory.
4. **Brand Alignment**: Palette tokens used (referencing `design/design-tokens.md`).
5. **Permissible Content**: Explicit statement that only visual assets are stored here (no PHP, no secrets).
6. **Maintenance & Sizing Rules**: Specific pixel/vector dimensions for icons, illustrations, and banners.

### Important Guidelines
- **Do NOT create empty placeholder folders**: Only create `plugin-assets/<future-plugin>/` when real assets are actively being introduced for that plugin.
- **Do NOT duplicate common icons**: Common navigation, status, or content icons belong in the root `icons/` directory. Only domain-specific symbols belong inside the plugin folder.
- **Preserve master vector sources**: Always retain original SVG files.
