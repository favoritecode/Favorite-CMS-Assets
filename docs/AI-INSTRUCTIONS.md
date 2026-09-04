# Favorite CMS Assets — AI Agent Operational Instructions

This document defines mandatory operational instructions for Artificial Intelligence assistants, autonomous agents, and paired coding AI (such as Antigravity, Google DeepMind AI, Claude, etc.) working within the **Favorite-CMS-Assets** repository.

---

## 1. Prime Directives

1. **Preserve Existing Structure**: NEVER redesign the repository, rename top-level directories, or introduce competing folder taxonomies (`assets/`, `resources/`, `ecosystem-assets/`, etc.). The existing 9 top-level directories (`brand/`, `demo-content/`, `design/`, `docs/`, `icons/`, `internal/`, `plugin-assets/`, `screenshots/`, `theme-assets/`) are permanent.
2. **Inspect First, Do NOT Assume**: Always inspect the existing tree and relevant `README.md` files BEFORE creating, modifying, or moving any asset.
3. **Search Before Creating**: Do NOT assume an asset is missing simply because it was not mentioned in the immediate user prompt. Search the repository with `find_by_name` or `grep_search` first.
4. **Never Recreate Valid Assets**: If an asset already exists (e.g. an icon in `icons/` or a logo in `brand/`), reuse it. Do not generate duplicate variants.
5. **No Code in the Asset Repository**: Never add PHP application logic, database schemas, composer packages, npm scripts, or runtime backend dependencies. This repository is strictly for visual assets and ecosystem documentation.
6. **No Fake Assets**: Do NOT generate mock images, fake screenshots, or empty placeholder folders for hypothetical plugins or themes. Only document scalable conventions.
7. **Zero Secrets**: Under no circumstances should an AI commit API keys, private keys, passwords, database credentials, or real customer data to this repository.
8. **Treat GitHub as Canonical Truth**: Understand that local clones are temporary working copies. All necessary instructions, context, and documentation must exist inside the repository itself so that any agent on any PC can succeed without conversational history.

---

## 2. Step-by-Step AI Agent Action Protocol

When assigned a task in this repository, follow this execution sequence:

```text
1. Inspect Working Tree
   ├── Run `git status` to verify clean state.
   └── Check `git branch` and remote connectivity.
2. Survey Relevant Directories
   ├── Read root `README.md`.
   ├── Read directory-specific `README.md` in the target folder.
   └── Search for existing assets to prevent duplicate work.
3. Apply Minimal, High-Precision Changes
   ├── Place assets strictly in their defined directories.
   ├── Follow kebab-case lowercase naming.
   └── Update corresponding documentation.
4. Verify Quality
   ├── Run `git diff --check` to ensure zero whitespace errors.
   ├── Run `git status` to inspect modified/untracked files.
   └── Review `git diff` to confirm only intended changes are present.
5. Commit and Push
   ├── Stage only intended files.
   ├── Write a clear, semantic commit message.
   └── Push to `origin/main`.
```

---

## 3. Directory Routing Rules for AI

When a user asks to place or find an asset, route strictly according to this table:

| Asset Type | Correct Target Directory | Prohibited Location |
| :--- | :--- | :--- |
| Favorite CMS Core Logos & Favicons | `brand/logo/`, `brand/favicon/` | `plugin-assets/`, `theme-assets/` |
| Brand Colors, Guidelines, Spacing | `brand/guidelines/`, `design/` | `internal/` (unless early draft) |
| Universal System Vector Icons | `icons/<category>/` | `plugin-assets/` |
| UI Specifications, Design Tokens | `design/` | `docs/` |
| Screenshots of CMS Features / Admin | `screenshots/` | `demo-content/` |
| Safe Sample Media for Demos | `demo-content/` | `screenshots/` |
| Theme Visuals, Hero Vectors, Textures | `theme-assets/<theme-name>/` | `design/`, `brand/` |
| Plugin Visuals, Domain Icons, Badges | `plugin-assets/<plugin-name>/` | `icons/`, `brand/` |
| Internal Drafts, Concept Artwork | `internal/` | `brand/`, `docs/` |
| Ecosystem Specs, Guides, Workflows | `docs/` | Root directory |

---

## 4. Self-Correction & Verification Checklist

Before reporting completion to the user, the AI must verify:
- [ ] Did I preserve the established top-level folder structure?
- [ ] Did I check that no runtime application code was introduced?
- [ ] Did I run `git diff --check` and verify zero whitespace errors?
- [ ] Does the documentation accurately describe what is actually in the repository?
- [ ] Did I commit and push cleanly to `origin/main`?
