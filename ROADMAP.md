# Documents plugin roadmap

## Compatibility target

Documents is being stabilized with the following compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1
- Maps plugin: optional integration only
- MediaGallery plugin: optional integration only
- Multisite-safe persistent storage: the Documents data directory must be derived from the current site's `$_CONF['path_data']`, using a sibling directory named `<basename(path_data)>-documents`.

If Maps or MediaGallery is missing or inactive, Documents must not load their files, query their tables, enqueue their JavaScript, or render related controls/output.

For Geeklog 2.2.2 and multisite deployments, persistent Documents data must be kept outside disposable cache-style locations. The directory must be unique for every site and must also work unchanged in a normal single-site installation.

The reference logic is:

```php
function DOCUMENTS_dataDir()
{
    global $_CONF;

    $base = isset($_CONF['path_data']) ? rtrim($_CONF['path_data'], "/\\") : '';
    if ($base === '') {
        return '';
    }

    return dirname($base) . DIRECTORY_SEPARATOR
        . basename($base) . '-documents' . DIRECTORY_SEPARATOR;
}
```

Examples:

- `/home/site/data/` -> `/home/site/data-documents/`
- `/home/site/data-site2/` -> `/home/site/data-site2-documents/`

This mirrors the multisite-safe storage pattern already used by other Geeklog components such as AmazonLinks. A fixed `site-documents/` directory must not be used because it would not guarantee isolation between sites.

Existing installations using the legacy `$_CONF['path_data'] . 'data_documents/'` location must be migrated safely to the new derived directory without losing custom templates or other stored plugin data. The migration must be idempotent and preserve backward compatibility during the transition.

## 1.1.x — Stabilization work

### 1.1.1 — Critical security and optional dependencies

- Remove legacy install/upgrade telemetry email.
- Remove TimThumb and replace legacy dynamic image handling with a local-only safe image pipeline.
- Audit and secure image upload validation and paths.
- Protect admin AJAX actions with Documents admin rights and proper JSON responses.
- Centralize optional dependency checks for Maps and MediaGallery.
- Never load Maps or MediaGallery resources unless the integration is both active and required by the current document/form.
- Audit direct request input used in SQL, paths and HTML.
- Keep compatibility with PHP 5.6; do not introduce PHP 7+ syntax.
- Introduce a central multisite-safe `DOCUMENTS_dataDir()` helper derived from the current site's `$_CONF['path_data']`; do not hard-code `site-documents`.
- Retain a safe legacy lookup path until migration is complete.

### 1.1.2 — PHP and logic fixes

- Fix menu mode comparisons.
- Fix category reorder field-name mismatch.
- Initialize variables and arrays before use.
- Remove normal-use PHP notices/warnings across PHP 5.6–8.1.
- Replace numeric document-status magic values with constants.

### 1.1.3 — Data integrity

- Validate and normalize category/document slugs.
- Detect duplicate slugs before later SQL constraints.
- Detect orphan documents, values, fields and pictures.
- Make delete operations clean related data consistently.

### 1.1.4 — Permissions, forms and CSRF

- Audit documents.admin and documents.publish coverage.
- Add Geeklog CSRF token checks to all mutating admin operations.
- Ensure category/document permissions apply to listings, search and submissions.
- Verify drafts, submissions and disabled documents never leak through search.

### 1.1.5 — Images and media

- Centralize image validation/storage/thumbnail generation/deletion.
- Store persistent thumbnails instead of dynamic legacy resizing.
- Keep Documents image management independent from MediaGallery.

### 1.1.6 — Templates and interface

- Move generated markup from PHP to .thtml where practical.
- Reduce inline styles and obsolete XHTML-era markup.
- Modernize admin navigation and document-state indicators.

### 1.1.7 — Installation, upgrades and persistent-data migration

- Rework the upgrade chain into explicit version steps.
- Test clean install, uninstall/reinstall and upgrade from 1.1.0.
- Declare the supported Geeklog range accurately.
- Derive the target directory from `$_CONF['path_data']` as a sibling `<basename(path_data)>-documents/` directory.
- Migrate legacy `$_CONF['path_data'] . 'data_documents/'` content to that derived site-specific target.
- Preserve custom templates and any other persistent Documents data during migration.
- Make the migration idempotent: rerunning an upgrade must not overwrite newer files or duplicate data.
- Keep a temporary read fallback to the legacy directory only while migration compatibility is needed; new writes must target the derived site-specific directory.
- Verify one multisite instance can never read or overwrite another site's Documents data directory.
- Verify the new directory is not treated as disposable cache data in Geeklog 2.2.2 cleanup routines.

### 1.1.8 — Configuration, language and cleanup

- Move image limits to Geeklog configuration.
- Synchronize English and French language keys.
- Update file headers, documentation and stale comments.

### 1.1.9 — Release candidate

Test the complete matrix:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1 where the Geeklog/PHP combination itself is viable
- single-site and multisite installations
- at least two multisite instances with distinct `$_CONF['path_data']` values
- fresh site-specific `*-documents/` storage and migration from legacy `data_documents/`
- Maps active / inactive / not installed
- MediaGallery active / inactive / not installed
- installation, upgrade, categories, fields, documents, drafts, submissions, permissions, search, images, comments and deletion

## 1.2.0 — Stable release

1.2.0 is the stabilization milestone. It should add no large new feature set. Release criteria:

- no TimThumb
- no install/upgrade telemetry
- secured uploads and admin AJAX
- no normal-use PHP warnings on supported environments
- reliable upgrade from 1.1.0
- persistent data stored in a site-specific sibling `<basename(path_data)>-documents/` directory
- no persistent Documents data removed by Geeklog 2.2.2 cache/data cleanup
- correct isolation between sites in multisite installations
- Maps and MediaGallery fully optional
- complete README and upgrade notes

## 1.3.0 — Architecture modernization

- Use numeric document IDs (`did`) for internal relations instead of `doc_url`.
- Add an explicit category ID to documents.
- Link pictures and values to `did`.
- Migrate MySQL tables from MyISAM to InnoDB.
- Add useful indexes and carefully introduced uniqueness constraints.
- Separate document/category/field/storage/rendering/integration responsibilities.
- Isolate Maps and MediaGallery adapters from the core plugin.
- Define a consistent internal field-type API: render, edit, validate, normalize, search and export.
- Improve routing while retaining compatibility with Geeklog URL handling.
- Centralize filesystem/storage access behind a Documents storage layer so multisite-safe paths are not hard-coded throughout the plugin.

## 1.4.0 — Functional evolution

Develop Documents as a configurable structured-content system for Geeklog:

- richer field types: integer, decimal, URL, email, phone, date/time, image, gallery, file, boolean, text, textarea, single/multiple choice, document relation, user and coordinates
- document-to-document relations
- repeatable fields
- advanced filtering and sorting
- configurable list/table/grid/card views
- better templates per content type
- SEO metadata and Schema.org support where relevant
- automatic/manual slugs
- revision history and restore
- document duplication
- CSV import/export
- JSON representation/API foundations
- Documents autotags
- Geeklog blocks for recent/popular/category/manual selections
- improved front-end submissions and moderation
- optional Maps features for marker/map/geographic views
- optional MediaGallery album/media integration

## Architecture principle

The existing core idea must be preserved: a category defines a structure of configurable fields, and documents store values for those fields. Stabilization comes first, architecture changes second, and new product features only after the 1.2.0 stable milestone.
