# Changelog

## 1.1.1 — Stabilization in progress

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

### Still scheduled before 1.2.0

The following historical code is deliberately handled in later 1.1.x stabilization steps rather than being mixed into 1.1.1:

- `include_html.php` logic/PHP cleanup, including the obsolete `preg_replace(... /e ...)` URL conversion.
- category reorder `catorder` / `cat_order` bug.
- user-menu `list_fields` / `list_groups` comparison bug.
- complete migration of custom templates from the old data directory.
- broader CSRF coverage for category/field/list mutations.
- full upload workflow audit and remaining PHP warnings.
- data-integrity and orphan-record checks.
