# Favorite Multimedia Theme v1.0.1 Release Notes

- **Version**: 1.0.1
- **Release Date**: 2026-09-09
- **Type**: Critical Bugfix & CMS Installer Compatibility
- **Requires Plugin**: Favorite Multimedia v1.0.6+
- **SHA-256**: `d904702b7ce6c3bfd6cf3950af26ccc013f8bb2b35e19c27e036ed0f404b48d3`

## Critical Fix: Favorite CMS Theme Installer Compatibility
- Corrected archive packaging structure to enforce single top-level enclosing directory (`favorite-multimedia-theme/`).
- Added canonical Favorite CMS manifest `theme.json` compliant with Favorite CMS `ThemeManager` schema.
- Added standard template hierarchy: `index.php`, `header.php`, `footer.php`, `functions.php`, `single.php`, `page.php`, `search.php`, `archive.php`, `404.php`, and `sidebar.php`.
- Generated valid 600x400 theme preview thumbnail `screenshot.png`.
- Completely resolved the issue where Favorite CMS `Appearance -> Themes` showed pseudo-themes (`Assets`, `Src`, `Views`, `Manifestjson`). Installing `favorite-multimedia-theme.zip` now creates exactly ONE installed theme: `Favorite Multimedia Theme`.

---

## Favorite Multimedia Theme v1.0.0 Release Notes (Historical)

- **Version**: 1.0.0
- **Release Date**: 2026-09-09
- **Requires Plugin**: Favorite Multimedia v1.0.6+
- **SHA-256**: `64419d881d03c0bb99b72aec027b93bf46e37c4b3c7e95a0efec204c895c9c82`

### Included Capabilities
1. **Universal Search Engine**: Movies, Series, Songs, Albums, Artists, Playlists with instant autocomplete.
2. **Personal Library Dashboard**: Continue Watching & Continue Listening with state continuity.
3. **Persistent Audio Player**: Canonical audio instance, queue management, lyrics modal.
4. **Theme Studio & Homepage Builder**: Live token preview, visual section builder, safe JSON export/import.
5. **Production Hardening**: Zero CMS core modifications, fail-closed permission gating.
