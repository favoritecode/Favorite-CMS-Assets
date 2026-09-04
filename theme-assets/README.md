# Theme Assets

Visual design assets, SVG textures, illustrations, and media guidelines for official and ecosystem themes within Favorite CMS.

---

## 1. Purpose & Scope

This directory houses the foundational visual identity and static design components for themes across the Favorite CMS ecosystem, with `favorite-web/` serving as the flagship official theme asset foundation.

### Strict Asset vs. Code Separation
- **What Belongs Here**:
  - Raw and optimized SVG backgrounds, textures, and patterns.
  - Vector illustrations for core theme sections, feature highlights, and empty states.
  - Decorative accents, dots, and subtle glow elements.
  - Placeholder specifications and media dimension guidelines for theme layouts.
- **What Does NOT Belong Here**:
  - **No Application or Theme Code**: No PHP templates, Blade/Twig engines, JavaScript modules, or CSS stylesheets.
  - **No Frameworks / Build Systems**: No Tailwind configurations, Bootstrap bundles, Sass files, or Node.js packages.
  - **No Runtime Archives**: No compiled distribution packages or `.zip` theme archives.
  - Source code for themes belongs exclusively in their respective theme source repositories (e.g. Favorite Web theme source repository).

### Theme Asset ↔ Source Code Mapping

| Theme Asset Directory | Target Ecosystem Role | Source-Code Implementation |
| :--- | :--- | :--- |
| `theme-assets/favorite-web/` | Flagship storefront theme & presentation layout | Favorite Web theme source repository |
| `theme-assets/<future-theme>/` | Future official or community themes | Respective theme source repository |

---

## 2. Directory Structure

```text
theme-assets/
├── README.md                  # This overarching theme assets guide
└── favorite-web/              # Official Favorite Web theme asset foundation
    ├── README.md              # Theme visual direction and documentation
    ├── backgrounds/           # SVG hero and section backdrop graphics
    ├── decorative/            # Reusable SVG accents (dots, grids, glows)
    ├── illustrations/         # Conceptual vector illustrations
    └── placeholders/          # Media sizing guidelines and placeholder specs
```

---

## 3. Scalable Pattern for Future Themes

The repository is architected to scale naturally as new themes are introduced into the Favorite CMS ecosystem (e.g. `theme-assets/<future-theme-name>/`).

### Standard Structure for a Future Theme
When visual assets are created for a new theme, create:
```text
theme-assets/<future-theme-name>/
├── README.md                  # Required: Visual direction, color palette, asset inventory
├── backgrounds/               # Optional: Hero and section SVG backdrops / textures
├── decorative/                # Optional: Subtle accent shapes, glows, grids
├── illustrations/             # Optional: Theme-specific hero or feature artwork
└── placeholders/              # Optional: Media aspect ratios, banner sizing guidelines
```

### Requirements for the Theme `README.md`
Every theme asset directory must provide a clear `README.md` documenting:
1. **Theme Identity & Purpose**: Target industry, visual tone, and audience.
2. **Palette & Typography References**: Tokens aligned with `design/design-tokens.md` and `design/typography.md`.
3. **Asset Inventory**: Description of each visual element and its intended layout location.
4. **Dimensions & Aspect Ratios**: Recommended dimensions for hero images, cards, and blog thumbnails.
5. **Strict Asset Separation**: Confirmation that zero PHP, CSS, or JS template files are stored here.

### Conventions
- **Vector First**: Backgrounds and decorative accents should be valid, lightweight SVGs with explicit `viewBox`.
- **Zero Business Logic**: Themes handle presentation; business logic is encapsulated in Core and plugins.
- **No Empty Directories**: Do NOT create empty folders for hypothetical themes until assets are actually ready.
