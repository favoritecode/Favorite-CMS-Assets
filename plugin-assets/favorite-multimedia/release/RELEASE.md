# Favorite Multimedia — Production Release Package

This directory contains the authoritative, verified production release archive and checksum verification metadata for **Favorite Multimedia**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | `v1.0.2` |
| **Package File** | `favorite-multimedia.zip` |
| **Package Size** | 334,474 bytes |
| **SHA-256 Checksum** | `96e24f3e27b8754f9e0f6f54029e903ef5d11281d504d7bdb7adb951688e408d` |
| **Source Repository** | `favoritecode/Favorite-CMS-Universal` |
| **Test Suite Verification** | 266 tests, 1,695 assertions (0 failures, 0 errors) |
| **Target Platform** | Favorite CMS Core (`Favorite-CMS-Universal`) |
| **Plugin Identifier** | `favorite-multimedia` |

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
2. Verify package integrity against SHA-256: `9c43260b5ac15c7d97c6e2dedef3af8c4547f00129e40b081b74976ddc7ce72b`.
3. Extract `favorite-multimedia.zip` directly into the `plugins/` directory of your Favorite CMS installation:
   - Resulting path: `plugins/favorite-multimedia/`
4. In the CMS Admin Panel, navigate to **Plugins** and click **Activate** on **Favorite Multimedia**.
5. All 28 database tables and 12 administrator permissions will be automatically configured.
