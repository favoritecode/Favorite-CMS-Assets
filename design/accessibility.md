# Favorite CMS — Accessibility Guidelines

Practical baseline rules ensuring all Favorite CMS interfaces and ecosystem extensions remain accessible to all users.

---

## 1. Readable Contrast (WCAG 2.1 AA)

- **Standard Text**: Body copy and input labels must achieve a contrast ratio of at least **`4.5:1`** against their background.
  - `#0F172A` (Ink Navy) on `#FFFFFF`: **16.1:1** (Passes AAA)
  - `#334155` (Slate Gray) on `#FFFFFF`: **9.6:1** (Passes AAA)
  - `#2563EB` (Favorite Blue) on `#FFFFFF`: **4.6:1** (Passes AA)
- **Large Text & Graphical Objects**: Headings (`>= 24px` or bold `>= 18.5px`) and interactive boundaries (input borders, active tab lines) must achieve at least **`3.0:1`**.
- **Dark Mode**: High-contrast white (`#FFFFFF`) or light slate (`#E2E8F0`) text against dark backgrounds (`#0B0F19` / `#0F172A`) ensures over **12:1** contrast.

---

## 2. Keyboard Accessibility

- **100% Operable by Keyboard**: Every actionable control (buttons, links, form fields, disclosure widgets, dropdowns, modal dismissals) must be reachable and operable using standard keyboard controls:
  - `Tab` / `Shift + Tab`: Move forward and backward through interactive elements.
  - `Enter`: Activate links and primary actions; submit forms.
  - `Space`: Toggle checkboxes, activate buttons, expand accordions.
  - `Arrow Keys`: Navigate within menus, radio groups, and tab lists.
  - `Escape`: Dismiss open modals, popovers, and dropdown menus.
- **Logical Tab Order**: The DOM order must match the visual reading order. Never manipulate visual position with CSS in a way that breaks sequential tab navigation.
- **Focus Trapping**: When a modal dialog opens, keyboard focus must be trapped inside the modal until dismissed, and focus must return to the triggering element upon closure.

---

## 3. Visible Focus State

- **Never Remove Focus Rings**: Do not apply `outline: none` without providing a distinct, high-contrast replacement.
- **Standard Focus Ring**:
  ```css
  :focus-visible {
    outline: 2px solid #2563EB;
    outline-offset: 2px;
  }
  ```
- **Dark Mode Focus Ring**: Use `#38BDF8` (Bright Cyan) or `#60A5FA` (Light Blue) for high visibility against dark surfaces.

---

## 4. Meaningful Labels & Accessible Names

- **Form Controls**: Every text input, select, and textarea must be explicitly bound to a visible `<label>` using the `for` / `id` attribute.
- **Icon-Only Buttons**: Any button without visible text (e.g., close icon `✕`, trash icon, search button) must include an `aria-label` or visually hidden screen-reader text (`.sr-only`).
- **Image Alternative Text**: Every non-decorative image must have descriptive `alt` text. Purely decorative graphics and background patterns must use `alt=""` or `aria-hidden="true"`.

---

## 5. Touch-Friendly Controls

- **Minimum Touch Targets**: On mobile devices and touch screens, interactive controls must provide a minimum touch target area of **`44px × 44px`** (via padding or bounding box), even if the visual icon is smaller.
- **Control Spacing**: Ensure at least `8px` of separation between adjacent touch targets to prevent accidental taps.

---

## 6. Color Independence

- **Never Rely on Color Alone**: Color must never be the sole visual indicator of status, errors, or requirements.
- **Redundant Indicators**:
  - Pair status colors with descriptive text labels (e.g., "Active", "Pending", "Failed").
  - Pair error messages with clear icons (e.g., alert exclamation circle) and descriptive error text.
  - Form validation states must display explicit text explanations below the input.

---

## 7. Responsive Text & Zoom

- **Scalable Units**: Define all typography using `rem` units so the interface scales seamlessly when users adjust their browser root font size.
- **200% Zoom Support**: Layouts must support zooming up to `200%` without clipping text, overflowing containers, or losing functionality.

---

## 8. Reduced-Motion Considerations

- **Respect Motion Preferences**: Honor the user's operating system animation settings via the CSS `prefers-reduced-motion` media query:
  ```css
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
      scroll-behavior: auto !important;
    }
  }
  ```
- Any decorative animations must instantly disable or collapse to an instantaneous opacity cross-fade.
