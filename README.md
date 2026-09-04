# Favorite CMS Assets

The central asset and documentation repository for the **Favorite CMS Ecosystem**.

---

## 1. Overview & Purpose

**Favorite-CMS-Assets** is the canonical, centralized home for official brand identity assets, vector icon systems, design foundations, screenshots, demo media, theme visual assets, and plugin assets across all products in the Favorite CMS ecosystem.

### Key Principles
- **Central Asset Hub**: Stores all reusable visual, graphic, and design specifications for Favorite CMS Core, themes, and plugins.
- **Visual Assets & Documentation Only**: This repository contains **zero runtime application code**. No PHP backend logic, Node.js applications, Composer packages, npm dependencies, or compiled `.zip` archives belong here. Application code lives in respective product repositories (e.g. `Favorite-CMS-Universal`, `Favorite-Pay`).
- **Canonical Source of Truth**: The remote GitHub repository (`https://github.com/favoritecode/Favorite-CMS-Assets`) is the single authoritative source of truth. Any developer or AI on any computer can clone this repository, read this documentation, and immediately work with complete clarity.
- **Zero Secrets & Absolute Privacy**: Never store passwords, API keys, merchant credentials, access tokens, private keys, customer data, or live database dumps in this repository.

---

## 2. Ecosystem Architecture

Favorite CMS is designed as a unified ecosystem of modular open-source solutions:

```text
Favorite CMS Ecosystem
│
├── Favorite CMS Universal  (Core open-source CMS runtime engine: MVC, database, routing, admin panel)
│
├── Favorite-CMS-Assets     (THIS REPOSITORY: Central design, visual assets, and specifications)
│   ├── Brand Identity      (Official logos, favicons, brand guidelines)
│   ├── Icon System         (57 foundational SVG symbols on 24×24 grid)
│   ├── Design Foundation   (Design tokens, typography, component specs, accessibility)
│   ├── Screenshots         (Showcases, feature captures, release media)
│   ├── Demo Content        (Royalty-free sample media, placeholder content)
│   ├── Theme Assets        (Flagship Favorite Web theme and future theme visual assets)
│   └── Plugin Assets       (Favorite Pay, Favorite Digital, Favorite Shop, and future plugins)
│
└── Product Repositories    (Independent runtime implementations)
    ├── Favorite Web        (Flagship theme source code)
    ├── Favorite Pay        (Shared financial layer, payment gateways, wallet ledger)
    ├── Favorite Digital    (Digital products, downloads, licensing, and memberships)
    └── Favorite Shop       (Physical commerce, multi-variant products, shipping, COD)
```

---

## 3. Directory Structure

```text
Favorite-CMS-Assets/
├── README.md                      # Primary repository entry point (THIS FILE)
├── .gitignore                     # Git ignore rules for OS, editor, and temp files
│
├── brand/                         # Official Favorite CMS brand identity
│   ├── README.md                  # Brand asset guidelines and usage rules
│   ├── logo/                      # Vector (SVG) and raster (PNG) brand marks and wordmarks
│   ├── favicon/                   # Multi-size favicons and apple touch icons
│   └── guidelines/                # Brand overview, color palettes, and logo usage rules
│
├── icons/                         # Unified vector SVG icon system (57 icons on 24×24 grid)
│   ├── README.md                  # Complete icon catalog, visual standards, and grid rules
│   ├── actions/                   # User action symbols (add, edit, delete, save, copy, etc.)
│   ├── commerce/                  # Commerce symbols (cart, order, money, membership, etc.)
│   ├── communication/             # Messaging, notifications, support
│   ├── content/                   # Publishing symbols (post, page, media, category, tag)
│   ├── navigation/                # Wayfinding symbols (dashboard, menu, search, settings, etc.)
│   ├── status/                    # State indicators (success, error, warning, locked, etc.)
│   └── users/                     # Identity symbols (user, users, account, role, moderator)
│
├── design/                        # Visual design foundations and UI specifications
│   ├── README.md                  # Design foundation index
│   ├── design-tokens.md           # Master color palettes, spacing scales, border radii, shadows
│   ├── typography.md              # Typeface stacks, font weights, line heights, scales
│   ├── ui-principles.md           # Core visual philosophy and interface rules
│   ├── components.md              # Minimal visual specifications for buttons, cards, tables, inputs
│   ├── accessibility.md           # WCAG AA contrast rules, keyboard navigation, focus states
│   └── asset-naming.md            # Kebab-case naming standards and pattern reference
│
├── screenshots/                   # Product captures and showcases
│   └── README.md                  # Screenshot standards, resolution guidelines, and privacy sanitization
│
├── demo-content/                  # Safe sample media and placeholder content
│   └── README.md                  # Royalty-free rules, placeholder guidelines, no PII policy
│
├── theme-assets/                  # Visual assets for Favorite CMS themes
│   ├── README.md                  # Overarching theme asset architecture and future theme pattern
│   └── favorite-web/              # Flagship Favorite Web theme visual foundation
│       ├── README.md              # Theme visual direction and structure
│       ├── backgrounds/           # SVG hero and section backdrops
│       ├── decorative/            # Reusable SVG accents (dots, glows, grids)
│       ├── illustrations/         # Section artwork and feature graphics
│       └── placeholders/          # Media sizing specs and dimensions
│
├── plugin-assets/                 # Visual assets for Favorite CMS plugins
│   ├── README.md                  # Overarching plugin asset architecture and future plugin pattern
│   ├── favorite-pay/              # Favorite Pay visual assets (icons, illustrations, payment methods)
│   ├── favorite-digital/          # Favorite Digital visual assets (icons, illustrations, placeholders)
│   └── favorite-shop/             # Favorite Shop visual assets (icons, illustrations, placeholders)
│
├── internal/                      # Internal project assets and concept explorations
│   └── README.md                  # Permissible content rules, draft guidelines, graduation policy
│
└── docs/                          # Repository documentation and ecosystem blueprints
    ├── README.md                  # Documentation registry and navigation index
    ├── REPOSITORY-GUIDE.md        # Comprehensive repository guide and ecosystem architecture
    ├── ASSET-CONVENTIONS.md       # Naming rules, format standards, source preservation, lifecycle
    ├── GIT-WORKFLOW.md            # Git operations, multi-PC portability, branch/commit hygiene
    ├── AI-INSTRUCTIONS.md         # Operational guidelines and guardrails for AI coding assistants
    └── FAVORITE-PAY-ARCHITECTURE.md # Technical blueprint for Favorite Pay payment orchestrator
```

---

## 4. Directory Responsibilities & Asset Placement

| Directory | What Belongs Here | What Does NOT Belong Here |
| :--- | :--- | :--- |
| `brand/` | Official Favorite CMS master logos, brand marks, favicons, and identity guidelines. | Plugin/theme logos, third-party marks, application code. |
| `icons/` | Universal 24×24 vector SVG icons using `currentColor`. | Multi-color illustrations, plugin-specific domain icons, icon-font files. |
| `design/` | Design tokens, typography hierarchies, component specs, accessibility standards. | CSS stylesheets, SCSS, Tailwind configs, JS runtime code. |
| `screenshots/` | High-resolution dashboard captures, workflow showcases, marketing media. | Uncropped desktop screenshots, images with real customer PII or live secrets. |
| `demo-content/` | Royalty-free placeholder images, sample avatars, dummy starter media. | Real user photos, copyrighted commercial images, customer databases. |
| `theme-assets/` | Visual presentation assets for themes (`theme-assets/<theme-name>/`). | PHP template files, HTML views, CSS stylesheets, theme zip files. |
| `plugin-assets/` | Marketplace graphics, feature badges, domain spot art (`plugin-assets/<plugin>/`). | Plugin PHP code, database migrations, Composer packages, zip archives. |
| `internal/` | Early design explorations, draft sketches, internal architecture diagrams. | Production passwords, environment secrets, customer data. |
| `docs/` | Repository documentation, asset standards, Git guides, ecosystem blueprints. | Runtime documentation for end-users of the CMS application. |

---

## 5. Scalable Patterns for Future Plugins & Themes

### Adding Future Plugins (`plugin-assets/<future-plugin>/`)
The repository is architected to scale seamlessly as new plugins are developed.
When introducing assets for a new plugin:
1. Create `plugin-assets/<future-plugin-name>/`.
2. Add a comprehensive `README.md` following the established model of `favorite-pay`, `favorite-digital`, or `favorite-shop`.
3. Organize assets into standard subdirectories as needed:
   - `icons/` for domain-specific 24×24 vector icons.
   - `illustrations/` for empty-state or feature spot illustrations.
   - `placeholders/` for banner and thumbnail specifications.
4. **Strict Rule**: Visual assets only. The plugin's runtime PHP code belongs in its own repository or `Favorite-CMS-Universal/plugins/`.

### Adding Future Themes (`theme-assets/<future-theme>/`)
When introducing assets for a new theme:
1. Create `theme-assets/<future-theme-name>/`.
2. Add a comprehensive `README.md` following the model of `favorite-web`.
3. Organize visual assets into standard subdirectories:
   - `backgrounds/` for SVG textures and backdrops.
   - `decorative/` for accent shapes and subtle glows.
   - `illustrations/` for theme-specific section art.
   - `placeholders/` for layout dimension guides.
4. **Strict Rule**: Visual assets only. Theme templates, CSS, and PHP logic belong in the theme's code repository.

---

## 6. Asset & Naming Conventions

- **Case & Delimiters**: Strictly lowercase with hyphens (kebab-case): `example-asset-name.svg`.
- **Descriptive**: Names must clearly state what the asset is (e.g. `dashboard-overview-dark.png`, `payment-confirmed.svg`).
- **No Version Suffixes**: Never use `-final`, `-v2`, `-new`, or `-latest`. Git tracks versions.
- **Preserve Existing Assets**: Do not rename existing assets merely for cosmetic reasons.
- **Source Preservation**: Always preserve original vector SVG or master source files. Do not overwrite sources to create compressed or raster derivatives.
- For complete specifications, see [**docs/ASSET-CONVENTIONS.md**](./docs/ASSET-CONVENTIONS.md).

---

## 7. New-PC Portability & Git Workflow

This repository is designed so any developer or AI on any computer can immediately clone, understand, and contribute to it.

```text
New PC
  ↓
git clone https://github.com/favoritecode/Favorite-CMS-Assets.git
  ↓
Read README.md and docs/
  ↓
Work on assets
  ↓
git diff --check
  ↓
git commit -m "docs: establish central asset conventions"
  ↓
git push origin main
```

For complete workflow instructions, see [**docs/GIT-WORKFLOW.md**](./docs/GIT-WORKFLOW.md).

---

## 8. AI Agent Instructions

AI coding assistants working in this repository must strictly adhere to the operational instructions in [**docs/AI-INSTRUCTIONS.md**](./docs/AI-INSTRUCTIONS.md):
- Inspect the repository before modifying.
- Search before creating new assets.
- Preserve the existing 9 top-level directories.
- Never add backend code, dependencies, or secrets.
- Verify diffs with `git diff --check` before committing.
- Treat GitHub as the canonical source of truth.

---

## 9. Security & Privacy Rules

This repository must **NEVER** contain:
- Passwords, secret keys, or access tokens.
- Payment gateway credentials, webhook secrets, or private certificates.
- Production database dumps or live customer data.
- Sensitive environment files (`.env`).
