# Favorite Multimedia — Production Release Package

This directory contains the authoritative, verified production release archive and checksum verification metadata for **Favorite Multimedia**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | `v1.0.4` |
| **Package File** | `favorite-multimedia.zip` |
| **Package Size** | 359,683 bytes |
| **SHA-256 Checksum** | `8ecef132a3f525848f7f6aedbebd1a3db94b3501a8e4561e0d4386de5a289e23` |
| **Source Repository** | `favoritecode/Favorite-CMS-Universal` |
| **Test Suite Verification** | 312 tests, 1,893 assertions (0 failures, 0 errors) |
| **Target Platform** | Favorite CMS Core (`Favorite-CMS-Universal`) |
| **Plugin Identifier** | `favorite-multimedia` |

---

## What's New in v1.0.4 — Final Hardening & Publishing Reliability

This official release hardens media URL resolution, SSRF boundaries, and publishing workflows while cleaning up admin navigation:

### Highlights

- **Deterministic Media Source Resolution**: Enforces strict URL precedence: YouTube -> Vimeo -> HLS (`.m3u8`) -> Direct Video -> Direct Audio -> Trusted External Embed -> Unknown.
- **MIME & Fallback Integrity**: Eliminates arbitrary MIME fabrication for unknown streams; preserves clean empty MIME when undetermined, and prevents unvalidated URLs from defaulting to embed.
- **SSRF Network Boundaries Preserved**: Admin-entered URLs avoid blocking live DNS lookups on form submission, while server-side network execution (`MediaDeliveryService` downloads and subtitle fetching) retains full DNS rebinding and loopback/private IP protection.
- **Configured vs. Playable Source Separation**: Distinguishes between configured sources (`MediaSource::isConfigured()`) and active playable sources (`MediaSource::isPlayable()`, `MediaSource::getPlayableForContent()`). Inactive or disabled sources count as configured to prevent accidental draft demotion on metadata updates, while publication readiness strictly requires at least one active, playable source matching the content mode.
- **Accurate Admin Media Statuses**: Introduces distinct statuses: `Ready` (active playable source), `Not Ready` / `Failed` (configured source inactive/failed), `Processing` (transcoding in progress), and `Check Failed` (exception/error containment).
- **Admin Navigation Cleanup**: Removed duplicate `Multimedia` child submenu under the top-level Multimedia menu. Top-level link routes cleanly to the canonical Dashboard without redundant child links.
- **Fail-Closed Access Boundaries**: Complete security isolation maintained with Favorite Digital (membership/licensing) and Favorite Pay (gateways). Zero core modifications to Favorite CMS.

---

## What's New in v1.0.3 — Playback Reliability, Multi-Source & Song Video Support

This major release brings robust external embed playback, multiple playback sources with failover, viewer source switching, and **100% video publishing parity for Songs** (Audio, Video, and Dual-Mode) to Favorite Multimedia:

### Highlights

- **Fixed YouTube & Vimeo Playback Normalization**: Eliminates player breakdowns by normalizing raw watch/share URLs to privacy-enhanced embed endpoints with strict hostname and parameter validation.
- **Generic External Embed with Domain Allowlist**: Added support for trusted third-party video players and external iframe embeds with admin-configurable domain allowlist, sandbox attributes, and private IP/loopback SSRF rejection.
- **Multiple Playback Sources per Content**: Attach multiple video/embed sources to Movies, Episodes, and Songs with custom labels, active/inactive toggling, drag/reorder controls, and automatic single-default enforcement (`MediaSource::enforceSingleDefault()`).
- **Manual Source Switching**: Frontend player dropdown rendered seamlessly when multiple active sources exist ($N \ge 2$), preserving playback position (`currentTime`) during transitions.
- **Automatic Playback Failover**: Intercepts fatal HTML5 errors (error codes 2, 3, 4) and HLS fatal errors with automatic, non-blocking failover to the next available source, complete with cycle loop protection.
- **Source Selector Auto-Hiding**: Fully hidden and omitted when only one source exists ($N \le 1$) for complete backward compatibility.
- **Song Multi-Mode Support**: Every Song can be Audio-only, Video-only (Music Video), or Audio + Video (Dual Mode), while maintaining a single canonical database record and slug.
- **Song Video Full Parity**: Song video supports all 6 ingestion options: Upload Video File (with FFmpeg auto-transcode), Direct Video URL, HLS Stream, YouTube, Vimeo, and External Embed.
- **Fail-Closed Access Protection**: Centralized in `MediaSourcePlaybackService` and enforced via `MultimediaAccessService::checkAccess()` (PUBLIC, LOGIN, PREMIUM). Unauthorized visitors receive zero stream or embed URLs.
- **Favorite Digital & Favorite Pay Boundaries Preserved**: Premium access remains exclusively evaluated and enforced through Favorite Digital; Favorite Pay remains payment provider only.

This update brings robust external embed playback, multi-source failover, and viewer switching to Favorite Multimedia:

- **YouTube & Vimeo Playback Normalization**: Fixed playback breakdown caused by raw watch/share URLs (`youtube.com/watch?v=...`, `youtu.be/...`, `vimeo.com/...`) by normalizing them to secure privacy-enhanced embed endpoints (`youtube-nocookie.com/embed/{id}` and `player.vimeo.com/video/{id}`) with strict hostname validation.
- **Generic External Embed with Domain Allowlist**: Added support for trusted third-party video players and external iframe embeds. Includes an admin-configurable `trusted_embed_domains` setting with wildcard matching (e.g. `*.example.com`), sandboxed iframe attributes, and loopback/private IP SSRF rejection.
- **Multiple Playback Sources per Content**: Attach multiple video/embed sources to Movies and Episodes with custom labels, active/inactive toggling, drag/reorder controls, and automatic single-default enforcement (`MediaSource::enforceSingleDefault()`).
- **Automatic Playback Failover**: Intercepts fatal HTML5 errors (error codes 2, 3, 4) and HLS fatal errors with automatic, non-blocking failover to the next available playback source, complete with cycle loop protection and toast feedback.
- **Viewer Source Switcher**: Frontend player dropdown rendered seamlessly when multiple active sources exist ($N \ge 2$), preserving playback position (`currentTime`) during transitions. Fully hidden and omitted when only 1 source exists for complete backward compatibility.
- **Centralized Playback Service (`MediaSourcePlaybackService`)**: Fail-closed access control enforcing `MultimediaAccessService::checkAccess()` (PUBLIC, LOGIN, PREMIUM).

---

## What's New in v1.0.2 — Critical Runtime Fix (Null-Safety & View Isolation)

This critical patch addresses production runtime errors on Edit Movie, Episode, and Song screens:

- **Eliminated Inline Binary Execution**: Moved `FFmpegService::isAvailable()` binary execution out of view templates (`movies.php`, `episodes.php`, `songs.php`) into safe controller pre-evaluation.
- **Hardened FFmpeg Detection**: `FFmpegService` now defensively checks `function_exists('exec')` and traps disabled functions with complete exception containment.
- **PHP 8.5 Null-Safety**: Fixed all `strtoupper()` and `htmlspecialchars()` calls across admin views to handle null or missing `source_type` and `label` without deprecations or TypeError crashes.
- **Resilient Default Source Resolution**: `MediaSource::getDefault()` now gracefully falls back to non-active sources (processing, pending, legacy) when editing content.
- **Safe Model Accessors**: Added `getSourceType()`, `getSourceLabel()`, `getUrlOrPath()`, and `getStatus()` on `MediaSource`.
- **Fail-Safe View Isolation**: The `Video / Media Stream` and `Audio Source & Media` form sections are now wrapped in local error containment, displaying an inline guidance notice (`"Media source could not be loaded. Please edit or replace the source."`) instead of crashing the entire CMS admin into a 500 error.

---

## What's New in v1.0.1 — Simple Media Publishing UX

This usability release makes everyday publishing simple and fast for Favorite CMS administrators directly from content forms:

- **Direct Video Upload & Stream Selector**: Upload video files (MP4, WebM, MKV, MOV, AVI) or enter direct MP4/WebM, HLS (`.m3u8`), YouTube, or Vimeo links directly in Add/Edit Movie and Add/Edit Episode.
- **Direct Audio Publishing**: Upload audio tracks (MP3, M4A, FLAC, WAV, AAC, OGG) or enter streaming audio URLs directly in Add/Edit Song.
- **Direct Artwork & Subtitles**: Upload posters, backdrops, episode thumbnails, song covers, and subtitle tracks (`.vtt`, `.srt`) without navigating away.
- **Quick Action Workflow**: Immediate `🚀 Publish Now`, `📝 Save Draft`, and `📅 Schedule` buttons.
- **Auto-Upserting**: Intelligently updates existing default `MediaSource` rows on edits without creating database duplicates.
- **No-Media Safety Protection**: Prevents accidental publication of titles with 0 media sources by automatically reverting to draft with a clear notification; frontend player views render a clean "Media Coming Soon" placeholder.
- **Media Status Badges & Empty States**: Color-coded badges (`Ready`, `Processing`, `No Media`, `Failed`) on admin tables, plus friendly empty-state cards across movies, series, episodes, and songs.
- **Advanced Sources**: Advanced source configuration remains fully accessible via the newly labeled "Advanced Sources" menu.

---

## Core Media Capabilities

### Content Management & Taxonomies
- **Feature Movies**: Complete movie catalogs, genres, director, cast, trailer, multi-quality video sources, multi-language subtitles, and download policies.
- **Web Series & Episodes**: Complete hierarchy (Series &rarr; Seasons &rarr; Episodes) with granular access mode inheritance and episode overrides.
- **Songs & Audio Collections**: Standalone tracks, artist profiles, music albums, synchronized lyrics, and duration tracking.
- **Audio Playlists**: Continuous curated playlists with sequential auto-advance, track management, and cover art.
- **Taxonomies**: Dynamic genres, artists, and albums linked seamlessly across movies, series, and songs.

### Media Player & Delivery Engine
- **Universal Player**: Responsive HTML5 player supporting MP4, WebM, HLS adaptive streaming (`.m3u8`), audio tracks, and embedded sources.
- **Adaptive HLS Streaming**: Quality variants (1080p, 720p, 480p, 360p) and multi-track audio selection with HMAC-SHA256 delivery tokens.
- **Protected Downloads**: Direct file download engine enforcing access modes, download policies, and session entitlements.
- **Multi-Language Subtitles**: VTT subtitle tracks with restricted content fallbacks.
- **Localized Metadata**: Localized titles, descriptions, and user language preferences.

### Operational Automation & CLI Runners
- **Scheduled Releases**: Time-locked publishing engine and automated scheduler (`bin/release-due.php`).
- **Media Processing Pipeline**: Background video transcoding, HLS packaging, and thumbnail generation (`bin/process-media.php`).
- **Storage Management**: Dual local and S3-compatible cloud storage driver with asset migration tools (`bin/manage-storage.php`).
- **Community & Moderation**: 5-star ratings, helpful reviews, threaded comments, and admin moderation queue.
- **Analytics & Insights**: Complete play, conversion, and retention tracking.

---

## Ecosystem Boundaries & Access Control

- **Favorite Multimedia**: Evaluates content access modes (`public`, `login`, `premium`).
- **Favorite Digital**: Sole and exclusive authority for `PREMIUM` entitlement.
  - If Favorite Digital is missing or disabled: **HTTP 403 Fail-Closed**.
  - If entitlement evaluation fails or errors: **HTTP 403 Fail-Closed**.
  - If subscription is expired: **HTTP 403 Fail-Closed**.
  - If active subscription exists: **HTTP 200 Allowed**.
- **Favorite Pay**: Checkout and payment provider only. Payment success never directly unlocks Premium playback without Favorite Digital entitlement record.

---

## Verification & Installation

### Checksum Verification
To verify archive integrity:
```bash
sha256sum -c checksums.sha256
```
Expected output:
```
favorite-multimedia.zip: OK
```

### Installation into Favorite CMS
1. Download `favorite-multimedia.zip` from this release.
2. Verify package integrity against SHA-256: `d63c32e0152280fc596ad3722d114c1769d2342997ea2be925ceee80216385fb`.
3. Extract `favorite-multimedia.zip` directly into the `plugins/` directory of your Favorite CMS installation:
   - Resulting path: `plugins/favorite-multimedia/`
4. In the CMS Admin Panel, navigate to **Plugins** and click **Activate** on **Favorite Multimedia**.
5. All 28 database tables and 12 administrator permissions will be automatically configured.
