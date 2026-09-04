# Favorite CMS — Typography Guidelines

Clean, modern, highly legible typographic system optimized for high performance and universal accessibility across all devices.

---

## 1. Font Family Stacks

Favorite CMS prioritizes native system font stacks and widely available open-source web fonts. This guarantees instant rendering with zero layout shifts, zero licensing encumbrances, and zero external network latency.

### Primary UI & Body Font
```css
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```
*Optional Web Font Equivalent*: If an embedded web font is used in documentation or marketing sites, **Inter** or **Plus Jakarta Sans** is recommended.

### Code & Monospace Font
```css
font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
```
Used for code snippets, API endpoints, shortcodes, and technical metadata.

---

## 2. Heading Hierarchy

Headings use crisp negative letter-spacing and tight line heights to maintain visual impact and structured rhythm.

| Level | Size (rem) | Size (px) | Weight | Line Height | Letter Spacing | Usage |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **H1** | `2.25rem` | `36px` | Bold / 800 | `1.2` | `-0.025em` | Page titles, primary dashboard headings |
| **H2** | `1.75rem` | `28px` | Bold / 700 | `1.25` | `-0.02em` | Major content sections, card group headers |
| **H3** | `1.375rem` | `22px` | Bold / 700 | `1.3` | `-0.015em` | Subsection titles, modal headers |
| **H4** | `1.125rem` | `18px` | Semibold / 600 | `1.4` | `-0.01em` | Card titles, widget headings |
| **H5** | `1.0rem` | `16px` | Semibold / 600 | `1.4` | `0` | Form section labels, list headers |
| **H6** | `0.875rem` | `14px` | Semibold / 600 | `1.4` | `0.025em` | Eyebrow text, uppercase category tags |

---

## 3. Body & Content Text

Body text is engineered for sustained reading comfort and high legibility.

| Style | Size (rem) | Size (px) | Weight | Line Height | Usage |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Body Large** | `1.125rem` | `18px` | Regular / 400 | `1.6` | Article intros, hero lead paragraphs |
| **Body Base** | `1.0rem` | `16px` | Regular / 400 | `1.5 - 1.6` | **Default content and UI text** |
| **Body Small** | `0.875rem` | `14px` | Regular / 400 | `1.5` | Form helper text, table content |
| **Caption** | `0.75rem` | `12px` | Medium / 500 | `1.4` | Timestamps, status pills, fine print |

---

## 4. UI Controls & Labels

Interactive UI controls require distinct weight and subtle tracking to communicate affordance clearly:

- **Buttons (Default)**: `14px` (`0.875rem`), Font Weight `600` (Semibold), Line Height `1.0`, Letter Spacing `0.01em`.
- **Buttons (Large)**: `16px` (`1.0rem`), Font Weight `600` (Semibold), Line Height `1.0`.
- **Form Input Labels**: `14px` (`0.875rem`), Font Weight `500` (Medium), Color `#334155`.
- **Navigation Links**: `14px` (`0.875rem`), Font Weight `500` (Medium) default, `600` (Semibold) active.
- **Badge Text**: `12px` (`0.75rem`), Font Weight `600` (Semibold), Letter Spacing `0.02em`.

---

## 5. Font Weights Scale

```css
--fav-font-regular:  400; /* Body copy, descriptions */
--fav-font-medium:   500; /* Form labels, table cells, secondary links */
--fav-font-semibold: 600; /* Subheadings, buttons, badge labels, card titles */
--fav-font-bold:     700; /* Headings (H2, H3), primary titles */
--fav-font-extrabold: 800; /* H1 page titles, hero displays, wordmarks */
```

---

## 6. Typographic Best Practices

1. **Relative Units**: Always declare font sizes in `rem` and line heights as unitless multipliers (e.g., `1.5`), ensuring respectful scaling with user browser accessibility settings.
2. **Line Length**: Keep long-form content reading widths between `60` and `75` characters per line (`max-width: 68ch`) to prevent visual fatigue.
3. **Contrast Compliance**: Body text on light backgrounds must never fall below `#334155` to consistently exceed the WCAG AA contrast ratio of `4.5:1`.
