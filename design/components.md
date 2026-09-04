# Favorite CMS — Component Guidance

Design specifications and interaction rules for standard UI components across the Favorite CMS ecosystem.

> [!NOTE]
> This document provides architectural design guidance only. Do not add runtime component code, frameworks, or dependencies to this repository.

---

## 1. Buttons

- **Primary Button**:
  - Background: `--fav-brand-primary` (`#2563EB`).
  - Text: `#FFFFFF`, Font Weight: `600`.
  - Hover: `--fav-brand-primary-hover` (`#1D4ED8`).
  - Usage: The single primary action on a screen or dialog (e.g., "Save Changes", "Publish").
- **Secondary Button**:
  - Background: `--fav-bg-subtle` (`#F1F5F9`), Border: `--fav-border-default` (`#E2E8F0`).
  - Text: `--fav-text-primary` (`#0F172A`).
  - Hover: Background `#E2E8F0`.
  - Usage: Supporting actions (e.g., "Cancel", "Back", "Preview").
- **Outline Button**:
  - Background: Transparent, Border: `1px solid --fav-border-strong` (`#CBD5E1`).
  - Text: `--fav-text-primary` (`#0F172A`).
- **Ghost Button**:
  - Background: Transparent, No border.
  - Hover: Background `--fav-bg-subtle`.
  - Usage: Toolbar icons, pagination controls, inline utility actions.
- **Destructive Button**:
  - Background: `--fav-color-error` (`#EF4444`), Text: `#FFFFFF`.
  - Hover: Background `#DC2626`.
  - Usage: Permanent deletions, irreversible actions.
- **Standard Heights**: Small (`32px`), Medium/Default (`40px`), Large (`48px`). Border Radius: `6px`.

---

## 2. Links

- **Inline Links**:
  - Color: `--fav-brand-primary` (`#2563EB`).
  - Hover: Underline with color `--fav-brand-primary-hover` (`#1D4ED8`).
  - In long-form text, links must be visually distinct from non-linked text via both color and underline or weight.
- **Navigation Links**:
  - Color: `--fav-text-secondary` (`#334155`).
  - Hover: `--fav-text-primary` (`#0F172A`).
  - Active: Bold weight (`600`), color `--fav-brand-primary`, with an active underline or pill background.

---

## 3. Forms & Inputs

- **Text Inputs & Selects**:
  - Default Height: `40px`. Border Radius: `6px`.
  - Border: `1px solid --fav-border-strong` (`#CBD5E1`).
  - Background: `--fav-bg-surface` (`#FFFFFF`).
  - Padding: `0 12px`. Font Size: `14px` (minimum `16px` on mobile viewports to prevent iOS auto-zoom).
  - Focus State: Border color `--fav-brand-primary` with a `2px` focus ring (`rgba(37, 99, 235, 0.2)`).
  - Error State: Border color `--fav-color-error` (`#EF4444`) with descriptive error text below the field.
- **Labels**:
  - Positioned directly above the input with `4px` gap. Font Size: `14px`, Font Weight: `500`, Color: `--fav-text-primary`.
- **Helper & Validation Text**:
  - Positioned below input with `4px` gap. Font Size: `12px`. Default Color: `--fav-text-muted`; Error Color: `--fav-color-error`.
- **Checkboxes & Radios**:
  - Size: `18px × 18px`. Accent Color: `--fav-brand-primary`.
  - Radios must use circle geometry; checkboxes use square with `4px` radius.

---

## 4. Cards

- **Surface & Boundary**:
  - Background: `--fav-bg-surface` (`#FFFFFF` in light, `#0F172A` in dark).
  - Border: `1px solid --fav-border-default` (`#E2E8F0` in light, `#334155` in dark).
  - Border Radius: `8px`.
  - Padding: `16px` (compact) or `24px` (standard).
  - Shadow: `--fav-shadow-sm`.
- **Interactive Cards**:
  - On hover: Border shifts to `--fav-border-strong` with elevation `--fav-shadow-md`. Avoid exaggerated physical lifts.

---

## 5. Badges & Status Indicators

- **Pill / Badge Structure**:
  - Height: `22px` to `24px`. Border Radius: `4px` or full pill (`9999px`).
  - Padding: `2px 8px`. Font Size: `12px`, Font Weight: `600`, Letter Spacing: `0.02em`.
- **Semantic Mapping**:
  - **Success**: Background `#ECFDF5`, Border `#A7F3D0`, Text `#065F46` (e.g., "Active", "Paid").
  - **Warning**: Background `#FFFBEB`, Border `#FDE68A`, Text `#92400E` (e.g., "Pending", "Needs Update").
  - **Error / Danger**: Background `#FEF2F2`, Border `#FECACA`, Text `#991B1B` (e.g., "Failed", "Inactive").
  - **Info / Neutral**: Background `#F1F5F9`, Border `#E2E8F0`, Text `#334155` (e.g., "Draft", "v1.0").

---

## 6. Alerts & Banners

- **Layout**:
  - Border Radius: `8px`. Padding: `16px`.
  - Left-aligned icon (`20px`) corresponding to alert type.
  - Header: `14px` Font Weight `600`. Body: `14px` Font Weight `400`.
  - Includes optional dismiss button (`ghost` style) on top right.
- **Color Themes**:
  - Success: Subtle green background with green border.
  - Warning: Subtle amber background with amber border.
  - Error: Subtle rose background with rose border.
  - Info: Subtle blue/sky background with blue border.

---

## 7. Tables

- **Header**:
  - Background: `--fav-bg-subtle` (`#F1F5F9`).
  - Text: `12px` uppercase, Font Weight `600`, Letter Spacing `0.05em`, Color `--fav-text-muted`.
  - Border Bottom: `1px solid --fav-border-default`.
- **Rows & Cells**:
  - Row Padding: `12px 16px`. Font Size: `14px`, Color `--fav-text-primary`.
  - Cell Divider: `1px solid --fav-border-default`.
  - Hover: Background `--fav-bg-canvas` (`#F8FAFC`).
  - Alignment: Text columns left-aligned; numerical/currency columns right-aligned; status/action columns centered or right-aligned.
- **Responsiveness**:
  - Always wrap tables in a container with `overflow-x: auto` to prevent viewport breakages on mobile devices.

---

## 8. Navigation

- **Top Application Bar**:
  - Height: `60px` to `64px`.
  - Background: `#FFFFFF` with bottom border `1px solid --fav-border-default`.
  - Left: Logo mark + product name.
  - Center/Right: Primary navigation items, global search, notification icon, user profile dropdown.
- **Admin Sidebar**:
  - Width: `240px` to `260px`.
  - Active Item: Distinct background (`--fav-bg-subtle` or soft brand tint) + left accent indicator bar (`3px solid --fav-brand-primary`).
  - Collapsible to compact icon-only mode (`64px` width) for space efficiency.
- **Breadcrumbs**:
  - Font Size: `13px` (`0.8125rem`). Separator: `/` or `chevron-right` in `--fav-text-muted`. Current page in `--fav-text-primary` (`font-weight: 600`).

---

## 9. Modals & Dialogs

- **Backdrop**:
  - Overlay: `rgba(15, 23, 42, 0.6)` with subtle backdrop fade.
- **Card**:
  - Max Width: `480px` (confirmation) to `640px` (standard form).
  - Background: `#FFFFFF`. Border Radius: `8px`. Elevation: `--fav-shadow-lg`.
- **Structure**:
  - Header: Dialog title (`18px`, `font-weight: 700`) + close icon button.
  - Body: Dialog content / form inputs with `20px` padding.
  - Footer: Actions aligned to the right (Secondary "Cancel" on left of Primary action).
- **Keyboard**:
  - Must close immediately on `Escape` key press.
  - Focus must remain trapped inside the modal while open.

---

## 10. Empty States

- **Structure**:
  - Centered layout inside container. Padding: `48px 24px`.
  - Graphic: Simple vector icon or line illustration (`64px` to `96px`).
  - Title: Clear, action-oriented heading (`18px`, `font-weight: 700`, e.g., *"No plugins installed yet"*).
  - Description: Helpful explanation (`14px`, Color `--fav-text-muted`, max width `40ch`).
  - Call to Action: Primary button pointing to the initial setup step (e.g., *"Browse Marketplace"*).

---

## 11. Loading States

- **Skeleton Screens**:
  - Preferred over generic spinners for page layouts.
  - Background: `#E2E8F0` with a subtle, non-distracting pulse animation.
  - Matches the exact dimensions and border radii of the content it replaces.
- **Button Loading**:
  - When a form is submitting, the button displays an inline spinner (`16px`), maintains its width, and sets `disabled` state to prevent duplicate submissions.
- **Asynchronous Feedback**:
  - Background processes must display deterministic progress bars where known, or indeterminate activity bars with descriptive status labels.
