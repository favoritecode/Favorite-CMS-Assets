# Internal Assets

Internal project resources, design drafts, concept artwork, exploratory sketches, and architectural reference diagrams.

---

## 1. Purpose & Scope

This directory provides a dedicated staging area for works-in-progress, early visual explorations, and internal team design artifacts that are not yet ready for public distribution or direct inclusion in production themes or plugins.

---

## 2. Permissible & Prohibited Content

### Permissible Content
- Early brand identity explorations and logotype concepts.
- Vector source files (`.ai`, `.svg`, `.sketch`, `.fig` exports) for upcoming design milestones.
- Architectural reference diagrams (PNG, SVG) illustrating ecosystem workflows.
- Internal slide decks or presentation graphics.
- Design tokens under active consideration.

### Strictly Prohibited Content
- **Passwords, secrets, or API keys** of any kind.
- **Live server or hosting credentials**.
- **Private customer information or PII**.
- **Production database dumps or customer backups**.
- **Runtime application code, scripts, or executables**.

---

## 3. Asset Graduation Policy

When an internal asset matures and is approved for production use:
1. Move the finalized asset to its canonical destination:
   - Official logos → `brand/logo/`
   - Universal icons → `icons/<category>/`
   - Plugin graphics → `plugin-assets/<plugin-name>/`
   - Theme graphics → `theme-assets/<theme-name>/`
   - System documentation → `docs/`
2. Update the corresponding directory's `README.md`.
3. Remove or archive the intermediate draft in `internal/`.
