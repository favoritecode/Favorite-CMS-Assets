# Favorite CMS Assets — Asset Conventions

This document establishes the official technical standards, naming rules, format conventions, and lifecycle policies for all assets stored in **Favorite-CMS-Assets**.

---

## 1. Naming Conventions

All new files and directories must adhere to strict, predictable naming standards.

### Core Rules
1. **Lowercase Only**: Always use lowercase alphanumeric characters (`a-z`, `0-9`).
2. **Kebab-Case**: Separate multi-word names with hyphens (`-`). Never use spaces, underscores (`_`), camelCase, or PascalCase for filenames.
3. **Descriptive & Explicit**: Names must clearly describe the content and purpose of the asset.
4. **No Ephemeral / Version Suffixes**: Never use suffixes like `-v2`, `-new`, `-final`, `-latest`, `-test`, or `-edited`. Git provides canonical version control.
5. **Preserve Existing Assets**: Do **not** rename existing valid assets merely to enforce stylistic preferences if doing so would break external references or create unnecessary diff churn.

### Good vs. Bad Examples

| Status | Filename | Rationale |
| :--- | :--- | :--- |
| **Good** | `favorite-cms-logo-horizontal.svg` | Clear, lowercase, hyphen-separated, descriptive. |
| **Good** | `payment-confirmed.svg` | Clear domain role within plugin icons. |
| **Good** | `dashboard-overview-dark.png` | Describes view and state clearly. |
| **Good** | `hero-abstract.svg` | Describes theme asset function. |
| **Bad** | `Logo_Final_v2.PNG` | Uppercase, underscore, version suffix, uppercase extension. |
| **Bad** | `screen 1.png` | Contains spaces and vague numbering. |
| **Bad** | `new-icon-test.svg` | Ephemeral test name. |
| **Bad** | `COD_illus.svg` | Mixed case, acronym, abbreviation. |

---

## 2. File Format Standards

### Vector Assets (SVGs) — Primary Standard
Vector SVG is the preferred format for logos, brand marks, icons, UI decorations, and spot illustrations.

- **Valid & Clean**: Must be well-formed, valid XML/SVG with clean syntax.
- **Explicit ViewBox**: Every SVG must declare an explicit `viewBox` attribute (e.g., `viewBox="0 0 24 24"` for icons, `viewBox="0 0 400 300"` for illustrations).
- **Self-Contained**: Must not reference external URLs, remote fonts, or local filesystem resources.
- **Theme-Agnostic Icons**: System icons in `icons/` must use `fill="none"` and `stroke="currentColor"` so they dynamically inherit text color.
- **Palette Alignment**: Brand and plugin illustrations should reference the tokens established in [`design/design-tokens.md`](../design/design-tokens.md):
  - Primary Brand Blue: `#2563EB`
  - Vivid Sky Accent: `#0EA5E9`
  - Success / Financial Emerald: `#10B981`
  - Warning / Stock Amber: `#F59E0B`
  - Error / Alert Red: `#EF4444`
  - Dark Slate Neutral: `#0F172A`
  - Background Neutral: `#F8FAFC`

### Raster Assets (PNG, WebP, JPG)
Raster formats are reserved for photographic media, complex realistic graphics, and user interface screenshots.

- **PNG**: Used for crisp UI screenshots, transparent logos, and lossless icons where SVG cannot be used.
- **WebP / JPG**: Used for photographic demo content, sample blog thumbnails, and avatar placeholders.
- **Resolution**:
  - Full-screen dashboard captures: 1920×1080 or 1440×900 at 72–144 DPI.
  - Dialog / modal / component captures: 800×600 or 1200×800.
  - Avatars: 256×256 or 512×512 square.
  - Article thumbnails: 1200×630 (standard OpenGraph 1.91:1 ratio).

---

## 3. Source Preservation & Derivatives

### The Master Asset Principle
- **Master Files**: Original, unrasterized vector assets (e.g. Master SVG or `.ai` vectors in `internal/`) must always be preserved.
- **Derivatives**: Raster exports (PNGs), web-optimized SVGs, or low-resolution thumbnails are *derived* from the master asset.
- **Rule**: Never overwrite or delete an editable master vector file simply to create an optimized derivative. Store derivatives alongside masters or in designated subdirectories.

### Example Derivative Structure
```text
brand/
└── logo/
    ├── favorite-cms-logo.svg              # Master vector source
    ├── favorite-cms-logo.png              # High-resolution raster export
    ├── favorite-cms-logo-horizontal.svg   # Horizontal layout vector
    └── favorite-cms-logo-horizontal.png   # Horizontal layout raster
```

---

## 4. Screenshot Standards & Privacy Sanitization

When adding screenshots to `screenshots/` or plugin/theme documentation:

1. **Crisp Rendering**: Use modern retina/high-DPI capture with consistent browser zoom (100%).
2. **Clean Frame**: Capture clean application windows without desktop clutter, operating system taskbars, personal browser bookmarks, or unrelated tabs.
3. **Mandatory Sanitization**:
   - **Zero PII**: Use fictional customer names (e.g., "Jane Doe", "Rahim Ahmed") and fictional emails (e.g., `user@example.com`).
   - **Zero Live Secrets**: Mask or blur all API keys, merchant credentials, bearer tokens, and session identifiers.
   - **Fictional Order Numbers**: Use clean synthetic identifiers (e.g., `ORD-2026-001`).
   - **Safe Domains**: Use `example.com`, `localhost`, or official staging domains (`favoritecms.test`).

---

## 5. Demo Content Standards

When adding demo media to `demo-content/`:

1. **Royalty-Free Only**: All media must be openly licensed (Creative Commons Zero, Unsplash License, or custom open-source assets created by the Favorite CMS team).
2. **No Proprietary Brand Imagery**: Do not use copyrighted commercial product photos, trademarked logos, or recognizable private individuals without written consent.
3. **Optimized File Size**: Demo images should be web-optimized (compressed WebP/JPG under 350 KB per image) to keep the Git repository lightweight.

---

## 6. Asset Lifecycle: Adding, Updating, & Retiring Assets

### Adding a New Asset
1. Check the existing repository to ensure an identical or suitable asset does not already exist.
2. Identify the correct destination folder (`brand/`, `icons/`, `plugin-assets/<plugin>/`, `theme-assets/<theme>/`, etc.).
3. Name the file using lowercase kebab-case.
4. Verify SVG validity or compress raster formats.
5. Update the directory's `README.md` to register the new asset if the directory maintains an explicit asset index.

### Updating an Existing Asset
1. If modifying a vector asset, preserve the existing coordinate system, canvas viewBox, and semantic classes where possible to prevent breaking consumer references.
2. If updating an image, overwrite the existing file in place to maintain link integrity across documentation.
3. Do not create a second file with a version suffix (e.g., `icon-v2.svg`).

### Retiring an Asset
1. Verify that no active documentation, repository README, or demo references the asset.
2. Remove the asset via standard Git removal (`git rm`).
3. Note the retirement in the commit message.
