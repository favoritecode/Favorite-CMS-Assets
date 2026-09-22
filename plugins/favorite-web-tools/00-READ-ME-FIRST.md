# Favorite Web Tools — Read Me First

**Plugin:** Favorite Web Tools  
**Slug:** `favorite-web-tools`  
**Platform:** Favorite CMS Universal

---

## 1. Purpose of this file

This file is the implementation entry point for the specification set in this package. It does not replace the detailed documents. It locks the cross-document rules that must remain consistent while the plugin is implemented.

If a detailed document conflicts with this file, stop and reconcile the conflict against the actual Favorite CMS repository before coding.

---

## 2. Canonical constants

### Access modes

Use exactly:

```text
FREE
LOGIN_REQUIRED
MEMBERSHIP_REQUIRED
```

Do not introduce alternate runtime names such as `PUBLIC`, `LOGIN`, `PREMIUM`, credits, quotas, token balances, or daily/monthly usage limits unless the specification is explicitly revised.

### Persistent tool statuses

Use the persisted lifecycle states defined by the Tool Registry/Lifecycle documents. `DRAFT`, `ACTIVE`, and `DISABLED` are the canonical public configuration states. Words such as “ready” or “testing” may describe validation phases but must not silently become new persisted statuses.

### Engine identifiers

Use the engine identifiers defined by the Engine specifications:

```text
HTML
CSS
JAVASCRIPT
PHP
PYTHON_API
```

`PHP` means a registered, controlled PHP handler. It never means executing arbitrary stored/user-provided PHP source. `JAVASCRIPT` does not grant arbitrary JavaScript execution in the CMS page context.

---

## 3. Active theme ownership — non-negotiable

Favorite Web Tools is a plugin, not a theme.

For every human-facing public page:

```text
Active Favorite CMS Theme
    ├── Header / navigation
    ├── Favorite Web Tools content
    └── Footer
```

The plugin must provide content, not a replacement site shell.

Therefore:

- Do not hardcode a specific theme header/footer path.
- Do not copy a theme's header/footer markup into the plugin.
- Do not globally register plugin template paths in a way that can override another active theme.
- Do not solve duplicate shells by regex-stripping `<header>`, `<footer>`, `<html>`, or `<body>` from rendered output.
- Use the canonical Favorite CMS active-theme renderer discovered from the actual repository.
- Machine endpoints (execution APIs, webhooks/callbacks, health endpoints, JSON endpoints) remain machine responses and do not receive the public theme shell.

---

## 4. CMS boundary types

When integrating with Favorite CMS core APIs, pass the native data types the core expects.

Examples:

- Menu titles: plain strings.
- Menu/submenu collections: native arrays when the CMS uses array functions such as `array_column()`.
- Route parameters: scalar values expected by the router.
- View variables: plain serializable values unless a core API explicitly supports custom objects.

Do not depend on `Stringable`, `ArrayAccess`, stack-trace line numbers, or custom collection behavior at a CMS boundary unless the actual core contract explicitly supports it.

---

## 5. Security execution boundary

- No arbitrary PHP execution.
- No arbitrary shell/system command execution.
- No direct arbitrary Python execution inside Favorite CMS.
- Python tools execute only through the configured external HTTP/API service contract.
- Browser JavaScript must not receive server secrets.
- If custom JavaScript execution is ever introduced, it must be explicitly isolated from the parent CMS DOM/session/storage and reviewed as a separate security feature.
- File uploads, remote URLs, redirects, and Python service targets must use the validation/SSRF controls defined in the security documents.

---

## 6. Source of truth and implementation discipline

Before implementing a feature, inspect the actual Favorite CMS repository for:

- plugin bootstrap/lifecycle conventions;
- router/request/response contracts;
- admin menu data structures;
- active-theme renderer;
- authentication/current-user representation;
- membership integration mechanism;
- migration conventions;
- plugin asset loading;
- cache behavior.

Do not invent a parallel framework when Favorite CMS already provides the capability.

---

## 7. Release acceptance rule

Tests are necessary but are not sufficient for a release.

A release candidate must pass all of the following:

1. Focused unit/integration tests.
2. Full Favorite Web Tools test suite.
3. Clean install/upgrade from the exact ZIP artifact that will be released.
4. Real-browser verification of critical admin and public flows.
5. Active-theme switch verification on public pages.
6. Access checks for `FREE`, `LOGIN_REQUIRED`, and `MEMBERSHIP_REQUIRED`.
7. No Favorite CMS core modifications unless the project scope explicitly authorizes them.
8. Package root validation: `favorite-web-tools/` with no double nesting.
9. Final ZIP size, file count, and SHA-256 recorded before release.
10. The released artifact must be byte-for-byte the verified artifact. If the hash changes, verification must be repeated.

---

## 8. Document reading order

Recommended order:

1. `00-READ-ME-FIRST.md`
2. `01-PROJECT-OVERVIEW.md`
3. `02-ARCHITECTURE.md`
4. `04-ACCESS-CONTROL.md`
5. `05-TOOL-REGISTRY-AND-DATA-MODEL.md`
6. `06-ENGINE-SYSTEM.md`
7. `16-TOOL-SECURITY-AND-SANDBOX.md`
8. `22-TOOL-THEME-INTEGRATION-AND-RESPONSIVE-UI.md`
9. `31-TESTING-AND-QUALITY-ASSURANCE.md`
10. `32-INSTALLATION-MIGRATION-AND-DEPLOYMENT.md`
11. `33-AI-AGENT-IMPLEMENTATION-MASTER-PLAN.md`
12. Remaining subsystem documents as required by the implementation phase.

See `34-DOCUMENT-REVIEW-CORRECTIONS-AND-GUARDRAILS.md` for the review changes applied to this package.
