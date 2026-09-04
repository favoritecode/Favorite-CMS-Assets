# Favorite CMS Assets — Documentation Index

Welcome to the central documentation directory for the **Favorite-CMS-Assets** repository.

This directory houses the foundational standards, architectural blueprints, asset conventions, workflows, and operational instructions governing the entire visual and design resource repository for the Favorite CMS ecosystem.

---

## Documentation Registry

| Document | Description | Target Audience |
| :--- | :--- | :--- |
| [**REPOSITORY-GUIDE.md**](./REPOSITORY-GUIDE.md) | Comprehensive overview of the asset repository architecture, ecosystem relationship, directory responsibilities, and strict separation between assets and application code. | Developers, Designers, System Architects |
| [**ASSET-CONVENTIONS.md**](./ASSET-CONVENTIONS.md) | Standard rules for asset placement, kebab-case naming, vector vs. raster formats, source preservation, derivative management, future plugin assets, and future theme assets. | Designers, Developers, AI Agents |
| [**GIT-WORKFLOW.md**](./GIT-WORKFLOW.md) | Git operations, branching, commit conventions, new PC onboarding, multi-PC synchronization, and diff verification rules. | Developers, Maintainers, AI Agents |
| [**AI-INSTRUCTIONS.md**](./AI-INSTRUCTIONS.md) | Explicit operational guardrails and guidelines for AI coding agents (Antigravity, DeepMind AI, etc.) working inside this repository. | AI Agents, Developers Pairing with AI |
| [**FAVORITE-PAY-ARCHITECTURE.md**](./FAVORITE-PAY-ARCHITECTURE.md) | Technical architecture reference and implementation blueprint for Favorite Pay. Serves as ecosystem architectural documentation; does not contain application runtime code or replace the Favorite Pay source repository. | Core Developers, Plugin Architects |

---

## Core Operational Principles

1. **Central Asset + Documentation Hub**: This repository is the canonical storehouse for brand identity, icons, design tokens, presentation media, and ecosystem documentation across all Favorite CMS products.
2. **Strict Code Separation**: This repository stores **visual, design, and reference assets only**. Zero PHP backend logic, JavaScript application code, Composer/npm runtime dependencies, or compiled `.zip` archives belong here.
3. **GitHub as Canonical Truth**: The remote GitHub repository (`https://github.com/favoritecode/Favorite-CMS-Assets`) is the authoritative source of truth. Local clones are temporary working copies.
4. **Zero Secrets**: Passwords, API keys, private keys, payment credentials, webhook secrets, customer PII, and `.env` files containing sensitive values are strictly forbidden.
5. **Preservation of Existing Assets**: Never delete, move, or rename existing assets for cosmetic reasons. New assets must conform to standard conventions while respecting existing filenames.
6. **Internal / Private Repository Role**: Although currently hosted on GitHub and temporarily public for development and inspection, this repository is intended as a private, internal asset resource for the project team. Security and data privacy rules apply unconditionally.
