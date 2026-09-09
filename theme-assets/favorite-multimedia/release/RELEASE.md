# Favorite Multimedia Theme v1.0.2 Release Notes

- **Version**: 1.0.2
- **Release Date**: 2026-09-09
- **Type**: UI/UX Polish — Premium Header + Persistent Global Navigation + Detail Page & Sidebar Finalization
- **Requires Plugin**: Favorite Multimedia v1.0.6+
- **SHA-256**: `fc75922477be25d299e123a6dfde152c41846d051c8260c7d5ce624299073d45`

## Highlights & Improvements
- **Detail Pages Unified Layout & Theme Tokens**:
  - Refactored all detail views (`Movie`, `Series`, `Episode`, `Song`, `Album`, `Artist`, `Playlist`) to use Theme Studio tokens (`--fm-color-bg-page`, `--fm-color-bg-surface`, `--fm-color-bg-elevated`, `--fm-color-text-primary`, `--fm-color-text-secondary`, `--fm-color-text-muted`, `--fm-color-border`, etc.).
  - Completely eliminated hardcoded navy/slate dark hexes (`#1e293b`, `#0f172a`, `#334155`).
  - Ensured only ONE persistent global header is rendered across all detail views (no duplicate sub-headers).
- **Reusable Single Content Sticky Sidebar**:
  - Highly configurable responsive sidebar system with dedicated Theme Studio controls in `ThemeConfig`:
    - `sidebar_enabled` (global and per-content-type enable toggles).
    - `sidebar_position`: `'right'` (default) or `'left'`.
    - `sidebar_width`: configurable with unit validation (default `'320px'`).
    - `sidebar_sticky`: toggleable sticky behavior with customizable top offset (`sidebar_sticky_offset`: `'20px'`).
    - `sidebar_surface`: surface elevation styling (`'elevated'`, `'surface'`, `'card'`).
    - Modular content blocks: `poster`, `actions` (Play/Next, Favorite, Follow), `download`, and `metadata`.
  - Responsive layout: seamlessly stacks below content on mobile viewports while preserving complete functionality.
- **Download UI Deduplication**:
  - Integrated smart deduplication: when the sidebar download block is active, inline download bars below players are suppressed to present exactly one primary download control.
- **Configurable Back Link**:
  - Optional back navigation link (`← Back to Movies`, `← Back to Series`, `← Back to Music`) controlled by `single_content.show_back_link` (default `false` / OFF).
  - Automatically targets canonical routes (`/movies`, `/series`, `/multimedia/music`).
- **Clickable Poster Media with Valid HTML5**:
  - Poster media and placeholder thumbnails are wrapped in accessible, valid links (`.fav-discovery-poster-link`) without nested `<a>` inside `<a>` tags.
- **Premium Dark Header Surface**: Modern streaming aesthetic with customizable glassmorphism, responsive height, and smooth scroll transitions to solid elevated dark state (`.fm-header-scrolled`).
- **Expanding Search Pill**: Elegant compact pill beside profile button that smoothly expands (150–250ms) on focus/click, auto-focuses input, collapses on Escape or click-outside, and submits search queries via GET to `/multimedia/search?q=...`.
- **Persistent Global Navigation**: Top navigation remains sticky (`position: sticky; top: 0; z-index: 1000`) and is rendered consistently across all frontend routes (Home, Movies, Series, Music, Search, Library, My List, History, Movie Detail, Series Detail, Episode Detail, Song Detail, Album, Artist, Playlist).
- **Route-Aware Active Nav**: Automatic canonical path detection highlighting the active section with `.active` class and `aria-current="page"` attribute.
- **Pure Inline SVG Icons**: Removed all emojis across header branding, search, chevron dropdown indicators, profile menu items, and mobile bottom navigation in favor of sharp, accessible SVGs.
- **Layering & Z-Index System**:
  - Header: `z-index: 1000`
  - Profile & Search Dropdowns: `z-index: 1050`
  - Audio Mini-Player: `z-index: 900`
  - Audio Expanded Player & Queue Drawer: `z-index: 2000`
- **Logged-in Profile Menu Integration**:
  - Replaced guest "Sign In" button with accessible profile dropdown when authenticated.
  - Avatar badge with initials, user display name, and dropdown indicator.
  - Role-aware menu items: My Profile, My Library, My List, History, Dashboard (if permitted), and Log Out.
- **Theme Studio Integration**: Fully respects Theme Studio settings (`header.sticky`, `header.style`, `branding.brand_title`, `branding.logo_url`, `single_content.*`).
- **Strict Isolation**: Favorite CMS core, Favorite Digital, and Favorite Pay completely untouched.

---

## Favorite Multimedia Theme v1.0.1 Release Notes (Historical)

- **Version**: 1.0.1
- **Release Date**: 2026-09-09
- **Type**: Critical Bugfix & CMS Installer Compatibility
- **Requires Plugin**: Favorite Multimedia v1.0.6+
- **SHA-256**: `d904702b7ce6c3bfd6cf3950af26ccc013f8bb2b35e19c27e036ed0f404b48d3`

---

## Favorite Multimedia Theme v1.0.0 Release Notes (Historical)

- **Version**: 1.0.0
- **Release Date**: 2026-09-09
- **Type**: Initial Major Release
- **Requires Plugin**: Favorite Multimedia v1.0.6+
- **SHA-256**: `64419d881d03c0bb99b72aec027b93bf46e37c4b3c7e95a0efec204c895c9c82`