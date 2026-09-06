# Favorite Multimedia — Production Release Package

This directory contains the authoritative, verified production release archive and checksum verification metadata for **Favorite Multimedia**.

---

## Release Package Summary

| Property | Value |
| :--- | :--- |
| **Release Version** | `v1.0.0` |
| **Package File** | `favorite-multimedia.zip` |
| **Package Size** | 321,867 bytes |
| **SHA-256 Checksum** | `74f39102b3ab1fd8f41dc4c4478e117921d48cb846945d7e0786b4b3e65c8cdb` |
| **Source Repository** | `favoritecode/Favorite-CMS-Universal` |
| **Locked Source Commit** | `c7f89bc920f3bee77cc268b0c6a799f75ac938e7` |
| **Test Suite Verification** | 252 tests, 1,610 assertions (0 failures, 0 errors) |
| **Target Platform** | Favorite CMS Core (`Favorite-CMS-Universal`) |
| **Plugin Identifier** | `favorite-multimedia` |

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
2. Verify package integrity against SHA-256: `74f39102b3ab1fd8f41dc4c4478e117921d48cb846945d7e0786b4b3e65c8cdb`.
3. Extract `favorite-multimedia.zip` directly into the `plugins/` directory of your Favorite CMS installation:
   - Resulting path: `plugins/favorite-multimedia/`
4. In the CMS Admin Panel, navigate to **Plugins** and click **Activate** on **Favorite Multimedia**.
5. All 28 database tables and 12 administrator permissions will be automatically configured.
