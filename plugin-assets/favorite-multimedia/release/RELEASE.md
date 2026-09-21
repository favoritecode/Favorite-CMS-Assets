# Favorite Multimedia v1.0.9

### Release Notes
- **Author Dashboard & Profile Compatibility**: Native PHP string title ('Multimedia') and native array submenus ensure 100% compatibility with Favorite CMS core admin shell and prevent Author dashboard 500 errors.
- **Admin Appearance Mode**: Canonical support for AUTO, LIGHT, and DARK color modes scoped strictly to multimedia admin UI.
- **Active Theme Architecture**: Preserved active Favorite CMS theme header and footer ownership. The plugin renders cleanly within any active theme.
- **Music Spotlight & Song Routing**: Fixed Music Spotlight card targets to point to canonical `/multimedia/song/{slug}` routes with zero 404s.
- **Public Music Detail UI Polish**: Upgraded Song and Album detail views with modern streaming platform hero layouts, pill badges, artist sublines, action buttons, monospaced tabular tracklist durations, and polished lyrics containers.
- **Artwork Fallback**: Added graceful inline error handling to show styled placeholder icons (`🎵` / `💿`) for missing or broken artwork, eliminating broken image icons.
- **Audio Playback Engine**: Fixed audio source resolution for Audio Only and Audio + Video tracks, prevented invalid audio playback for Video Only tracks, added clearer error messages, and resolved duplicate toast notifications.
- **Theme & Core Boundaries**: Standalone Favorite Multimedia Theme remains v1.0.2. Favorite CMS core, Favorite Digital, and Favorite Pay remain completely untouched.

### Package Checksums (SHA-256)
```
f4382d29e44197789c0e39f46a827d730b4ea0ef5068a7dc06ad2710f1a1d720  favorite-multimedia.zip
f4382d29e44197789c0e39f46a827d730b4ea0ef5068a7dc06ad2710f1a1d720  favorite-multimedia-v1.0.9.zip
```