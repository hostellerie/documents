# Changelog

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

### Automated tests and packaging

- Added `tests/config_upgrade_test.php` to verify configuration upgrade idempotence and preservation of customized image limits.
- Added `tests/language_sync_test.php` to verify English/French language-key parity.
- Storage migration, configuration upgrade and language synchronization tests run under PHP 5.6 and PHP 8.1.
- Renamed the permanent packaging workflow to `.github/workflows/package-current.yml` so its filename is not tied to an old development version.
- Packaging remains non-mutating for source files and commits only the generated ZIP and lint report.
- Current development package: `documents-1.1.8.zip`.

### Installation and migration carried forward from 1.1.7

- The plugin upgrade chain runs persistent-data migration before recording the new plugin version.
- Legacy `data_documents/` content is migrated recursively to the site-specific `<basename(path_data)>-documents/` directory.
- Existing target files are never overwritten.
- The legacy directory is never deleted automatically.
- The migration is idempotent and includes a two-site multisite isolation test.
- Fresh installation safely loads `functions.inc` before storage helpers are used when required by the post-install hook.

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
- Confirmed that the Geeklog database APIs used by this stabilization line (`DB_count()` and `DB_escapeString()`) predate the Geeklog 2.1.1 compatibility floor.

### Public controller and rendering

- Removed the obsolete `preg_replace(... /e ...)` URL-linking code from document rendering.
- Fixed the `catorder` / `cat_order` category reorder bug.
- Fixed the `list_fields` / `list_groups` menu comparison.
- Added safe category/document existence checks before reading database result keys.
- Normalized edit-category, edit-field, edit-group and edit-select identifiers before SQL lookup.
- New-document routing now validates and escapes the category URL and rejects a missing category before building the edit form.
- Document editing now initializes the document structure, escapes the requested document URL for SQL, rejects missing documents before access checks, and validates the numeric category ID before loading category metadata.
- Initialized rendering accumulators and comment/OpenGraph defaults before use.
- Custom templates now prefer the multisite-safe Documents data directory and use the historical directory only as a temporary read fallback.
- Missing/invalid custom-template directories fall back to the standard plugin templates instead of constructing an arbitrary path.
- Fixed the custom-template `rtrim()` mask escaping that caused `include_html.php` to fail syntax checks on PHP 5.6, 7.4 and 8.1.
- Image previews now pass local Documents filenames to the restricted image endpoint.
- Radio rendering no longer indexes the wrong field value/select array.

### Save-path stabilization

- Document saves now normalize the category ID before SQL and reject missing categories before field processing.
- Dynamic field values no longer read `$_REQUEST[$var_name]` blindly; missing/array values are handled safely.
- Image upload detection now verifies the `$_FILES` entry and temporary filename before calling `is_uploaded_file()`.
- New document slugs are built from the normalized request source instead of reading a dynamic `$_POST` key directly.
- New document checkbox and general field values now use guarded request access.
- New document permissions are normalized through `DOCUMENTS_requestPermissions()` before value rows are inserted.
- Document-row creation now normalizes the requested status and document permissions before inserting into `documents_docs`.
- Document-row editing now normalizes the requested status and rejects an empty `doc_url` before updating `documents_docs`.
- Category saves normalize text values, permissions and numeric IDs before SQL while preserving an existing Maps association when Maps is unavailable.
- Field saves normalize delete/edit identifiers, category/order/select IDs and permissions before SQL.
- Group saves normalize name/help values and cast the group ID before SQL.
- Select-option saves normalize name/value/group/order values and cast the option ID before SQL.

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

- Confirm the PHP 5.6/7.4/8.1 lint workflow completes successfully on the final 1.1.2 commit.
- Run manual functional tests on Geeklog 2.1.1 and 2.2.2 before considering 1.1.2 complete.
- Broader CSRF/right semantics remain scheduled for the dedicated rights/security milestone so 1.1.2 does not alter legacy authorization behavior unexpectedly.

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
