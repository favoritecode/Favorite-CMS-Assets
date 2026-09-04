# Favorite CMS — Asset Naming Conventions

Standardized file naming guidelines to ensure clarity, discoverability, and clean organization across all digital assets in the Favorite CMS ecosystem.

---

## 1. General Principles

- **Lowercase Only**: Always use lowercase characters. Avoid CamelCase, PascalCase, or mixed casing (`favorite-logo.svg` not `FavoriteLogo.SVG`).
- **Hyphen Separation (kebab-case)**: Use hyphens (`-`) to separate words. Never use spaces, underscores (`_`), or special symbols.
- **Descriptive & Self-Explaining**: Names must describe what the asset is, its context, and its specific variant.
- **No Ambiguous Versioning**: Never use names like `final`, `v2`, `new`, `latest`, or `test` (e.g., `logo-final2.png` is forbidden). Git handles version history.
- **Sensible Dimensions/Context**: When assets are formatted for specific fixed slots (e.g., marketplace banners or favicons), append the dimensions (e.g., `-772x250.png` or `-32x32.png`).

---

## 2. Standard Naming Pattern

```text
[category]-[context]-[descriptor]-[optional-variant/size].[ext]
```

---

## 3. Concrete Asset Examples

### Logos & Marks
- `favorite-cms-logo.svg` (Primary stacked logo)
- `favorite-cms-logo-horizontal.svg` (Horizontal header lockup)
- `favorite-cms-logo-mark.svg` (Standalone mark / symbol)
- `favorite-cms-wordmark.svg` (Standalone typography)
- `favorite-pay-logo-horizontal.svg` (Future ecosystem product horizontal logo)
- `favorite-shop-logo-mark.svg` (Future ecosystem product standalone mark)

### Favicons & Touch Icons
- `favicon.svg` (Scalable vector favicon)
- `favicon.png` (Master 512×512 raster favicon)
- `favicon-32x32.png` (Standard desktop browser favicon)
- `favicon-16x16.png` (Compact browser tab favicon)
- `apple-touch-icon.png` (iOS home screen icon)

### UI Icons
- `icon-dashboard.svg`
- `icon-settings.svg`
- `icon-user.svg`
- `icon-chevron-down.svg`
- `icon-credit-card.svg`
- `icon-shopping-cart.svg`
- `icon-cloud-download.svg`

### Illustrations & Empty States
- `illustration-empty-plugins.svg`
- `illustration-setup-complete.svg`
- `illustration-no-results.svg`
- `illustration-server-error.svg`

### Screenshots
- `screenshot-dashboard-overview-dark.png`
- `screenshot-theme-customizer-light.png`
- `screenshot-plugin-manager-overview.png`
- `screenshot-media-library-grid.png`

### Product Graphics & Directory Media
- `banner-marketplace-772x250.png` (Plugin directory header banner)
- `badge-verified-developer.svg`
- `badge-compatible-cms.svg`
- `card-plugin-thumbnail-256x256.png`

### Theme Assets
- `pattern-subtle-grid.svg`
- `pattern-dots-accent.svg`
- `default-hero-background.jpg`
- `default-author-avatar.png`

### Plugin-Specific Assets
- `favorite-pay-badge-stripe.svg`
- `favorite-pay-card-flow.svg`
- `favorite-digital-file-zip.svg`
- `favorite-shop-checkout-diagram.svg`

---

## 4. Source vs. Derived Assets

- **Source Preservation**: Keep the original editable vector file (`.svg`) or master design source file intact.
- **Derivative Naming**: Raster derivative exports must share the exact basename of the source asset with their respective extension and dimension suffix (e.g., `favorite-cms-logo-mark.svg` -> `favorite-cms-logo-mark.png`).
- **Never Overwrite Source**: Do not replace high-resolution master assets with compressed or reduced web thumbnails.
