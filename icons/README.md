# Favorite CMS — Icon System

A unified, lightweight, and extensible SVG icon system engineered for Favorite CMS Core, the Administrative Dashboard, Favorite Web theme, ecosystem plugins (Favorite Pay, Favorite Digital, Favorite Shop), and technical documentation.

---

## 1. System Purpose & Architecture

The Favorite CMS Icon System provides a foundational suite of 57 vector symbols designed for clarity, high contrast, and immediate recognition across administrative interfaces and consumer-facing web experiences.

- **Centralized & Modular**: Grouped into 7 logical categories reflecting core CMS and commerce operations.
- **Zero Framework Dependency**: Pure, valid SVGs that can be embedded directly, referenced via standard `<img>` tags, or inlined in templating systems without JavaScript libraries or icon-font bloat.
- **Theme-Agnostic (`currentColor`)**: Fills and strokes dynamically adapt to parent typography colors across light mode, dark mode, brand-active states, and semantic alerts.

---

## 2. Visual Style & Grid Standards

All icons strictly adhere to a shared geometric construction language:

- **Grid Base**: `24px × 24px` coordinate grid (`viewBox="0 0 24 24"`).
- **Stroke Architecture**: Uniform `2px` stroke weight (`stroke-width="2"`).
- **Terminals & Corners**: Round stroke caps (`stroke-linecap="round"`) and round corner joins (`stroke-linejoin="round"`), echoing the friendly, modern geometric curves of the Favorite CMS brand mark.
- **Visual Weight**: Optical centering within a 20×20 safe canvas zone (2px outer padding).
- **Simplicity**: Flat-first vector geometry. Free from gradients, pseudo-3D bevels, drop shadows, or unneeded decorative clutter.

---

## 3. Category Structure & Available Icons (57 Total)

### Navigation (`icons/navigation/` — 9 icons)
Structural navigation, wayfinding, and layout panels.
- `dashboard.svg` — Modular admin widget grid
- `home.svg` — Home / site front-end
- `menu.svg` — Hamburger drawer toggle
- `search.svg` — Global search magnifying lens
- `settings.svg` — System settings cog
- `back.svg` — Previous navigation arrow
- `forward.svg` — Forward navigation arrow
- `chevron-down.svg` — Dropdown / collapse toggle
- `chevron-right.svg` — Submenu / breadcrumb arrow

### Actions (`icons/actions/` — 12 icons)
User commands, state modifications, and toolbar tools.
- `add.svg` — Plus symbol (create / add new)
- `edit.svg` — Pencil on baseline (modify / draft)
- `delete.svg` — Wastebasket (remove / trash)
- `save.svg` — Storage diskette (save changes)
- `close.svg` — Dismiss cross (`✕`)
- `upload.svg` — Tray with upward arrow
- `download.svg` — Tray with downward arrow
- `refresh.svg` — Dual circular arrows (reload / sync)
- `copy.svg` — Overlapping document sheets (duplicate / clipboard)
- `filter.svg` — Funnel tool (data filtering)
- `sort.svg` — Ascending order bars with direction arrow
- `more.svg` — Horizontal ellipsis (`...`) for contextual menus

### Content & Taxonomy (`icons/content/` — 9 icons)
Publishing units, media assets, and structural organization.
- `post.svg` — Article sheet with written lines
- `page.svg` — Webpage layout wireframe
- `media.svg` — Media item with play badge
- `image.svg` — Picture frame with mountains and sun
- `file.svg` — Single document with folded corner
- `folder.svg` — Directory folder
- `tag.svg` — Tag badge with punch hole
- `category.svg` — 2×2 cluster taxonomy blocks
- `calendar.svg` — Date picker with binder rings and grid

### Users & Permissions (`icons/users/` — 5 icons)
Identity management, team collaboration, and access control.
- `user.svg` — Single user profile silhouette
- `users.svg` — Multi-user community / team
- `role.svg` — User entity paired with access key
- `moderator.svg` — Shield badge with verification check
- `account.svg` — User avatar enclosed in profile circle

### Communication (`icons/communication/` — 5 icons)
Messaging, user feedback, and customer support.
- `comment.svg` — Single conversation bubble
- `message.svg` — Dual chat dialog bubbles
- `email.svg` — Postal envelope with flap
- `notification.svg` — Alert bell with clapper
- `support.svg` — Lifebuoy lifesaver ring

### Commerce (`icons/commerce/` — 9 icons)
E-commerce, transactions, subscriptions, and digital goods.
- `product.svg` — Isometric merchandise package
- `cart.svg` — Shopping cart on wheels
- `order.svg` — Receipt clipboard with checkmark
- `payment.svg` — Credit/debit card with magnetic band
- `wallet.svg` — Leather billfold wallet with coin clasp
- `money.svg` — Currency banknote
- `shipping.svg` — Logistics transport truck
- `download-product.svg` — Packaged cloud download for digital licenses/files
- `membership.svg` — VIP membership star badge

### Status & Feedback (`icons/status/` — 8 icons)
System states, validation outcomes, and security indicators.
- `success.svg` — Enclosed circle with checkmark
- `warning.svg` — Danger triangle with exclamation point
- `error.svg` — Enclosed circle with cross
- `info.svg` — Enclosed circle with information mark (`i`)
- `pending.svg` — Clock face with hour and minute hands
- `locked.svg` — Closed padlock
- `unlocked.svg` — Open shackle padlock
- `verified.svg` — 12-point scalloped rosette with checkmark

---

## 4. Usage & Implementation Guidance

### Inline SVG (Recommended)
Inline embedding allows full control over color, hover transitions, and sizing via CSS:
```html
<svg class="icon icon-settings" width="20" height="20" aria-hidden="true">
  <!-- Content from icons/navigation/settings.svg -->
</svg>
```

### CSS currentColor Styling
By default, all strokes reference `currentColor`. Changing the parent element's text color automatically colors the icon:
```css
.btn-primary .icon {
  color: #FFFFFF;
}

.alert-success .icon {
  color: var(--fav-color-success, #10B981);
}

.nav-link:hover .icon {
  color: var(--fav-brand-primary, #2563EB);
}
```

### Sizing Scale
Standard sizes recommended across ecosystem interfaces:

| Size Token | Pixel Dimensions | Recommended Usage |
| :--- | :--- | :--- |
| **Micro (`sm`)** | `16px × 16px` | Inline badges, table row utility actions, breadcrumbs |
| **Default (`md`)** | `20px × 20px` | Navigation menu links, button leading icons, form inputs |
| **Prominent (`lg`)** | `24px × 24px` | Top bar utility icons, card headers, status banners |
| **Hero (`xl`)** | `32px – 48px` | Empty state cards, onboarding flows, modal headers |

---

## 5. File Naming & Expansion Rules

1. **Lowercase + Hyphens**: Always use kebab-case (`chevron-down.svg`, `download-product.svg`).
2. **Category Placement**: Place new icons into the exact matching functional category folder.
3. **No Redundant Duplicates**: Do not add near-identical icons. Prefer semantically distinct symbols.
4. **No Third-Party Dumps**: Do NOT bulk import external icon libraries (e.g., Font Awesome, Lucide, Material Icons, Feather). Every icon in this repository must remain coherent with the Favorite CMS geometric standard.
5. **No Embedded Code**: SVGs must never contain `<script>`, raster base64 data, or external network links.
