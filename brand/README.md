# Brand Assets

Official brand identity resources, master vector marks, logotypes, favicons, and visual guidelines for the Favorite CMS ecosystem.

---

## 1. Directory Structure

```text
brand/
├── README.md                  # This brand foundation guide
├── logo/                      # Master vector (SVG) and high-res raster (PNG) logos
│   ├── favorite-cms-logo.svg / .png             # Primary vertical/stacked logo
│   ├── favorite-cms-logo-horizontal.svg / .png  # Primary horizontal lockup
│   ├── favorite-cms-logo-mark.svg / .png        # Standalone geometric brand mark
│   └── favorite-cms-wordmark.svg                # Standalone typographic wordmark
├── favicon/                   # Multi-resolution browser favicons and touch icons
│   ├── favicon.svg / .png                       # Master vector and 512×512 favicon
│   ├── favicon-32x32.png / 16x16.png            # Standard desktop browser favicons
│   └── apple-touch-icon.png                     # iOS / mobile web touch icon (180×180)
└── guidelines/                # Brand identity specifications
    ├── brand-overview.md      # Core brand mission, voice, and design principles
    ├── brand-colors.md        # Official color palette (HEX, RGB, HSL) and usage rules
    └── logo-usage.md          # Clear space, minimum sizing, and incorrect usage rules
```

---

## 2. Guidelines & Policies

- **What Belongs Here**: Official Favorite CMS master marks, logotypes, wordmarks, favicons, brand style guides, and color definitions.
- **What Does NOT Belong Here**: Plugin-specific icons (use `plugin-assets/`), theme-specific artwork (use `theme-assets/`), third-party logos, or application source code.
- **Ecosystem Relationship**: Favorite CMS brand identity is the parent visual anchor for the entire ecosystem. Official plugins (`Favorite Pay`, `Favorite Digital`, `Favorite Shop`) and themes (`Favorite Web`) derive their visual cues directly from these master brand standards.
- **Master Vector Preservation**: Always preserve editable vector SVG source files. Never overwrite master vectors with raster exports or heavily altered derivatives.
- **Naming Convention**: Lowercase with hyphens and clear variant descriptions (e.g. `favorite-cms-logo-horizontal.svg`, `favorite-cms-logo-mark.png`).
