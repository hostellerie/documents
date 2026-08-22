# Changelog

## 1.1.10 — Configuration upgrade and public statistics option

Compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1

### Configuration upgrade

- Added an explicit `DOCUMENTS_updateConfig_1_1_10()` upgrade step.
- The 1.1.10 upgrade re-registers missing display/integration settings idempotently.
- Existing customized values are preserved, including What's New settings and statistics visibility.
- This ensures sites already recorded as Documents 1.1.9 receive the new configuration entries during upgrade.

### Statistics visibility

- Extended `stats_visibility` with a fourth level: everyone, including anonymous visitors.
- Visibility levels are now: hidden, administrators only, authenticated users plus administrators, and everyone including anonymous visitors.
- The default remains administrators only so upgrading does not expose statistics automatically.
- Added English and French labels for the anonymous/public option.
- Added regression tests covering anonymous, authenticated and administrator visibility behavior.

### Carried forward from the 1.1.9 development cycle

- Text fields can normalize display case without changing stored values.
- Geeklog What's New can list recent Documents entries with configurable interval and limit.
- The Documents home page can show a lightweight statistics block.
- Geeklog search receives a meaningful description sourced from document text fields.

## 1.1.9 — Release candidate

Compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1

### Release-candidate checks

- Added `tests/release_candidate_test.php` for cross-cutting stabilization invariants.
- The release-candidate checks verify the supported Geeklog and PHP ranges.
- The checks verify multisite-safe persistent storage and the non-overwriting legacy-data migration guard.
- The checks verify CSRF protection on mutating controller routes and `documents.admin` protection on admin AJAX.
- The checks verify that Maps and MediaGallery remain optional integrations guarded by centralized availability helpers.
- The checks verify removal of the legacy TimThumb runtime/files and look for obsolete direct install/upgrade telemetry mail patterns.
- The previous storage-migration, configuration-upgrade and language-synchronization tests remain mandatory.
- Release-candidate checks run under PHP 5.6 and PHP 8.1 before packaging.

### Document workflow and authorization hardening

- Added centralized document visibility and edit guards.
- Active documents continue to use normal Geeklog permissions.
- Drafts are restricted to their owner or a Documents administrator.
- Submitted documents are frozen for normal users while awaiting moderation.
- Non-admin draft/submission lists are filtered by `owner_id` before rendering.
- Search results remain restricted to active documents and normal Geeklog permissions.
- Existing document edit/save requests are bound to the document's real category; forged `cid`/category values are rejected.
- Non-admin users cannot self-publish by forging the `active` request value.
- Document workflow states are normalized server-side before legacy save handlers run.
- Non-admin POST values for `owner_id`, `group_id` and `perm_*` are ignored.
- Existing documents preserve their stored owner, group and permissions during non-admin edits.
- New non-admin documents receive the current user as owner, the Documents Admin group and the configured default permissions.
- The same trusted values are propagated to dependent save paths such as Maps markers.
- Added `tests/document_visibility_test.php` and `tests/document_edit_security_test.php`.

### Packaging

- Development metadata is now 1.1.9.
- The permanent workflow targets `modernize-1.1.9` and builds `documents-1.1.9.zip` only after automated checks pass.
- Packaging remains non-mutating for source files and commits only the generated ZIP and lint report.
- Automated checks do not replace the manual Geeklog compatibility matrix required before 1.2.0.

### Administration and localization carried forward from 1.1.8

- The data-integrity administration report now uses language keys instead of hard-coded English labels.
- English and French include matching labels for the integrity audit, duplicate slugs, orphan records, image consistency and administration navigation.
- The English/French parity test covers these keys.

### Manual validation still required

Before 1.2.0, manually validate the release candidate across the supported and viable environment matrix, including:

- Geeklog 2.1.1 through 2.2.2;
- PHP 5.6 through 8.1 where the Geeklog/PHP combination itself is viable;
- single-site and multisite installations;
- two multisite instances with distinct `$_CONF['path_data']` values;
- clean installation and upgrade from an existing Documents installation;
- migration from legacy `data_documents/` storage;
- Maps active, inactive and not installed;
- MediaGallery active, inactive and not installed;
- categories, fields, documents, drafts, submissions, permissions, search, images, comments and deletion.

## 1.1.8 — Configuration, language and cleanup

Compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1

### Configuration

- Moved image width, height and file-size limits into Geeklog configuration.
- Added upgrade logic that inserts only missing configuration records.
- Existing customized image limits are preserved during upgrade.
- Added runtime fallback values for incomplete historical configurations.
- Added an explicit 1.1.8 upgrade step in the stabilization upgrade chain.
- Kept a compatibility alias for development builds that used the temporary `DOCUMENTS_updateConfig_1_1_2()` helper name.

### Language synchronization

- Synchronized English and French language keys.
- Added the missing `default_permissions` configuration label in both languages.
- Normalized configuration fieldset and selection labels.
- Cleaned several obsolete or awkward English and French labels without changing their keys.
- Normalized indentation in the maintained language files.
- Removed the obsolete closing `?>` tags from the maintained language files.
- Localized the data-integrity administration report in English and French.

### Automated tests and packaging

- Added `tests/config_upgrade_test.php` to verify configuration upgrade idempotence and preservation of customized image limits.
- Added `tests/language_sync_test.php` to verify English/French language-key parity.
- Storage migration, configuration upgrade and language synchronization tests run under PHP 5.6 and PHP 8.1.
- Renamed the permanent packaging workflow to `.github/workflows/package-current.yml` so its filename is not tied to an old development version.
- Packaging remains non-mutating for source files and commits only the generated ZIP and lint report.

### Installation and migration carried forward from 1.1.7

- The plugin upgrade chain runs persistent-data migration before recording the new plugin version.
- Legacy `data_documents/` content is migrated recursively to the site-specific `<basename(path_data)>-documents/` directory.
- Existing target files are never overwritten.
- The legacy directory is never deleted automatically.
- The migration is idempotent and includes a two-site multisite isolation test.
- Fresh installation safely loads `functions.inc` before storage helpers are used when required by the post-install hook.

## 1.1.2 — PHP and logic stabilization

Compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1

### Compatibility layer

- Added `include_compat.php` for stabilization helpers shared by the 1.1.x line.
- Added named document status constants for inactive, active, draft and submission states.
- Added guarded request helpers to avoid undefined-index notices and normalize numeric identifiers.
- Added permission normalization helpers for Geeklog permission arrays.
- Added a compatibility safety net for legacy controller request keys.
- Replaced historical executable regular-expression URL linking with `preg_replace_callback()`.
- Added strict custom-template name validation and multisite-safe template lookup helpers.

### Public controller and rendering

- Fixed category reorder and list-menu comparison bugs.
- Added category/document existence checks before reading database results.
- Hardened route identifiers before SQL lookup.
- Initialized rendering accumulators and comment/OpenGraph defaults.
- Custom templates prefer multisite-safe persistent storage with a temporary legacy read fallback.
- Missing or invalid custom-template directories fall back to standard plugin templates.
- Image previews use the restricted local Documents image endpoint.

### Save-path stabilization

- Hardened category, document, field, group and selection save paths.
- Guarded dynamic field/request access.
- Improved image-upload bookkeeping and validation.
- Normalized document status and permission values.
- Preserved existing optional Maps/MediaGallery values when integrations are unavailable.

### Optional integrations

- Maps and MediaGallery use centralized availability helpers.
- Their resources, queries and controls are skipped when the corresponding plugin is unavailable.
- Google Maps loading uses HTTPS and omits the obsolete `adsense` library.

## 1.1.1 — Security, optional integrations and multisite storage

Compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1

### Security and privacy

- Removed unsolicited install telemetry email.
- Removed unsolicited upgrade telemetry email.
- Removed TimThumb and replaced the public image path with a local-only image preview endpoint.
- Remote image fetching and arbitrary path traversal are not allowed by the replacement image endpoint.
- Restricted administrative AJAX actions to users with `documents.admin` rights.
- Added explicit JSON responses and safer numeric filtering to admin AJAX.

### Optional integrations

- Added centralized Maps and MediaGallery availability helpers.
- Maps and MediaGallery controls are hidden when their plugins are inactive or unavailable.
- Marker and album field types are not offered without their corresponding integration.

### Multisite and persistent data

- Added `DOCUMENTS_dataDir()` based on each site's `$_CONF['path_data']`.
- Added `DOCUMENTS_legacyDataDir()` for migration from historical `data_documents/` storage.

## Still required before 1.2.0

- complete the manual 1.1.10 release-candidate compatibility matrix;
- correct any regressions found during those tests;
- confirm the final installable archive and report are produced from the release-candidate branch.
