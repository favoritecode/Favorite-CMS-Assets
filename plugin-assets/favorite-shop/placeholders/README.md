# Favorite Shop — Physical Product Media Guidelines

This directory defines storage conventions, aspect ratio standards, and image optimization guidelines for future physical product photography, catalog thumbnails, category banners, and delivery media used by **Favorite Shop**.

---

## Strict Policy: No Fake Product Photography

To maintain repository cleanliness, performance, and legal compliance:
- **Do NOT commit generic stock photos**, uncompressed warehouse snaps, or fake branded merchandise.
- **Do NOT commit copyrighted third-party product imagery** without written authorization or licensed merchant agreements.
- In local development, storefront testing, and CI pipelines, use CSS placeholder skeletons, SVG vector fallbacks, or programmatically generated canvas mocks.

---

## Standard Specifications & Aspect Ratios

When authentic merchant product photography or authorized demo media is curated in future phases, adhere strictly to these standardized aspect ratios:

| Media Type | Aspect Ratio | Standard Dimensions | Preferred Format | Storefront Usage |
|---|---|---|---|---|
| **Product Gallery Main** | `1:1` | 1000×1000, 800×800 | WebP, Progressive JPEG | Primary product view, zoom modal, gallery slide |
| **Catalog Thumbnail** | `1:1` or `4:3` | 600×600, 800×600 | WebP, Progressive JPEG | Shop catalog grid, category listing, search feed |
| **Category Hero Banner** | `16:9` | 1440×810, 1280×720 | WebP, Progressive JPEG | Category landing pages, seasonal promotional banners |
| **Cart & Order Thumbnail** | `1:1` | 160×160, 120×120 | WebP, Progressive JPEG | Cart drawer, mini-cart, checkout order summary |
| **Delivery / Zone Graphics** | `16:9` or Scalable | Dynamic `viewBox` | SVG, WebP | Delivery zone coverage map, courier tracking badge |

---

## Recommended Optimization Standards

1. **WebP (Primary Web Format)**:
   - High visual fidelity with low byte weight.
   - Target quality: 80–85% lossy compression for photo captures.
2. **Progressive JPEG (Secondary / Fallback)**:
   - Always progressive scan, stripped of EXIF metadata, camera device tags, and color profiles.
3. **SVG (Vector graphics / Badges)**:
   - For delivery badges, stock indicators, and empty cart states.
   - Cleaned with SVGO, zero embedded raster data.
4. **Target File Budgets**:
   - Category banners (`16:9`): < 150 KB
   - Product gallery images (`1:1`): < 100 KB
   - Catalog thumbnails (`1:1`): < 50 KB
   - Mini-cart thumbnails: < 20 KB
   - Vector graphics: < 5 KB

---

## File Naming Conventions

All media files must follow lowercase kebab-case naming indicating product slug, variation attribute, and aspect ratio:

```text
[product-slug]-[variation]-[media-role]-[aspect-ratio].[ext]
```

Examples:
- `cotton-tshirt-navy-main-1x1.webp`
- `ceramic-mug-white-thumb-1x1.webp`
- `apparel-collection-banner-16x9.webp`
- `placeholder-empty-shop.svg`

---

## Intellectual Property & Data Safety

- **Merchant Compliance**: Ensure all vendor product photography conforms to merchant and distributor copyright permissions.
- **Zero Real Customer Data**: Never commit customer delivery slips, actual shipping labels containing real residential addresses, or phone numbers.
