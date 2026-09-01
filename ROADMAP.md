# Documents plugin roadmap

## Compatibility target

Documents 1.2.0 targets:

- Geeklog 2.1.1 through 2.2.2;
- PHP 5.6 through 8.1;
- MySQL/MariaDB only;
- Maps plugin as an optional service integration;
- MediaGallery plugin as an optional integration;
- multisite-safe persistent storage derived from the current site's `$_CONF['path_data']`.

MSSQL support has been removed. PHP 5.6 compatibility remains a hard requirement, so runtime code must not introduce PHP 7+ syntax.

## 1.2.0 — SEO, security and interoperability modernization

### Completed in the current development branch

#### Database and upgrade path

- Plugin development metadata moved to 1.2.0.
- Added a dedicated `documents_cat.metadescription VARCHAR(255)` column.
- `cat_help` remains independent from SEO metadata.
- Added an idempotent 1.2.0 MySQL/MariaDB schema migration.
- Removed the legacy MSSQL installation schema and explicitly reject unsupported database backends.
- The 1.2.0 upgrade regenerates Documents rewrite rules.
- Document route IDs are constrained to the historical `VARCHAR(40)` schema limit before persistence.

#### Public SEO

- Added canonical URLs for home, category and document pages.
- Added dedicated category meta descriptions.
- Individual documents may define a `metadescription` field; when absent or empty, Documents builds a safe description from useful text fields and finally falls back conservatively.
- Added OpenGraph and Twitter Card metadata.
- Added JSON-LD for collection and document pages, including document breadcrumbs.
- Document Schema.org type defaults to `CreativeWork` and may be selected through an optional `schema_type` field from an allowlist.
- Added robots rules by editorial status: active content may be indexed, inactive/draft/submission content is `noindex` according to workflow state.
- The first usable document image is reused consistently for OpenGraph, Twitter Card and JSON-LD.
- Removed obsolete Google+ output and duplicate managed metadata.
- Paginated category pages retain distinct canonical URLs using `?page=N`.

#### Public rendering

- Added a semantic public home page with responsive category cards.
- Added a semantic category page with responsive document cards and pagination.
- Added an editorial default document template: main image, main text content, structured properties and rich fields.
- Removed the generic `Document details` wrapper from the default document page.
- Added visible document breadcrumbs.
- Custom document templates remain supported.
- Public category/document output consistently escapes dynamic text.
- Public CSS is separate from administration CSS.

#### Workflow and visibility

- Editorial status and read visibility are deliberately separate.
- Geeklog document/category permissions control who may read a document.
- Active, inactive, draft and pending documents may therefore remain visible to authorized users.
- Pending submissions are frozen for non-admin owners until moderation.
- Sitemap, generic IndexNow lifecycle collection, feeds and other public-indexing surfaces continue to use active/public content only.
- Public category lists show status badges to Documents administrators.

#### Administration

- Added standalone modern category, field, selection-group and selection editors.
- Added a Template/CSS presentation guide in Documents administration.
- Added a read-only integrity audit for duplicate routes, orphan records and image consistency.
- Added dedicated mutation layers protected by `documents.admin` and Geeklog CSRF validation.
- Dynamic values use normalization and `DB_escapeString()` rather than new `addslashes()` usage.
- Used fields cannot silently change category or type.
- Category/field/group/selection deletion applies integrity guards and cleanup rules.

#### Document saves and images

- Added a secure document save dispatcher and mutation layer.
- Required fields and selection values are validated server-side.
- Existing document/category binding and edit permissions are verified before mutation.
- Non-admin users cannot forge owner, group, permissions or publication state.
- New document URLs are deterministic, unique and constrained to 40 characters.
- Image uploads use Geeklog's upload class with MIME, extension, dimension and file-size restrictions.
- Failed mutations remove newly uploaded files and successful replacements remove obsolete images/previews.
- Secure document deletion cleans Documents values/images and emits the correct lifecycle transition.

#### Maps ownership boundary

- Maps remains optional: Documents installs and operates without Maps.
- Documents no longer writes directly to Maps tables and does not allocate marker IDs.
- Marker creation/update/deactivation is delegated to Maps through `PLG_invokeService('maps', 'marker_save', ...)`.
- Marker rendering is delegated through `PLG_invokeService('maps', 'marker_render', ...)`.
- Documents stores only the marker ID returned by Maps in its own `documents_values` table.
- A regression test now protects this ownership boundary and rejects reintroduction of direct Maps-table SQL.

#### Interoperability with Hello, Hub, IndexNow and XML Sitemap

- Added `plugin_getiteminfo_documents()` for single documents and `id='*'` collections.
- Added collection options including `since`, `limit`, ordering and `filter[date-created]`.
- Added `plugin_idtourl_documents()` and `plugin_urltoid_documents()`.
- Added `plugin_collectSitemapItems_documents()`.
- Exposed normalized fields including ID, type, subtype, title, canonical URL, description, excerpt, dates, author, primary image, category and hit count.
- Item Info enforces both document and category permissions.
- Anonymous-public indexability requires active state plus anonymous read access to both document and category.
- Lifecycle events describe public-indexing transitions with `PLG_itemSaved` and `PLG_itemDeleted`.
- Hello and Hub can consume Documents through Item Info rather than Documents-specific SQL.

#### Autotags, blocks, feeds and statistics

- Added document and category autotags.
- Added recent and popular PHP blocks.
- Added native Geeklog feed callbacks.
- Added native Geeklog statistics callbacks.
- The Geeklog ranking is `Top Ten Documents` and remains restricted to active permitted documents.
- The `/documents/` summary keeps a generic `Statistics` heading with published-document and view counters.

#### Compatibility CI and packaging

The GitHub Actions release workflow now runs, before packaging:

- PHP 5.6 syntax lint for all `.php` and `.inc` files;
- all autonomous `tests/*_test.php` regression checks under PHP 5.6;
- PHP 8.1 syntax lint for all `.php` and `.inc` files;
- all autonomous `tests/*_test.php` regression checks under PHP 8.1;
- ZIP creation only after every preceding check succeeds;
- archive integrity verification with `unzip -t`.

The release workflow is intentionally blocking: a regression test failure prevents a new installable archive from being committed.

### Remaining before 1.2.0 release

#### Automated release gate

- Bring every currently maintained autonomous regression test to green under PHP 5.6 and PHP 8.1 now that the workflow actually executes the complete suite.
- Keep the Maps ownership-boundary test green.
- Confirm the final workflow produces a clean `dist/documents_1.2.0-2.1.1.zip` after the complete suite passes.

#### Manual Geeklog validation

Static CI is not a substitute for real installations. Validate at minimum:

- Geeklog 2.1.1 with PHP 5.6;
- Geeklog 2.2.2 with PHP 8.1;
- fresh installation from the generated ZIP;
- upgrade from an existing 1.1.x site;
- single-site and multisite storage isolation;
- anonymous, authenticated and administrator permissions;
- inactive, active, draft and pending workflow states;
- publication, depublication and deletion;
- images and comments;
- search, What's New, feeds, sitemap and statistics;
- document/category autotags and recent/popular blocks;
- Hello/Hub/IndexNow interoperability where available.

#### Maps manual validation

With Maps absent, Documents must install and all non-marker categories must function normally.

With a compatible Maps version installed, validate:

- marker creation through `marker_save`;
- marker update;
- rendering through `marker_render`;
- deactivation/withdrawal when a source document is deleted or withdrawn;
- no direct Documents SQL against Maps tables.

#### MediaGallery integration

Categories containing an `album` field remain an optional compatibility path. Keep MediaGallery optional and avoid expanding direct coupling before 1.2.0. A stricter service boundary can be developed later.

#### Release documentation

- Keep `CHANGELOG.md`, `README.md`, `ROADMAP.md` and `TESTING.md` aligned with the final 1.2.0 behavior.
- Record the exact environments used for the final manual matrix.
- Prepare final release notes after the manual matrix is green.

### Deferred after 1.2.0

The following work should not block the compatible 1.2.0 release:

- physically remove additional unreachable historical controller blocks after final regression testing;
- further reduce legacy controller code without breaking old URLs or custom templates;
- optimize collection permission filtering before SQL `LIMIT`;
- formalize the MediaGallery service boundary;
- additional Schema.org specializations and richer field-type APIs.

## 1.3.0 — Architecture modernization

After the compatible 1.2.0 release:

- use numeric document IDs (`did`) for internal relations instead of `doc_url`;
- add an explicit category ID to document rows;
- link pictures and values to `did`;
- migrate MySQL tables from MyISAM to InnoDB;
- add indexes and carefully introduced uniqueness constraints;
- separate document/category/field/storage/rendering/integration responsibilities further;
- define a consistent internal field-type API: render, edit, validate, normalize, search and export;
- centralize filesystem/storage access completely;
- reduce or retire remaining historical controller responsibilities.

## 1.4.0 — Functional evolution

Develop Documents as a configurable structured-content system for Geeklog:

- richer field types: integer, decimal, URL, email, phone, date/time, image, gallery, file, boolean, text, textarea, single/multiple choice, document relation, user and coordinates;
- document-to-document relations;
- repeatable fields;
- advanced filtering and sorting;
- configurable list/table/grid/card views;
- richer Schema.org support;
- automatic/manual slugs;
- revision history and restore;
- document duplication;
- CSV import/export;
- JSON representation/API foundations;
- improved front-end submissions and moderation.

## Architecture principle

A category defines the structure, Documents owns document data, permissions and canonical URLs, and other plugins consume standardized contracts rather than querying Documents tables directly. Optional integrations remain optional and use service boundaries whenever another plugin owns the underlying data.
