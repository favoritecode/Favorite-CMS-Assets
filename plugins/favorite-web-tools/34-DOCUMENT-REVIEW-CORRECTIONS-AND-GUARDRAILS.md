# 34 — Document Review, Corrections and Implementation Guardrails

## 1. Review scope

This document records corrections and hardening added after reviewing the full Favorite Web Tools specification package.

The original product intent remains unchanged. The changes below remove ambiguity, improve Markdown readability, and add implementation/release guardrails where the specification previously relied on assumptions.

---

## 2. Documentation-format corrections

The package contained many exported Markdown escapes such as escaped headings/bullets and nonstandard fenced-code `id` attributes. These were normalized so Markdown renderers display the documents correctly.

No product requirement was intentionally changed by this formatting cleanup.

---

## 3. Canonical cross-document decisions

The following values are locked unless the specification is explicitly revised:

### Access modes

```text
FREE
LOGIN_REQUIRED
MEMBERSHIP_REQUIRED
```

### Engine identifiers

```text
HTML
CSS
JAVASCRIPT
PHP
PYTHON_API
```

### Execution security

- PHP tools use registered controlled handlers only.
- JavaScript engine support does not imply arbitrary same-origin script execution.
- Python tools use the external Python API service contract.
- No generic eval/shell execution feature is permitted.

---

## 4. Active-theme rendering correction

Human-facing Favorite Web Tools pages must be rendered by the currently active Favorite CMS theme.

The plugin must not own or generate the site's global header/footer and must not globally pollute the CMS template-resolution stack with plugin/theme paths.

Correct model:

```text
CMS active-theme renderer
    -> theme header/navigation
    -> Favorite Web Tools content view
    -> theme footer
```

A plugin content view should not contain its own `<!DOCTYPE>`, `<html>`, `<body>`, global `<header>`, or global `<footer>` unless the CMS's explicit view contract requires those elements.

A not-found/access-gate view follows the same rule: content-only view, active-theme shell.

---

## 5. CMS API compatibility correction

Custom abstractions may be used inside the plugin, but the boundary passed to Favorite CMS must exactly match the core API contract.

This is especially important for:

- admin menu arrays;
- menu title strings;
- router callback signatures;
- response objects;
- current-user objects;
- migration metadata;
- template resolver inputs.

Do not rely on “array-like” or “string-like” objects when the core calls strict PHP functions that require native arrays/strings.

---

## 6. Exact-artifact verification improvement

Local source tests can pass while an installable package still fails because of stale files, missing files, double nesting, cache, or a different built artifact.

Therefore the final release process must verify the exact ZIP that will be published:

```text
build ZIP
-> record file count / size / SHA-256
-> install that exact ZIP
-> clear relevant caches
-> run real-browser smoke tests
-> publish the same bytes
```

A rebuilt ZIP with a new hash is a new release candidate and must be reverified.

---

## 7. Real-browser acceptance improvement

For critical flows, test results alone are not final proof of production behavior.

Minimum browser checks should include:

- admin plugin dashboard/list/create/edit flows;
- current-user/profile compatibility if plugin menus are globally registered in admin;
- public tool catalog;
- an individual tool page;
- execution success/error states;
- active-theme header/footer behavior;
- active-theme switch behavior;
- responsive/mobile behavior;
- `FREE`, `LOGIN_REQUIRED`, and `MEMBERSHIP_REQUIRED` gates.

---

## 8. Release package contract

Unless the actual Favorite CMS installer requires a different contract, the release archive must use:

```text
favorite-web-tools/
    plugin metadata/bootstrap
    src/
    views/
    assets/
    migrations/
    ...
```

Never package:

```text
favorite-web-tools/favorite-web-tools/
```

Do not include development-only files such as `.git`, editor configuration, local secrets, logs, caches, scratch files, or test-output artifacts unless Favorite CMS explicitly requires them.

---

## 9. Security notes to verify during implementation

The detailed security documents already define the primary rules. During implementation, explicitly verify:

- CSRF protection for state-changing admin/account requests;
- authentication/authorization on every protected execution/configuration endpoint;
- server-side validation independent of client validation;
- SSRF protection for external service/URL integrations;
- upload MIME/extension/size/path validation;
- output escaping by context;
- no secrets in frontend code/logs;
- safe timeouts and bounded response sizes for Python services;
- no open redirects from tool configuration or access-gate return URLs;
- rate/abuse controls only where operationally required, without creating the forbidden user-credit/quota product model.

Operational abuse protection is not a user-facing quota/credit system and must not alter the three access modes.

---

## 10. Implementation precedence

Use this precedence when two documents appear to conflict:

1. Actual Favorite CMS core/public plugin contract.
2. `00-READ-ME-FIRST.md` cross-document locks.
3. Security and access-control specifications.
4. More specific subsystem specification.
5. `33-AI-AGENT-IMPLEMENTATION-MASTER-PLAN.md` implementation sequencing.
6. General overview/examples.

Do not silently choose between contradictory requirements. Record the conflict and resolve it before implementation.
