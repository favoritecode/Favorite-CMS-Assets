# Favorite Multimedia — Visual Asset Foundation

Welcome to the visual asset and branding foundation for **Favorite Multimedia**, the official multimedia management plugin for the Favorite CMS ecosystem.

---

## 1. Purpose & Ecosystem Architecture

**Favorite Multimedia** powers the streaming, cataloging, and media playback lifecycle within Favorite CMS:

```text
Favorite CMS Core
        ↓
   Favorite Pay (Optional financial layer: BDT checkout, gateways, wallet ledger)
        ↓
 Favorite Digital (Optional entitlement layer: memberships, access verification)
        ↓
Favorite Multimedia (Movies, Web Series, Songs, Audio Playlists, Media Delivery)
        ↓
Favorite Web Theme (Visual presentation & templates)
```

### Core Media Capabilities
- **Movies Management**: Complete feature-length movie catalogs, directors, cast, genres, multiple video qualities, multi-language subtitles, and download policies.
- **Web Series & Episodes**: Multi-season hierarchy (Series &rarr; Seasons &rarr; Episodes) with granular access inheritance and episode-level overrides.
- **Songs & Audio Collections**: Single tracks, albums, lyrics display, artist taxonomy, and duration tracking.
- **Continuous Audio Playlists**: Responsive audio playlist player with auto-advance, track ordering, and next/prev controls.
- **Universal Media Player**: Responsive HTML5 player supporting MP4/WebM, HLS/M3U8 streaming, and embedded third-party sources with responsive UI controls.
- **Protected Download Engine**: Authenticated attachment delivery, server-side streaming proxy with SSRF protection, and streaming manifest safeguards.

---

## 2. Brand Relationship & Identity Policy

Favorite Multimedia adheres strictly to the official Favorite CMS brand guidelines:
- **Ecosystem Cohesion**: Follows the core visual palette established in `design/design-tokens.md`:
  - Primary Anchor: `#2563EB` (Favorite Blue)
  - Active & Unlocked States: `#0EA5E9` (Vivid Sky) / `#10B981` (Emerald)
  - Warning States: `#F59E0B` (Amber)
  - Dark Surface Panels: `#0B0F19` and `#1E293B`
- **Zero Incompatible Fonts**: Uses standard system typography (`-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto`).

---

## 3. Directory Structure & Asset Inventory

```text
plugin-assets/favorite-multimedia/
├── README.md                  # This foundational guide
├── icons/                     # Domain-specific multimedia icons (24×24 SVG)
│   ├── movie.svg              # Clapperboard film reel icon
│   ├── series.svg             # Television display series icon
│   ├── song.svg               # Music eighth notes track icon
│   ├── playlist.svg           # Sequential audio playlist icon
│   ├── player-play.svg        # Player play trigger
│   ├── player-pause.svg       # Player pause indicator
│   ├── player-download.svg    # Protected media download arrow
│   └── access-premium.svg     # VIP star membership access badge
├── illustrations/             # Conceptual vector spot artwork (400×300 SVG)
│   └── multimedia-hero.svg    # Modern cinema & audio multimedia hero illustration
└── release/                   # Verified production release package and checksums
    ├── favorite-multimedia.zip # Locked release archive (SHA-256 verified)
    ├── RELEASE.md             # Release metadata, verification stats, capabilities
    ├── checksums.sha256       # Cryptographic checksum file
    └── favorite-multimedia.zip.sha256 # Direct checksum file
```

---

## 4. Technical Specifications

Every SVG in this directory complies with the Favorite CMS asset standards:
- **Scalability**: All icons use `viewBox="0 0 24 24"`; all illustrations use `viewBox="0 0 400 300"`.
- **Pure Vector**: 100% vector XML markup. Zero embedded raster images, zero base64 payloads, zero external fonts.
- **Adaptive Theming**: Icons use `stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"` for light and dark modes.

---

## 5. Security & Prohibited Content

Favorite Multimedia assets must **NEVER** contain:
- Actual binary media files (MP4, MP3, FLAC, MKV, etc.).
- Proprietary DRM keys, access tokens, or private license credentials.
- Backend PHP code, database schemas, or secret API credentials.

