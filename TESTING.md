# Documents 1.2.0 release-candidate test matrix

This checklist defines the validation required before promoting Documents 1.2.0.

## Compatibility target

- Geeklog 2.1.1 through 2.2.2;
- PHP 5.6 through 8.1;
- MySQL/MariaDB installation and 1.2.0 schema migration.

## Automated/static checks

Before a release package is considered testable:

- run PHP syntax lint on all plugin PHP/INC files with PHP 5.6 and PHP 8.1;
- verify metadata/version consistency reports 1.2.0;
- verify no PHP syntax introduced after PHP 5.6 is used;
- verify configuration-upgrade idempotence;
- verify the 1.2.0 schema migration is idempotent;
- verify English/French language-key parity where language keys are changed;
- verify release packaging contains no development-only or generated files.

Automated checks do not replace real Geeklog installation tests.

## Environment matrix

| Geeklog | PHP | Fresh install | Upgrade | Front end | Admin | Status |
| --- | --- | --- | --- | --- | --- | --- |
| 2.1.1 | 5.6 | Pending | Pending | Pending | Pending | Pending |
| 2.1.x | 7.x | Pending | Pending | Pending | Pending | Pending |
| 2.2.0/2.2.1 | 7.x/8.0 | Pending | Pending | Pending | Pending | Pending |
| 2.2.2 | 8.1 | Pending | Pending | Pending | Pending | Pending |

Only combinations that Geeklog itself can run on need to be tested. Record the exact Geeklog and PHP version used for each manual test.

## Fresh installation

- Install Documents from the generated `documents-1.2.0.zip` package.
- Confirm plugin installation completes without PHP warnings or fatal errors.
- Confirm the plugin version is registered as 1.2.0.
- Confirm `documents_cat.metadescription` exists and defaults to an empty string.
- Confirm the public Documents directory and rewrite rules are created correctly.
- Confirm the site-specific persistent directory is derived from `$_CONF['path_data']` as `<basename(path_data)>-documents/`.
- Confirm no legacy `data_documents/` directory is required on a clean installation.
- Open Documents configuration and verify all fields and fieldsets render correctly.

## Upgrade validation

Test at least one installation originating from the historical 1.1.0 line and one recent stabilization build.

- Back up database, images and persistent Documents data before upgrading.
- Confirm schema migrations from old versions still execute when required.
- Confirm the 1.2.0 migration adds `metadescription` once and preserves existing category data.
- Run the upgrade path again and confirm the schema migration is idempotent.
- Confirm the persistent-data migration runs before the new plugin version is recorded.
- Confirm files from `data_documents/` are copied recursively.
- Confirm existing files in the destination are never overwritten.
- Confirm the legacy directory remains present after migration.
- Confirm customized image limits survive the upgrade.
- Confirm custom templates remain available from the persistent directory.

## Multisite isolation

Use two Geeklog sites with distinct `$_CONF['path_data']` values.

Example:

- site A: `/home/account/g2data/` -> `/home/account/g2data-documents/`
- site B: `/home/account/g3data/` -> `/home/account/g3data-documents/`

Verify:

- each site creates and uses only its own Documents persistent directory;
- a custom template created for site A is not visible to site B;
- migration on one site does not copy into or overwrite the other site's directory;
- Geeklog cache/data cleanup does not remove either Documents persistent directory.

## Category SEO

For a public category:

- create a category with a dedicated `metadescription`;
- edit the meta description and confirm the value is persisted independently from `cat_help`;
- clear the meta description and confirm the page still renders with a safe fallback description;
- confirm exactly one canonical URL points to the clean category URL;
- confirm the page outputs one useful meta description;
- confirm OpenGraph URL/title/description are present;
- confirm Twitter Card metadata is present;
- confirm JSON-LD uses `CollectionPage`;
- confirm private categories cannot expose SEO metadata to unauthorized users;
- confirm editing `cat_help` does not modify `metadescription` and vice versa.

## Document SEO

For an active public document:

- confirm the clean document URL is canonical;
- request the legacy `index.php?mode=view&cat=...&doc=...` route and confirm a 301 redirect to the clean URL;
- confirm the document title is used as the page/OG title;
- confirm the first suitable descriptive text field supplies the description/excerpt;
- confirm empty descriptions fall back safely without exposing raw HTML;
- confirm the first usable image is exposed as the social image when present;
- confirm JSON-LD uses `CreativeWork` with canonical URL and modification dates;
- confirm drafts, submissions, inactive and permission-protected documents do not expose public SEO metadata;
- confirm no duplicate legacy OpenGraph block is present;
- confirm the obsolete Google+ script is absent.

## Interoperability: Item Info

Test the Geeklog dispatcher where available as well as the direct callback.

For one public document, verify:

- `plugin_getiteminfo_documents($id, 'url')` returns a scalar canonical URL;
- one requested property returns a scalar;
- multiple requested properties return values in requested order;
- inaccessible or non-existent items return false;
- useful fields are available: `id`, `type`, `subtype`, `title`, `url`, `description`, `excerpt`, `date-created`, `date-modified`, `uid`, `author`, `image`, `category`.

For collections, verify:

- `plugin_getiteminfo_documents('*', 'id,title,url,date-modified', 0, $options)` returns normalized records;
- `since` filters on created/modified activity as documented;
- `limit` is respected;
- `modified-desc`, `modified-asc`, `created-desc` and `created-asc` work;
- Geeklog-style `filter[date-created]` works;
- collection results never contain drafts, inactive or unauthorized items.

## Hello readiness

Simulate the data request Hello needs for notifications/digests:

```php
plugin_getiteminfo_documents(
    '*',
    'id,title,url,excerpt,date-modified,image,category',
    0,
    array('since' => time() - 86400, 'limit' => 20, 'order' => 'modified-desc')
);
```

Verify that Hello can build a notification without querying any Documents table directly.

## Hub readiness

For a known document:

- resolve structured metadata through Item Info;
- resolve its canonical URL through `plugin_idtourl_documents()`;
- resolve the clean URL back through `plugin_urltoid_documents()`;
- verify the returned identity uses `type=documents`, the document slug as `id`, and `subtype=document`;
- verify permissions remain authoritative in Documents rather than Hub.

## IndexNow lifecycle readiness

With a listener that records Geeklog lifecycle events, validate:

- creating an active document emits `PLG_itemSaved($id, 'documents')`;
- editing an active document emits a saved event;
- draft -> active emits a saved event;
- active -> draft/inactive emits a deleted/invalidation event;
- deleting an active document emits a deleted event;
- creating/editing a draft without publication does not announce a public URL;
- when deletion is handled, `plugin_idtourl_documents()` can still provide the canonical URL during the event request;
- no Documents-specific SQL is required in IndexNow.

## Sitemap

- call `plugin_collectSitemapItems_documents($uid, $limit)`;
- confirm each row contains `url` and `date-modified`;
- confirm canonical clean URLs are returned;
- confirm only active permitted content is included;
- confirm `$limit=0` works as the core unlimited-collection convention;
- compare the result with the Item Info collection fallback.

## Autotags

Validate:

- `[document:document-id]` renders a permitted document link;
- `[document:document-id card]` renders a responsive card;
- `[document:document-id Custom label]` uses the custom label;
- `[documents:category-slug]` renders a compact recent list;
- `[documents:category-slug 10]` respects the requested limit;
- private/inactive documents never leak through an autotag;
- invalid IDs render no unsafe output.

## Blocks

Create Geeklog PHP blocks using:

- `phpblock_documents_recent`;
- `phpblock_documents_popular`.

Verify both blocks:

- display public permitted items only;
- produce valid responsive markup;
- remain functional for anonymous and authenticated visitors;
- do not query or expose inaccessible documents.

## Core functional tests

### Categories

- create, edit, reorder and delete a category;
- duplicate or invalid slugs are rejected or normalized safely;
- hidden/private categories do not leak through public listings.

### Fields and selections

- create, edit and delete common field types;
- create selection groups and options;
- required fields are enforced;
- plain text/date values cannot persist executable HTML from non-admin submissions;
- forged select/radio values are rejected;
- unavailable Maps or MediaGallery field types cannot be forged through requests.

### Documents

- create a document;
- edit an existing document;
- save as inactive, active, draft and submission where applicable;
- confirm permissions are preserved;
- delete a document and verify related records/images are cleaned consistently.

### Search and visibility

- active permitted documents appear in search;
- private documents respect Geeklog permissions;
- drafts, submissions and inactive documents do not leak to unauthorized users.

### Comments

- display comments;
- post a comment;
- verify notification behavior;
- verify unauthorized comment deletion is refused.

## Images

Test JPEG, PNG, GIF and WebP where the PHP/GD environment supports them.

- upload a valid image;
- verify width, height and size configuration limits;
- reject invalid or disguised uploads;
- verify generated previews;
- verify path traversal and remote fetch attempts are rejected;
- replace an image and confirm obsolete previews are removed;
- delete a document/image and confirm related files are cleaned safely.

## Optional Maps integration

Test three states:

1. Maps not installed;
2. Maps installed but inactive;
3. Maps installed and active.

Without Maps, Documents must not include Maps files, query Maps tables, enqueue Maps/Google Maps JavaScript or render Maps controls. Other Documents functionality must continue normally.

With Maps active, validate the existing map/category and marker/document integration without allowing Maps failures to break unrelated field types.

## Optional MediaGallery integration

Test three states:

1. MediaGallery not installed;
2. MediaGallery installed but inactive;
3. MediaGallery installed and active.

Without MediaGallery, Documents must not include MediaGallery files, instantiate its classes, query its data or render album controls. Other Documents functionality must continue normally.

With MediaGallery active, validate album selection and rendering using MediaGallery's own thumbnail URLs.

## Security regression checks

- every mutating public/admin Documents controller action rejects invalid or missing CSRF tokens;
- a valid token is consumed only once by the main controller;
- non-admin users cannot forge owner/group/permission fields;
- non-admin dynamic text input cannot store executable markup through ordinary text fields;
- select/radio values must belong to their configured group;
- admin AJAX refuses users without `documents.admin`;
- image endpoint does not allow remote fetching or path traversal;
- no TimThumb runtime remains;
- no install/upgrade telemetry email is sent;
- request values used in SQL, filesystem paths and HTML remain normalized/escaped on tested paths.

## Release decision

Documents 1.2.0 can be released only when:

- syntax lint passes on PHP 5.6 and PHP 8.1;
- no blocking PHP warning/fatal error remains in supported test environments;
- fresh install and upgrade tests pass;
- the category `metadescription` migration and editing flow are verified;
- SEO metadata/canonical output is verified on home, category and document pages;
- Item Info, collection, URL resolution, lifecycle and sitemap tests pass;
- Hello, Hub and IndexNow can consume Documents without Documents-specific SQL;
- multisite isolation is confirmed manually;
- optional dependency states are validated;
- no data-loss, permission or indexing regression is found.
