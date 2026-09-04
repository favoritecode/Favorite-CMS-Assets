# Favorite CMS Assets — Git Workflow & Multi-PC Synchronization

## 1. Canonical Source-of-Truth Principle

```text
Local PC 1                 GitHub Repository (Canonical Truth)                 Local PC 2
┌─────────────┐            ┌─────────────────────────────────┐            ┌─────────────┐
│ Working Tree│ ──push──>  │ https://github.com/favoritecode │  <──pull── │ Working Tree│
│ (Temporary) │            │ Favorite-CMS-Assets.git (main)  │            │ (Temporary) │
└─────────────┘            └─────────────────────────────────┘            └─────────────┘
```

- **GitHub is the canonical source of truth**: The remote `main` branch on GitHub represents the authoritative state of all visual assets and documentation.
- **Local PCs are working copies**: Any machine (development PC, laptop, build agent, or AI sandbox) holds only a working copy.
- **Portability Rule**: Any developer or AI on any PC must be able to clone the repository from GitHub and immediately understand, use, and extend it without relying on conversational memory or local-only notes.

---

## 2. Onboarding a New PC (Fresh Setup)

When setting up a new developer machine or AI workspace:

### Step 1: Install Git
Ensure Git is installed and available in your environment:
```bash
git --version
```

### Step 2: Clone the Repository
Clone the canonical GitHub repository:
```bash
git clone https://github.com/favoritecode/Favorite-CMS-Assets.git
cd Favorite-CMS-Assets
```

### Step 3: Verify Working Tree
Verify that the clone is clean and tracking `origin/main`:
```bash
git status
git branch -vv
```

### Step 4: Read the Entry Point
Open and read `README.md` and `docs/README.md` to understand the repository structure and conventions before making any modifications.

---

## 3. Daily Synchronization Workflow

When resuming work on an existing local clone:

### 1. Pull Latest Remote State
Always fetch and pull before making new edits to avoid diverging from GitHub:
```bash
git checkout main
git pull --ff-only origin main
```

### 2. Inspect Existing Structure Before Adding Files
Before creating a new file, inspect the existing directory:
```bash
# Example: Check if an icon already exists
ls icons/commerce/
```

### 3. Make Minimal, Targeted Changes
Add or update the necessary assets and update the corresponding `README.md`.

### 4. Check Diff and Staging Status
```bash
# Check whitespace and line ending errors
git diff --check

# Check modified and untracked files
git status

# Inspect exact changes
git diff
```

### 5. Stage Only Intended Files
Never use blanket `git add .` if unintended scratch files, logs, or editor configs exist. Stage explicitly:
```bash
git add docs/
git add plugin-assets/favorite-pay/
```

### 6. Commit with Semantic Message
Use clear, conventional commit prefixes:
- `docs:` Documentation additions or updates.
- `feat:` New visual assets, icons, or illustrations.
- `fix:` Asset corrections, SVG path fixes, or broken link fixes.
- `refactor:` Organization, standardization, or file cleanups without breaking changes.

Example:
```bash
git commit -m "docs: establish central asset conventions and repository guide"
```

### 7. Push to Canonical Origin
```bash
git push origin main
```

### 8. Verify Remote State
```bash
git status
# Confirm: "Your branch is up to date with 'origin/main'."
```

---

## 4. Conflict Resolution & Hygiene

- **Linear History**: Strive to maintain a clean, linear Git history on `main`.
- **Pre-Push Validation**: Always run `git diff --check` prior to committing to ensure no trailing whitespace or corrupt line endings are introduced.
- **Accidental Files**: Never commit `.DS_Store`, `Thumbs.db`, `.env`, editor settings (`.idea/`, `.vscode/`), or temporary cache files. These are ignored by `.gitignore`.
