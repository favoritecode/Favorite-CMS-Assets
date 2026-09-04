# Theme Assets

Visual design assets, SVG textures, illustrations, and media guidelines for official and ecosystem themes within Favorite CMS.

---

## Purpose & Scope

This directory houses the foundational visual identity and static design components for themes across the Favorite CMS ecosystem, with `favorite-web/` serving as the flagship official theme assets foundation.

### Strict Asset vs. Code Separation

- **What Belongs Here**:
  - Raw and optimized SVG backgrounds, textures, and patterns.
  - Vector illustrations for core theme sections, feature highlights, and empty states.
  - Decorative accents, dots, and subtle glow elements.
  - Placeholder specifications and media dimension guidelines for future imagery.
- **What Does NOT Belong Here**:
  - **No Application or Theme Code**: No PHP templates, Blade/Twig engines, JavaScript modules, or CSS stylesheets.
  - **No Frameworks / Build Systems**: No Tailwind configurations, Bootstrap bundles, Sass files, or Node.js packages.
  - **No Runtime Archives**: No compiled distribution packages or `.zip` theme archives.
  - Source code for themes belongs exclusively in their respective theme repositories (e.g., `Favorite-Web`).

---

## Directory Structure

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

## Conventions & Rules

1. **Format Standards**:
   - Vector graphics must be clean, valid SVGs with explicit `viewBox` definitions and no external asset dependencies.
   - SVGs should use tokenized palette colors from `design/design-tokens.md` (`#2563EB`, `#0EA5E9`, `#0F172A`, `#F8FAFC`, etc.).
2. **Naming Conventions**:
   - Lowercase kebab-case indicating role and content (e.g., `hero-abstract.svg`, `online-shop.svg`, `section-soft.svg`).
3. **Zero Business Logic & Zero Secrets**:
   - Themes in Favorite CMS handle presentation; business logic is encapsulated in plugins (`Favorite Pay`, `Favorite Digital`, `Favorite Shop`).
   - Never commit sensitive configuration files, API keys, credentials, or private client data.
