# Changelog

## 1.1.2 — PHP and logic stabilization in progress

Compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1

### Compatibility layer

- Added `include_compat.php` for stabilization helpers shared by the 1.1.x line.
- Added named document status constants for inactive, active, draft and submission states.
- Added `DOCUMENTS_requestValue()` and `DOCUMENTS_requestInt()` to avoid undefined-index notices and normalize numeric identifiers.
- Added `DOCUMENTS_requestPermissions()` for safe handling of Geeklog permission arrays or already-normalized permission values.
- Added `DOCUMENTS_initializeRequestDefaults()` as a compatibility safety net for the legacy public controller; the public entry point invokes it before `include_html.php` so missing request keys do not generate PHP 8.1 warnings.
- Added `DOCUMENTS_linkifyUrls()` based on `preg_replace_callback()` as the PHP 5.6–8.1 replacement for the historical `preg_replace(... /e ...)` implementation.
- Added strict custom-template name validation.
- Added `DOCUMENTS_customTemplateDir()` for the new site-specific multisite-safe template location.
- Added `DOCUMENTS_customTemplateReadDir()` so existing installations can temporarily read legacy `data_documents/templates/` content until the physical 1.1.7 migration.
- The public entry point now loads the compatibility layer before `include_html.php`.
- Plugin development metadata is now 1.1.2.

### Public controller and rendering

- Removed the obsolete `preg_replace(... /e ...)` URL-linking code from document rendering.
- Fixed the `catorder` / `cat_order` category reorder bug.
- Fixed the `list_fields` / `list_groups` menu comparison.
- Added safe category/document existence checks before reading database result keys.
- Normalized edit-category, edit-field, edit-group and edit-select identifiers before SQL lookup.
- Initialized rendering accumulators and comment/OpenGraph defaults before use.
- Custom templates now prefer the multisite-safe Documents data directory and use the historical directory only as a temporary read fallback.
- Missing/invalid custom-template directories fall back to the standard plugin templates instead of constructing an arbitrary path.
- Image previews now pass local Documents filenames to the restricted image endpoint.
- Radio rendering no longer indexes the wrong field value/select array.

### List rendering

- Updated `include_lists.php` metadata to 1.1.2.
- Initialized category/document/group/select callback return values and list SQL accumulators to avoid PHP 8.1 undefined-variable warnings.
- Added the missing `$_USER` global in the category callback.
- Category lookup is validated before its fields are read and its URL is escaped before use in legacy SQL.
- Select-group identifiers are normalized to integers before being appended to SQL.
- Category and document image previews now pass only a local basename to the restricted Documents image endpoint; obsolete TimThumb `q` and `zc` parameters are no longer emitted.
- Missing `image`/`marker` keys in submission and draft list rows are handled without undefined-array-key warnings.
- Category Maps autotags and document marker autotags are emitted only when Maps is actually available.

### Optional integrations

- Public Maps rendering now uses `DOCUMENTS_hasMaps()` and performs no Maps query/output when Maps is unavailable.
- Google Maps JavaScript is loaded only while rendering an actual marker field and now uses HTTPS without the obsolete `adsense` library.
- Public MediaGallery rendering now uses `DOCUMENTS_hasMediaGallery()` and performs no MediaGallery include/query when unavailable.
- MediaGallery thumbnails are rendered from MediaGallery's own thumbnail URL rather than being sent through the Documents local-only image endpoint.
- Existing marker/album values are preserved when their optional plugin is inactive during document editing.
- Forged attempts to create unavailable optional field types are rejected by the controller.

### Upload and marker stabilization

- Reworked image upload bookkeeping so filenames, input controls and field metadata remain synchronized when only some image fields contain an upload.
- Empty upload batches now return cleanly without passing undefined filename arrays to Geeklog's upload class.
- Upload-generated filenames are restricted to a safe local basename before the Geeklog upload class performs MIME/dimension validation.
- Fixed a marker-save bug that could replace a Documents field ID with the returned Maps marker ID.
- `mkid` is now treated as an alphanumeric/string identifier instead of a numeric request value.
- Marker persistence now guards request keys, quotes marker IDs in SQL and skips Maps work entirely when Maps is unavailable.

### Test packaging

- Test packaging runs PHP syntax checks against PHP 5.6, 7.4 and 8.1 before creating the ZIP.
- Development-only `.github/` and `tools/` files are excluded from the installable archive.
- Test artifact name: `documents-1.1.2-test.zip`.

### Remaining 1.1.2 work

- Continue the request/SQL audit in the remaining save paths for malformed-input handling and stronger per-handler normalization.
- Confirm the PHP 5.6/7.4/8.1 lint workflow completes successfully on the final 1.1.2 commit.
- Run manual functional tests on Geeklog 2.1.1 and 2.2.2 before considering 1.1.2 complete.

## 1.1.1 — Security, optional integrations and multisite storage

Compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1

### Security and privacy

- Removed unsolicited install telemetry email.
- Removed unsolicited upgrade telemetry email.
- Removed the unused admin TimThumb copy.
- Replaced the public TimThumb endpoint with a local-only image preview endpoint.
- Remote image fetching and arbitrary path traversal are not allowed by the new image endpoint.
- Restricted administrative AJAX actions to users with `documents.admin` rights.
- Added explicit JSON responses and safer numeric filtering to admin AJAX.

### Optional integrations

- Added centralized Maps and MediaGallery availability helpers.
- Maps controls are hidden when Maps is inactive or unavailable.
- MediaGallery album controls are hidden when MediaGallery is inactive or unavailable.
- Marker and album field types are not offered when their corresponding plugin is unavailable.
- Google Maps JavaScript is only loaded by the edit form when a marker field actually requires it.
- Google Maps loading now uses HTTPS and no longer requests the obsolete `adsense` library.

### Multisite and persistent data

- Added `DOCUMENTS_dataDir()`.
- The persistent Documents data directory is derived from the current site's `$_CONF['path_data']` as a sibling `<basename(path_data)>-documents/` directory.
- Added `DOCUMENTS_legacyDataDir()` for future migration from the historical `data_documents/` location.
- The physical idempotent migration of existing persistent data is scheduled for the 1.1.7 upgrade work.

### Compatibility and cleanup

- Updated plugin metadata to 1.1.1 with Geeklog 2.1.1 as the minimum supported Geeklog version.
- Added runtime compatibility checks for PHP 5.6 through 8.1 and Geeklog 2.1.1 through 2.2.2.
- Updated configuration defaults and entry-point headers.
- Removed several uninitialized-variable and unsafe request assumptions from the edited code paths.
- Fixed the comment notification username assignment bug.
- Added README and ROADMAP documentation.

## Still scheduled before 1.2.0

- complete migration of custom templates from the old data directory;
- broader CSRF coverage for category/field/list mutations;
- remaining PHP warnings and request/SQL cleanup;
- data-integrity and orphan-record checks.
