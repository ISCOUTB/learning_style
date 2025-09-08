# Learning Style Block - Releases

This document describes the automated release system for the Learning Style block.

## 🚀 Automated Release System

### Release Workflow (`release.yml`)

**Triggered by:** Creating and pushing a new tag with format `v*` (e.g., `v1.2.0`)

**Actions performed:**
1. **Version Detection** - Extracts version from `version.php`
2. **Package Creation** - Creates clean ZIP package excluding development files
3. **GitHub Release** - Creates formal release with detailed changelog
4. **Asset Upload** - Attaches ZIP file to the release

### Development Build Workflow (`build.yml`)

**Triggered by:** 
- Push to `main` or `develop` branches
- Pull requests to `main` branch

**Actions performed:**
1. **Package Building** - Creates development package with branch/commit info
2. **Artifact Storage** - Stores package as GitHub Actions artifact (30 days)
3. **Structure Validation** - Verifies all required Moodle plugin files exist
4. **Syntax Checking** - Runs PHP syntax validation on all PHP files

## 📦 Creating a New Release

### Step 1: Update Version
Edit `version.php` to increment the version numbers:
```php
$plugin->version = 2025090806;  // Increment build number
$plugin->release = '1.2';       // Update release version
```

### Step 2: Create and Push Tag
```bash
git add version.php
git commit -m "Release v1.2"
git tag v1.2
git push origin main
git push origin v1.2
```

### Step 3: Automatic Release
The GitHub Action will automatically:
- Detect the new tag
- Create a release package
- Publish the release with detailed notes
- Upload the installable ZIP file

## 📋 Release Package Contents

The automated release includes:
- All PHP source files
- Language files (`lang/`)
- Database definitions (`db/`)
- Styles and assets (`pix/`, `dashboard/`)
- Configuration files

**Excluded from release:**
- `.git` directory and Git files
- `.github` workflows directory
- Documentation files (`*.md`)
- Test files (`test_*`)
- Log files (`*.log`)

## 🔍 Package Validation

Each build automatically validates:
- ✅ Required Moodle plugin files exist
- ✅ PHP syntax is correct
- ✅ Plugin structure follows Moodle standards
- ✅ Version information is properly formatted

## 📊 Release Features

Each release includes:
- **Automatic versioning** from `version.php`
- **Detailed release notes** with feature descriptions
- **Installation instructions**
- **Compatibility information**
- **Security and permissions overview**
- **ZIP package** ready for Moodle installation

## 🏷️ Version Numbering

- **Major releases:** `v2.0`, `v3.0` (breaking changes)
- **Minor releases:** `v1.1`, `v1.2` (new features)
- **Patch releases:** `v1.1.1`, `v1.1.2` (bug fixes)

## 📈 Development Artifacts

Development builds are stored as GitHub Actions artifacts:
- **Naming:** `learning-style-dev-{branch}-{commit}`
- **Retention:** 30 days
- **Access:** Available in Actions tab for collaborators
- **Content:** Same as release but with development metadata
