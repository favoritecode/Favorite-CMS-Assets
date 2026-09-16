# Favorite Multimedia — Production Release Package

This directory contains the authoritative, verified production release archive and checksum verification metadata for **Favorite Multimedia**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | `v1.0.7` |
| **Package File** | `favorite-multimedia.zip` (and `favorite-multimedia-v1.0.7.zip`) |
| **Package Size** | 571,649 bytes |
| **SHA-256 Checksum** | `3512b494c7b807a3c53dc8ce69f640b3b0fb2032bcf94367b7e971c8ef05dc89` |
| **Source Repository** | `favoritecode/Favorite-CMS-Universal` |
| **Test Suite Verification** | 859 tests, 4,661 assertions (0 failures, 0 errors) |
| **Target Platform** | Favorite CMS Core (`Favorite-CMS-Universal`) |
| **Plugin Identifier** | `favorite-multimedia` |

---

## What's New in v1.0.7 — Ratings UX, Comments UX, Bulk Actions, Song 500 Forensic Fix & Unified Playback

This update addresses critical engagement and administrative workflows, resolves a forensic issue on song detail pages, and introduces unified volume memory, persistent background audio, and floating video:

### 1. Interactive Star Rating & Real-Time Aggregation
- **Zero Browser Popups**: Replaced all native browser `alert()` popups across the rating system with non-intrusive, responsive `FavoriteToast` notifications.
- **In-Flight Lock Protection**: Added an in-flight submission lock (`isRatingInFlight`) that prevents duplicate or race-condition rating submissions while a request is actively processing.
- **Dynamic Average & Counter Recalculation**: Submitting a star rating immediately recalculates the visible average rating and total rating count in the DOM without requiring a full page refresh.
- **Graceful Guest Handling**: Guest visitors attempting to rate receive an informative warning toast ("Please sign in to rate this content.") and are smoothly redirected to `/login` with the return URL preserved.
- **Hardened API CSRF & Validation**: `/multimedia/api/rate` defensively verifies both `_token` and `csrf_token` session tokens, rejects unauthenticated guests with HTTP 401 JSON, and validates integer rating bounds (1 to 5).

### 2. Discussion & Comment Submission UX
- **Universal Form Availability**: The `#fmm-post-comment-form` is consistently rendered for both authenticated users and guests, providing clear sign-in prompts and return URL preservation when logged out.
- **Double-Layered Validation**: Enforces non-empty and 1,000-character limits both client-side and server-side in `MediaPlaybackController::apiSaveComment()`.
- **In-DOM Dynamic Append**: Successfully posted comments are instantly formatted and appended to `#fmm-comments-container` with animated highlight, empty placeholder removal, and comment counter increment.
- **Submission State Feedback**: Submit button is disabled with a loading state during flight (`isCommentInFlight`), preventing double-submits.

### 3. Admin Movies & Songs Multi-Select & Bulk Actions
- **Eliminated HTML5 Form Nesting**: Completely eliminated parser pointer corruption by routing single-row deletes through dedicated external hidden forms (`#fmm-movie-single-delete-form` and `#fmm-song-single-delete-form`), leaving the main table strictly enclosed by `#movies-bulk-form` and `#songs-bulk-form`.
- **Master Checkbox with Indeterminate State**: Added select-all checkbox with automatic indeterminate state handling (`checkbox.indeterminate = true`) and a live badge indicating the number of selected items.
- **Robust Bulk Operations**:
  - `publish`: Strictly verifies media readiness before publishing. Items lacking playable media stay in draft with an informative flash warning, while valid items are published.
  - `draft`: Reverts selected movies or songs back to draft status.
  - `delete`: Performs cascading deletion of sources, ratings, comments, favorites, and subtitles, protected by `MultimediaPermission::DELETE`.

### 4. Song 500 Internal Server Error Forensic Fix
- **Root Cause Resolution**: `MultimediaFrontendController::song()` previously passed an associative array from `MediaSourcePlaybackService::getPlayableSources()` into `MultimediaAccessService::checkDownloadPermission()`, which type-hinted `?MediaSource $source = null`. This caused a fatal `TypeError` (HTTP 500) whenever a song with playable sources was viewed.
- **Defensive Type Widening & Normalization**:
  - `MultimediaFrontendController::song()` now explicitly resolves a `MediaSource` model instance before calling `checkDownloadPermission()`.
  - `MultimediaAccessService::checkDownloadPermission()` and `DownloadSourceService::getDownloadOptions()` widened `$source` / `$selectedSource` parameter typing to `object|array|null` and safely normalize array representations via `MediaSource::find((int)$source['id'])`.
- **Zero Regressions**: Fully tested and validated across 9 distinct song permission and access scenarios (Audio-only, Video-only, Dual Mode, No Sources, Downloads Enabled/Disabled, Public, Login-required, Premium-required).

### 5. Unified Remembered Volume System (`window.FavoriteMediaVolume`)
- **Single Source of Truth**: Unified volume management for both audio and video players via `localStorage['fm_media_volume']` (normalized float `0.0` to `1.0`).
- **Initial First-Play Clamping**: Safe initial volume default clamped to `0.25` (25%) preventing sudden loud playback.
- **Bi-Directional Real-Time Synchronization**: Audio and video players stay synchronized in real-time across tabs and player elements without feedback loops.
- **Smart Unmute Memory**: Unmuting restores the user's previously remembered non-zero volume rather than getting stuck at silence.

### 6. Persistent Background Audio & Media Session API
- **Continuous Playback**: Playback continues uninterrupted when switching tabs, minimizing the browser window, or navigating, safeguarded against unintended `visibilitychange` pauses.
- **Media Session Integration**: Full OS/browser notification controls and hardware media key bindings (`play`, `pause`, `previoustrack`, `nexttrack`, `seekto`) with active track metadata (title, artist, album, artwork).

### 7. Floating Video & Picture-in-Picture (PiP)
- **Standard PiP Support**: Added `.fav-btn-pip` button to video controls using the standard HTML5 Picture-in-Picture API (`video.requestPictureInPicture()`).
- **Mutual Playback Exclusion**: Coordinated events (`fm:audio:play` and `fm:video:play`) ensure video pauses immediately when audio begins, and audio pauses when video starts.

### 8. Legitimate Background Audio Mode
- **Dual-Source Content Switching**: For songs configured with both video and audio streams, users can toggle "Background Audio" to switch seamlessly from video to audio playback. Strictly utilizes legitimately uploaded/configured audio sources; no third-party audio extraction or scraping.

### 9. Admin Playback Settings
- **New Playback Panel in Multimedia Settings**: Configurable `default_media_volume` (clamped 5%-50%), `remember_media_volume` (Yes/No), `enable_pip` (Yes/No), and `enable_background_audio` (Yes/No).
- **Dynamic Configuration Injection**: Injects settings to frontend players via `window.FavoriteMultimediaConfig`.

---

## What's New in v1.0.6 — Multiple Download Links & Auto Next Play

This targeted feature release introduces two major enhancements before release: **Multiple Download Links** and **Auto Next Play for Playlists & Episodes**:

### 1. Multiple Download Links Architecture & Experience

- **Scalable Relational Model (`multimedia_download_sources`)**: Full relational schema supporting multiple manual download links per content item (`movie`, `episode`, `song`) with fields for `label`, `url`, `quality`, `format`, `provider`, `sort_order`, and `is_active`.
- **Zero-Loss Migration (012)**: Seamlessly migrates existing single `download_url` records into the new table on upgrade, preserving existing links with zero data loss.
- **Unified Download Resolution Pipeline (`DownloadSourceService`)**:
  1. Priority 1: Active manual download sources ordered by `sort_order ASC, id ASC`.
  2. Priority 2: Legacy `download_url` fallback for un-migrated content items.
  3. Priority 3 & 4: Uploaded or direct playable media sources (strictly excludes embeds, YouTube, Vimeo, and HLS `.m3u8` streams).
  4. Deduplication: Eliminates identical normalized URLs across sources.
- **Fail-Closed Access Boundaries**: Fully integrated with Favorite Digital subscription entitlement evaluation (`MultimediaAccessService::checkAccess()`). Unauthorized visitors/users cannot download protected content or leak raw download URLs (`options = []`, `download_url = null`).
- **Controlled Route `/multimedia/download-source/{id}`**: Secure streaming/redirect controller with full publication verification, access enforcement, and SSRF/URL security validation (rejects `javascript:`, `data:`, `file:`, control chars, and private IP/localhost SSRF).
- **Adaptive Frontend Detail UI**: Automatically renders a single clean `Download` button when only 1 option is available (100% backward compatible UI). When multiple options exist, renders an accessible styled dropdown selector displaying quality, format, and provider badges.
- **Intuitive Admin Management**: Dynamic table in Movie, Episode, and Song admin edit views allowing administrators to add, edit, reorder, toggle, or delete download links without nested form collision. Metadata-only edits safely preserve existing download links.

### 2. Auto Next Play for Playlists & Episodes

- **Playlist Auto-Advance & Cycle Control**: Automatic sequential playback of playlist items with order preservation, repeat mode cycling, shuffle mode, safe skipping of unplayable/sourceless items, and clean termination at the end of the playlist (renders "Playlist completed" state; never loops infinitely unless repeat is active).
- **Cross-Season Episode Auto-Advance**: Seamlessly advances through episodes within the current season, and smoothly transitions from the final episode of one season to Episode 1 of the subsequent season. Safely skips draft, scheduled, or sourceless episodes with cycle loop protection (visited ID set + safety caps).
- **Fail-Closed Access Protection**: Fully integrated with Favorite Digital subscription entitlement evaluation via `MultimediaAccessService::checkAccess()`. Unauthorized visitors/users attempting to fetch the next premium episode or playlist track are blocked from receiving playable stream URLs (`player_url = null, sources = []`), with clear `LOGIN_REQUIRED` or `PREMIUM_REQUIRED` error codes.
- **Next Up Countdown Overlay**: Visual overlay displaying the upcoming episode/track thumbnail, title, season/episode badge, configurable countdown timer (3-30 seconds, or immediate), and instant `Play Now` and `Cancel` controls.
- **Browser Autoplay Rejection Handling (`safePlay`)**: Gracefully catches browser autoplay policy rejections (`NotAllowedError`) without triggering misleading source failovers, rendering a clean "Click to Play" overlay for explicit user gesture activation.
- **Admin Configuration**: New settings in Multimedia Settings to toggle Auto-Play Next (`auto_play_next`) and adjust countdown duration (`auto_next_countdown`).
- **Comprehensive Verification**: Validated with a dedicated 20-test automated suite covering all resolution pathways, authorization boundaries, loop prevention, and playback controllers. The full plugin test suite passes with 386 tests, 2,315 assertions (0 failures, 0 errors).
- **Zero Core Modifications**: ZERO changes made to Favorite CMS core (`app/`, `resources/`, `database/`), Favorite Digital, or Favorite Pay.

---

---

## What's New in v1.0.5 — Main Form Publishing, Inline External Embed & Edit Workflow Fix

This critical release fixes the production admin publishing, embed persistence, and edit lifecycle on the main Movie, Episode, and Song Add/Edit forms:

### Highlights

- **Eliminated Nested HTML Forms**: Removed inner auxiliary action forms and Add Source subforms from inside `<form id="fav_movie_form">` and the episode form. This prevents HTML5 parser pointer reset from detaching `Publish Now`, `Save Draft`, `Schedule`, `access_mode`, and subsequent fields.
- **Fixed Subform Field Overwrite Collision**: Eliminated empty `<input type="text" name="video_url">` collisions where empty subform inputs overwrote entered media URLs on form submission.
- **Full Inline External Embed & Iframe Support**: `MediaSourceResolver::extractIframeUrl()` extracts clean embed URLs from pasted `<iframe>` snippets, decodes HTML entities, and normalizes protocol-relative URLs (`//`).
- **Expanded Known Embed Providers**: Cloudflare Stream (`iframe.videodelivery.net`), Twitch, Facebook, Wistia, Rumble, Streamtape, BunnyCDN (`iframe.mediadelivery.net`), Mux, Loom, Spotify, Google Drive, and Archive.org are recognized by default with subdomain wildcard support.
- **Actionable Flash Error Reporting**: Controller now sets actionable error notices directly in `$_SESSION['flash_error']` and unsets `$_SESSION['flash_success']` if content is demoted to draft.
- **Restored Canonical Dashboard Submenu**: Registered `multimedia-dashboard` as canonical first submenu under `multimedia`, hiding only the duplicate `Multimedia` child link.
- **Fail-Closed Access Boundaries Preserved**: ZERO modifications to Favorite CMS core, Favorite Digital, or Favorite Pay. Complete test suite passes with 335 tests, 2,048 assertions (0 failures, 0 errors).

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
2. Verify package integrity against SHA-256: `3d61c77160b15706c591dd397a3ba4dbe34eb1834d33af34d420e3493328c5e7`.
3. Extract `favorite-multimedia.zip` directly into the `plugins/` directory of your Favorite CMS installation:
   - Resulting path: `plugins/favorite-multimedia/`
4. In the CMS Admin Panel, navigate to **Plugins** and click **Activate** on **Favorite Multimedia**.
5. All 28 database tables and 12 administrator permissions will be automatically configured.
