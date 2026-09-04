# Favorite CMS Assets — Repository Guide

## 1. Executive Summary & Purpose

The **Favorite-CMS-Assets** repository is the authoritative, centralized asset and documentation repository for the entire **Favorite CMS Ecosystem**.

Its purpose is to:
- Provide a single, canonical location for official brand assets, icons, design foundations, screenshots, demo media, theme assets, and plugin assets.
- Ensure that visual assets are maintained independently of application runtime code.
- Serve as the documentation archive for architectural specifications, visual standards, and ecosystem conventions.
- Enable any developer or AI on any computer in the world to clone the repository, understand its structure immediately, locate needed assets, and extend it cleanly.

---

## 2. Ecosystem Relationships & Boundaries

Favorite CMS is an interconnected suite of open-source and modular commerce solutions:

```text
Favorite CMS Ecosystem
├── Favorite-CMS-Universal (Core runtime application: CMS kernel, router, MVC, database, admin dashboard)
├── Favorite-CMS-Assets (Central asset & documentation repository — THIS REPOSITORY)
│   ├── Brand identity (logos, favicons, brand guidelines)
│   ├── Shared icons (57 foundational SVG icons)
│   ├── Design foundation (tokens, typography, components, a11y)
│   ├── Screenshots (feature showcases, release media)
│   ├── Demo content (safe sample media, dummy placeholders)
│   ├── Theme assets (visual assets for Favorite Web and future themes)
│   └── Plugin assets (visual assets for Favorite Pay, Digital, Shop, and future plugins)
└── Individual Product Repositories
    ├── Favorite-Web (Flagship presentation theme source code)
    ├── Favorite Pay (Payment orchestrator, wallet, gateways runtime code)
    ├── Favorite Digital (Digital downloads & memberships runtime code)
    └── Favorite Shop (Physical commerce & inventory runtime code)
```

### What Belongs in THIS Repository (`Favorite-CMS-Assets`)
- Brand identity files (SVGs, PNGs, brand marks, logotypes, wordmarks).
- Favicons and app touch icons.
- Central SVG icon system (navigation, actions, commerce, status, content, users).
- Design system documentation and tokens (colors, typography, components, spacing).
- Screenshots for documentation, marketing, GitHub showcases, and release announcements.
- Royalty-free placeholder media and demo content for test sandboxes.
- Visual assets for official themes (`theme-assets/<theme-name>/`).
- Visual assets for official plugins (`plugin-assets/<plugin-name>/`).
- Ecosystem documentation, architectural specifications, and contributor guides (`docs/`).
- Internal design drafts, exploratory sketches, and work-in-progress materials (`internal/`).

### What Explicitly Does NOT Belong Here
- **Application Source Code**: No PHP classes, controllers, models, or views.
- **Runtime Packages**: No Composer dependencies (`vendor/`), npm dependencies (`node_modules/`), or framework libraries.
- **Build Configurations**: No Webpack, Vite, Sass compilers, or package manifest build chains.
- **Compiled Archives**: No production release distribution `.zip` or `.tar.gz` packages (releases belong on GitHub Releases in their respective product repositories).
- **Secrets & Private Data**: No API keys, credentials, private keys, database dumps, passwords, or customer PII.

---

## 3. Directory Responsibilities & Inventory

| Directory | Primary Role | Permitted File Types | Reusability |
| :--- | :--- | :--- | :--- |
| [`brand/`](../brand/README.md) | Official Favorite CMS brand identity, logotypes, favicons, and guidelines. | `.svg`, `.png`, `.ico`, `.md` | Universal across all ecosystem products and official communications. |
| [`icons/`](../icons/README.md) | Core 57-symbol vector icon system on a 24x24 grid using `currentColor`. | `.svg`, `.md` | Shared across Core CMS, admin UI, themes, and plugins. |
| [`design/`](../design/README.md) | Visual design foundations: tokens, typography, UI principles, components, and a11y rules. | `.md`, `.json`, `.svg` | System-wide design specification reference. |
| [`screenshots/`](../screenshots/README.md) | Product captures, admin workflow demonstrations, and release showcases. | `.png`, `.webp`, `.jpg`, `.md` | Documentation, marketing, GitHub repository READMEs. |
| [`demo-content/`](../demo-content/README.md) | Safe sample media, avatar placeholders, and test content for onboarding. | `.jpg`, `.png`, `.webp`, `.svg`, `.md` | Local development fixtures, theme demo sites, sandbox testing. |
| [`theme-assets/`](../theme-assets/README.md) | Visual assets specifically created for Favorite CMS themes (e.g. `favorite-web`). | `.svg`, `.png`, `.webp`, `.jpg`, `.md` | Theme-specific presentation layer. |
| [`plugin-assets/`](../plugin-assets/README.md) | Visual assets specifically created for Favorite CMS plugins (`favorite-pay`, `favorite-digital`, `favorite-shop`, future plugins). | `.svg`, `.png`, `.webp`, `.jpg`, `.md` | Plugin-specific documentation and marketplace listings. |
| [`internal/`](../internal/README.md) | Internal explorations, concept drafts, and unfinalized sketches. | `.ai`, `.svg`, `.png`, `.md` | Internal development team only. |
| [`docs/`](./README.md) | Repository guides, asset conventions, Git workflows, AI instructions, and architecture blueprints. | `.md` | Maintainers, contributors, AI assistants, ecosystem developers. |

---

## 4. Scalable Plugin & Theme Asset Architecture

### Plugin Asset Pattern
Every plugin in the ecosystem receives a dedicated folder under `plugin-assets/`:
```text
plugin-assets/
├── README.md
├── favorite-pay/
├── favorite-digital/
├── favorite-shop/
└── <future-plugin-name>/
    ├── README.md              # Required: Purpose, asset inventory, conventions
    ├── icons/                 # Domain-specific 24×24 SVG icons (optional)
    ├── illustrations/         # Domain spot illustrations / empty states (optional)
    └── placeholders/          # Media guidelines and dimensions (optional)
```

### Theme Asset Pattern
Every theme in the ecosystem receives a dedicated folder under `theme-assets/`:
```text
theme-assets/
├── README.md
├── favorite-web/
└── <future-theme-name>/
    ├── README.md              # Required: Visual direction, palette, structure
    ├── backgrounds/           # SVG hero/section backdrops (optional)
    ├── decorative/            # Accent vectors, glows, grids (optional)
    ├── illustrations/         # Theme-specific section art (optional)
    └── placeholders/          # Theme image sizing guidelines (optional)
```

**Rule**: Do NOT create empty placeholder folders for hypothetical plugins or themes. Only create the directory when assets are actively being introduced for that product.

---

## 5. Summary Checklist for Contributors

Before committing to this repository, verify:
- [ ] Asset is placed in the correct directory.
- [ ] File name uses lowercase kebab-case (`example-asset-name.svg`).
- [ ] No application code or dependencies are included.
- [ ] No credentials, API keys, or private data are present.
- [ ] Master/source vector files are preserved.
- [ ] Relevant README file is updated if a new asset or category was added.
- [ ] `git diff --check` passes with zero errors.
