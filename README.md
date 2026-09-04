# Favorite CMS Assets

Private asset repository for the Favorite CMS ecosystem.

## Overview

- **Private Repository**: This repository is private and dedicated solely to Favorite CMS brand, design, and visual resources.
- **Purpose**: Centralized storage for brand assets, design specifications, icons, screenshots, demo content, theme assets, and plugin visuals.
- **Independent Project**: Favorite CMS Core is a separate public/open-source repository. This repository is **NOT** a runtime dependency of Favorite CMS Core.
- **No Source Code**: Do not place application source code, package managers, or runtime dependencies here (no PHP, Node.js, JavaScript apps, Composer, or build tools).
- **Security & Privacy**: Never store secrets, passwords, API keys, access tokens, customer data, private hosting credentials, or database dumps here.

## Directory Structure

```text
Favorite-CMS-Assets/
├── README.md
├── brand/
│   ├── README.md
│   ├── logo/
│   ├── favicon/
│   └── guidelines/
├── icons/
│   └── README.md
├── design/
│   └── README.md
├── screenshots/
│   └── README.md
├── demo-content/
│   └── README.md
├── theme-assets/
│   └── README.md
├── plugin-assets/
│   ├── README.md
│   ├── favorite-pay/
│   │   └── README.md
│   ├── favorite-digital/
│   │   └── README.md
│   └── favorite-shop/
│       └── README.md
└── internal/
    └── README.md
```

Each subdirectory contains its own `README.md` detailing asset scope, permissible files, reusability, and conventions.

## Asset Conventions

- **File Naming**:
  - Use lowercase letters where practical.
  - Use hyphens (`-`) for multi-word file names (kebab-case).
  - Use descriptive, meaningful names (e.g., `favorite-cms-logo-horizontal.svg`, `dashboard-overview-dark.png`).
  - Avoid ambiguous or version-tagged names like `final-final2.png`, `new-logo.png`, or `test123.svg`.
- **Source Preservation**:
  - Preserve original source files (e.g., vector SVGs or master design files) whenever appropriate.
  - Do not overwrite source assets merely to create optimized or web-ready derivatives.
  - If source and derived variants are needed in the future, follow a simple pairing convention when assets are introduced.