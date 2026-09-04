# Favorite Digital — Product Media & Cover Guidelines

This directory defines conventions and storage guidelines for future production cover art, file previews, and digital product thumbnails used by **Favorite Digital**.

---

## Philosophy: No Fake Product Mockups

To keep the repository lightweight, professional, and clutter-free:
- **Do NOT commit fake product screenshots, mock e-book covers, or unverified software packaging**.
- Real digital product assets must only be added when actual, authorized downloadable products and licensed documentation exist.
- In local development and automated testing, use CSS placeholders, responsive canvas skeletons, or standardized vector fallback graphics.

---

## Standard Specifications & Aspect Ratios

When actual digital product assets are curated for production or official demos, adhere strictly to these standardized aspect ratios:

| Media Type | Aspect Ratio | Standard Dimensions | Preferred Format | Usage Context |
|---|---|---|---|---|
| **Product Cover Art** | `16:9` | 1280×720, 800×450 | WebP, Progressive JPEG | Product detail page hero, featured listings |
| **Catalog Thumbnail** | `4:3` | 800×600, 400×300 | WebP, Progressive JPEG | Digital store catalog grid, search results |
| **Square Product Tile** | `1:1` | 600×600, 300×300 | WebP, Progressive JPEG | Customer library, dashboard download list |
| **Membership Tier Badge** | `1:1` | 256×256, 128×128 | SVG, WebP | Subscription pass icon, account tier badge |
| **File Type / Extension Badge**| `1:1` | 64×64, 48×48 | SVG | ZIP, PDF, EPUB, code bundle file indicators |

---

## Recommended Optimization & Formats

1. **WebP (Primary)**:
   - High visual fidelity with low payload.
   - Recommended quality: 80–85%.
2. **Progressive JPEG (Secondary / Fallback)**:
   - Always progressive scan, stripped of camera EXIF data and color profiles.
3. **SVG (Vector graphics / Badges)**:
   - For file-type badges, license icons, and empty library states.
   - Cleaned with SVGO, zero embedded raster data.
4. **Target File Budgets**:
   - Product covers (`16:9`): < 100 KB
   - Catalog thumbnails (`4:3`): < 60 KB
   - Square tiles (`1:1`): < 40 KB
   - Vector badges: < 5 KB

---

## File Naming Conventions

All media files must follow lowercase kebab-case naming indicating product slug, asset type, and ratio:

```text
[product-slug]-[media-type]-[aspect-ratio].[ext]
```

Examples:
- `starter-kit-cover-16x9.webp`
- `cms-handbook-thumb-4x3.webp`
- `ui-components-tile-1x1.webp`
- `badge-pro-membership.svg`
- `placeholder-empty-library.svg`

---

## Intellectual Property & Licensing

- **License Compliance**: Every product asset committed must have verifiable ownership rights or a clear open-source / Creative Commons license documented.
- **Zero Confidential Files**: Never commit proprietary customer software, commercial binary executables, or unreleased private digital products to this asset repository.
