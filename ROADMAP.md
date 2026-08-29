# Documents plugin roadmap

## Compatibility target

Documents 1.2.0 targets:

- Geeklog 2.1.1 through 2.2.2;
- PHP 5.6 through 8.1;
- MySQL/MariaDB only;
- Maps plugin as an optional integration;
- MediaGallery plugin as an optional integration;
- multisite-safe persistent storage derived from the current site's `$_CONF['path_data']`.

MSSQL support has been removed. PHP 5.6 compatibility remains a hard requirement, so the plugin must not introduce PHP 7+ syntax in runtime code.

## 1.2.0 — SEO, security and interoperability modernization

### Completed in the current development branch

#### Database and upgrade path

- Plugin development metadata moved to 1.2.0.
- Added a dedicated `documents_cat.metadescription VARCHAR(255)` column.
- `cat_help` remains an independent field and is not reused as SEO metadata.
- Added an idempotent 1.2.0 MySQL/MariaDB schema migration.
- Removed the legacy MSSQL installation schema and reject unsupported database backends explicitly.
- The 1.2.0 upgrade forces regeneration of Documents rewrite rules.
- Document route IDs are constrained to the historical `VARCHAR(40)` schema limit before persistence.

#### Public SEO

- Added canonical URLs for home, category and document pages.
- Added dedicated meta descriptions, with the category `metadescription` field taking priority for category pages.
- Added OpenGraph and Twitter Card metadata.
- Added JSON-LD using `CollectionPage` for collection pages and `CreativeWork` for document pages.
- Removed obsolete Google+ output from the default template.
- Added duplicate managed-meta cleanup to avoid multiple canonical/description/social tags.
- Paginated category pages retain a distinct canonical URL using `?page=N`.

#### Public rendering

- Added a semantic public home page with responsive category cards.
- Added a semantic category page with responsive document cards and pagination.
- Added a modern default document renderer using `<article>` and `<dl>/<dt>/<dd>` rather than table layout.
- Public category/document output consistently escapes dynamic text.
- Custom document templates remain supported through the historical renderer.
- Maps and MediaGallery specialized rendering remains available through the historical compatibility path.
- Public CSS is separate from administration CSS.

#### Category editor

- Added a standalone 1.2.0 category editor.
- `metadescription` is loaded directly with the category and rendered directly in the form.
- Removed the temporary `category-meta.php` AJAX endpoint and its client-side preload.
- Existing `index.php?mode=edit_cat` URLs are internally routed to the new editor.

#### Secure administration mutations

- Added dedicated mutation layers for categories, fields, selection groups and selection values.
- Default admin forms use dedicated POST endpoints protected by `documents.admin` and Geeklog CSRF validation.
- Historical `save_cat`, `save_field`, `save_group` and `save_select` requests are intercepted after authorization and routed to the secure mutation layer, so their old SQL blocks are no longer reachable through normal requests.
- Dynamic text values are normalized and SQL values use `DB_escapeString()` rather than new `addslashes()` usage.
- Field types use an explicit allowlist.
- Used fields cannot silently change category or type.
- Deleting categories, fields, groups and selections applies integrity guards and cleanup rules.
- Read-only admin AJAX requires `documents.admin` but deliberately does not consume the one-time form CSRF token.

#### Document saves

- Added a progressive secure document save dispatcher.
- Standard categories now save through a dedicated mutation layer instead of the historical controller.
- Supported secure-path field types currently include text, textarea, decimal, date, checkbox, select, category and image.
- Required fields and select options are validated server-side.
- Existing document/category binding and edit permissions are verified before mutation.
- Non-admin users cannot forge owner, group or permission values; this is enforced both by the dispatcher and the document mutation layer.
- Workflow states are normalized server-side.
- New document URLs are deterministic, unique and constrained to 40 characters.
- New image uploads use Geeklog's upload class with MIME, extension, dimension and file-size restrictions.
- Image uploads are separated from database writes; failed mutations remove newly uploaded files and successful replacements remove obsolete images/previews.
- Document deletion remains on the historical path for now so existing image/integration cleanup behavior is preserved.

#### Interoperability with Hello, Hub, IndexNow and XML Sitemap

- Added `plugin_getiteminfo_documents()` for single documents and `id='*'` collections.
- Added collection options including `since`, `limit`, ordering and `filter[date-created]`.
- Added `plugin_idtourl_documents()` and `plugin_urltoid_documents()`.
- Added `plugin_collectSitemapItems_documents()`.
- Added normalized fields including ID, type, subtype, title, canonical URL, description, excerpt, creation/modification dates, owner/author, primary image, category and hit count.
- Item Info enforces both document and category permissions.
- Added anonymous-public indexability checks: active state alone is not enough; document and category must both grant read access to Geeklog anonymous user ID 1.
- Lifecycle notifications now describe public-indexing transitions:
  - private -> public: `PLG_itemSaved`;
  - public -> public modification: `PLG_itemSaved`;
  - public -> private/inactive/deleted: `PLG_itemDeleted`;
  - private -> private: no public indexing event.
- This prevents active but private content from being submitted to generic IndexNow listeners.
- XML Sitemap compatibility follows Geeklog core, which calls plugin sitemap collectors with anonymous user ID 1.
- Hello and Hub can consume Documents through Item Info rather than Documents-specific SQL.

#### Autotags, blocks, feeds and statistics

- Added document and category autotags.
- Added recent and popular PHP blocks.
- Added native Geeklog feed callbacks.
- Added native statistics summary/detail callbacks.
- These surfaces reuse the common content/interoperability layer instead of creating separate content models.

#### Compatibility CI

A GitHub Actions compatibility workflow now runs on the development branch and pull requests:

- PHP 5.6 syntax lint for all `.php` and `.inc` files;
- PHP 8.1 syntax lint for all `.php` and `.inc` files;
- all autonomous `tests/*_test.php` regression checks on both PHP versions.

The current branch passes this CI on both PHP 5.6 and PHP 8.1.

### Remaining before 1.2.0 release

#### Manual Geeklog validation

Static CI is not a substitute for real installations. Validate at minimum:

- Geeklog 2.1.1 with a viable PHP 5.6 environment;
- Geeklog 2.2.2 with PHP 8.1;
- fresh installation;
- upgrade from an existing 1.1.x site;
- single-site and multisite storage isolation;
- anonymous, authenticated, publisher and administrator permissions;
- drafts, submissions, publication, depublication and deletion;
- comments;
- search, What's New, feeds, sitemap and statistics;
- Hello/Hub/IndexNow integration where those plugins are available.

#### Maps integration

Documents still uses its historical Maps marker mutation path for categories containing a `marker` field.

Do **not** duplicate this SQL into a new Documents module. Maps 1.6.0 already exposes internal marker read/render/validity services, but its current `services.inc.php` does not expose a general marker create/update service. The preferred next step is:

1. add a trusted `marker_save`/create-update service to Maps;
2. call it through `PLG_invokeService()` from Documents;
3. remove Documents-specific writes to Maps tables;
4. keep Maps responsible for marker validation, URL, lifecycle and map recalculation.

Until that service exists, Maps categories remain on the tested legacy compatibility path.

#### MediaGallery integration

Categories containing an `album` field remain on the historical compatibility path. Keep MediaGallery optional and avoid duplicating its internal database logic in Documents. A future adapter/service should own this integration boundary.

#### Legacy controller reduction

- Physically remove unreachable `save_cat`, `save_field`, `save_group` and `save_select` blocks from `include_html.php` after final manual regression tests.
- Move remaining document deletion and specialized integration mutations out of `include_html.php`.
- Continue reducing `include_html.php` without breaking custom templates or old administration URLs.

#### Collection query refinement

Item Info currently rechecks category permissions for every returned item, so it is secure. For collection efficiency and exact `limit` fulfillment, move category permission filtering into the collection query before `LIMIT`, and apply the same optimization to blocks/feed queries.

## 1.3.0 — Architecture modernization

After the compatible 1.2.0 release:

- use numeric document IDs (`did`) for internal relations instead of `doc_url`;
- add an explicit category ID to document rows;
- link pictures and values to `did`;
- migrate MySQL tables from MyISAM to InnoDB;
- add indexes and carefully introduced uniqueness constraints;
- separate document/category/field/storage/rendering/integration responsibilities further;
- isolate Maps and MediaGallery behind formal service adapters;
- define a consistent internal field-type API: render, edit, validate, normalize, search and export;
- centralize filesystem/storage access completely;
- reduce or retire the historical monolithic `include_html.php` controller.

## 1.4.0 — Functional evolution

Develop Documents as a configurable structured-content system for Geeklog:

- richer field types: integer, decimal, URL, email, phone, date/time, image, gallery, file, boolean, text, textarea, single/multiple choice, document relation, user and coordinates;
- document-to-document relations;
- repeatable fields;
- advanced filtering and sorting;
- configurable list/table/grid/card views;
- better templates per content type;
- richer Schema.org support;
- automatic/manual slugs;
- revision history and restore;
- document duplication;
- CSV import/export;
- JSON representation/API foundations;
- improved front-end submissions and moderation.

## Architecture principle

A category defines the structure, Documents owns document data, permissions and canonical URLs, and other plugins consume standardized contracts rather than querying Documents tables directly. Optional integrations must remain optional and must not make Documents depend on the internal schema of another plugin when a service boundary can be provided instead.
