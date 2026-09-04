# Favorite Web — Image Placeholders & Media Guidelines

This directory defines conventions and storage guidelines for future production imagery, photography, and demo media used by the **Favorite Web** theme.

---

## Philosophy: No Fake Stock Photography

To maintain a clean, lightweight, and professional repository:
- **Do NOT commit generic stock photos**, uncompressed camera dumps, or arbitrary placeholder images.
- Keep this directory clean until real, production-ready, or authorized demo imagery is curated.
- In development and local theme testing, rely on CSS background colors, SVG vector placeholders, or inline canvas mocks.

---

## Standard Aspect Ratios & Specifications

When real images or demo assets are added in the future, adhere strictly to these standardized aspect ratios:

| Target Context | Aspect Ratio | Recommended Resolutions | Primary Format | Usage |
|---|---|---|---|---|
| **Hero / Banner** | `16:9` | 1920×1080, 1280×720 | WebP, Progressive JPEG | Page headers, hero sections, featured banners |
| **Feature / Post Cards** | `4:3` (or `16:9`) | 800×600, 1200×900 | WebP, Progressive JPEG | Blog posts, case studies, feature teasers |
| **Product Thumbnails** | `1:1` | 800×800, 600×600 | WebP, Progressive JPEG | Shop items, digital download previews |
| **User Avatars** | `1:1` | 256×256, 128×128 | WebP, PNG | Testimonials, author bios, user profiles |
| **Vector Placeholders** | Any | Scalable `viewBox` | SVG | Default fallback graphics, wireframe placeholders |

---

## Recommended File Formats & Optimization

1. **WebP (Primary)**:
   - Modern standard for web delivery.
   - Target quality: 80–85% with lossy compression, or lossless for graphics with flat colors.
2. **Progressive JPEG (Secondary / Fallback)**:
   - Used only when WebP fallback is required.
   - Always progressive scan, stripped of EXIF metadata.
3. **SVG (Vector graphics / UI placeholders)**:
   - For wireframes, empty states, and vector illustrations.
   - Cleaned with SVGO, no inline styles or external font dependencies.
4. **Target File Sizes**:
   - Hero images: < 150 KB
   - Card / feature images: < 80 KB
   - Product thumbnails & avatars: < 40 KB

---

## File Naming Conventions

All media files must follow lowercase kebab-case naming indicating role, topic, and aspect ratio:

```text
[category]-[subject]-[aspect-ratio].[ext]
```

Examples:
- `hero-homepage-16x9.webp`
- `card-digital-assets-4x3.webp`
- `product-starter-kit-1x1.webp`
- `avatar-testimonial-01-1x1.webp`
- `placeholder-empty-cart.svg`

---

## Security & Licensing

- **Zero Secrets / PII**: Never commit images containing personal data, real credit cards, or proprietary client data.
- **License Compliance**: Any future demo image must have a clear permissive open-source license (e.g., CC0, Unsplash License, or custom owned assets) documented in its metadata.
