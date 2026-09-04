# Favorite CMS — Design Tokens

A compact, practical set of design tokens establishing visual consistency across Favorite CMS and all ecosystem projects.

---

## 1. Color Tokens

Based directly on the official Favorite CMS brand foundation.

### Brand Palette
```css
--fav-brand-primary:       #2563EB; /* Favorite Blue (Primary Anchor) */
--fav-brand-primary-hover: #1D4ED8; /* Deep Royal (Hover/Active) */
--fav-brand-primary-light: #3B82F6; /* Electric Blue (Dark Surface Primary) */
--fav-brand-accent:        #0EA5E9; /* Vivid Sky (Modular Wing & Highlights) */
--fav-brand-accent-hover:  #0284C7; /* Deep Sky */
--fav-brand-accent-light:  #38BDF8; /* Bright Cyan (Dark Surface Accent) */
```

### Neutral & Surface (Light Mode)
```css
--fav-bg-canvas:       #F8FAFC; /* Page Background */
--fav-bg-surface:      #FFFFFF; /* Card & Panel Surface */
--fav-bg-subtle:       #F1F5F9; /* Subtle Table Rows, Code Blocks */
--fav-border-default:  #E2E8F0; /* Standard Dividers & Card Borders */
--fav-border-strong:   #CBD5E1; /* Input Borders, Active Outlines */
--fav-text-primary:    #0F172A; /* Primary Headings & Body Text */
--fav-text-secondary:  #334155; /* Secondary Labels & Descriptions */
--fav-text-muted:      #64748B; /* Captions, Footnotes, Meta Text */
```

### Neutral & Surface (Dark Mode)
```css
--fav-bg-canvas-dark:      #0B0F19; /* Dark Canvas */
--fav-bg-surface-dark:     #0F172A; /* Dark Surface Panels */
--fav-bg-subtle-dark:      #1E293B; /* Dark Elevated Elements */
--fav-border-default-dark: #334155; /* Dark Dividers */
--fav-border-strong-dark:  #475569; /* Dark Active Borders */
--fav-text-primary-dark:   #FFFFFF; /* Pure White High-Contrast */
--fav-text-secondary-dark: #E2E8F0; /* Clean Reading Contrast */
--fav-text-muted-dark:     #94A3B8; /* Muted Dark Text */
```

### Semantic Status Colors
```css
/* Success (Matches future Favorite Pay accent) */
--fav-color-success:        #10B981;
--fav-color-success-subtle: #ECFDF5;
--fav-color-success-border: #A7F3D0;

/* Warning (Matches future Favorite Shop accent) */
--fav-color-warning:        #F59E0B;
--fav-color-warning-subtle: #FFFBEB;
--fav-color-warning-border: #FDE68A;

/* Error / Danger */
--fav-color-error:          #EF4444;
--fav-color-error-subtle:   #FEF2F2;
--fav-color-error-border:   #FECACA;

/* Information (Matches Favorite Web / Sky accent) */
--fav-color-info:           #0EA5E9;
--fav-color-info-subtle:    #F0F9FF;
--fav-color-info-border:    #BAE6FD;
```

---

## 2. Spacing Scale

Built on a clean 4px modular grid.

| Token | Value (px) | Value (rem) | Common Application |
| :--- | :--- | :--- | :--- |
| `--fav-space-1` | `4px` | `0.25rem` | Micro spacing, icon gaps |
| `--fav-space-2` | `8px` | `0.5rem` | Badge padding, tight input gaps |
| `--fav-space-3` | `12px` | `0.75rem` | Button horizontal padding, compact lists |
| `--fav-space-4` | `16px` | `1.0rem` | Default card padding, form gap |
| `--fav-space-6` | `24px` | `1.5rem` | Section gutters, standard card interior |
| `--fav-space-8` | `32px` | `2.0rem` | Page layout margins, major headings |
| `--fav-space-12`| `48px` | `3.0rem` | Block separation, hero padding |
| `--fav-space-16`| `64px` | `4.0rem` | Major page sections |

---

## 3. Border Radius Scale

Maintains the clean, approachable geometric curves of the brand mark.

| Token | Value | Common Application |
| :--- | :--- | :--- |
| `--fav-radius-none` | `0px` | Full-bleed banners, sharp tables |
| `--fav-radius-sm` | `4px` | Badges, tags, code snippets |
| `--fav-radius-md` | `6px` | Buttons, text inputs, select boxes |
| `--fav-radius-lg` | `8px` | Standard cards, dropdown menus, modals |
| `--fav-radius-xl` | `12px`| Hero cards, notification toasts |
| `--fav-radius-full`| `9999px` | Circular avatars, rounded status pills |

---

## 4. Shadow & Elevation Scale

Minimal, subtle shadows that maintain clarity without excessive visual weight.

```css
--fav-shadow-none: none;
--fav-shadow-sm:   0 1px 2px 0 rgba(15, 23, 42, 0.05);
--fav-shadow-md:   0 4px 6px -1px rgba(15, 23, 42, 0.08), 0 2px 4px -2px rgba(15, 23, 42, 0.04);
--fav-shadow-lg:   0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.03);
```

---

## 5. Component Sizing Scale

Standardized control heights for buttons, text inputs, and select elements:

| Size | Height | Font Size | Horizontal Padding | Touch Target |
| :--- | :--- | :--- | :--- | :--- |
| **Small (`sm`)** | `32px` | `13px` (`0.8125rem`) | `10px` | Compact tables / toolbars |
| **Medium (`md`)**| `40px` | `14px` (`0.875rem`) | `16px` | **Default for all forms & UI** |
| **Large (`lg`)** | `48px` | `16px` (`1.0rem`) | `20px` | Primary marketing CTAs, mobile touch |
