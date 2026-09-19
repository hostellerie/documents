# Documents for Geeklog

Documents is a configurable structured-content plugin for Geeklog. A category defines fields and presentation rules, while individual documents store values for those fields.

## Development status

The current development target is **Documents 1.2.0**.

The 1.2.0 work builds on the 1.1.x stabilization series and adds public SEO, a modern default document presentation, stronger request/input security, reusable autotags/blocks and a generic content-interoperability layer for Geeklog consumers such as Agent, Eclipse, Hub, Hello, IndexNow and XML Sitemap.

See [ROADMAP.md](ROADMAP.md) for the wider roadmap and [TESTING.md](TESTING.md) for the release validation matrix.

## Compatibility target

- Geeklog **2.1.1 through 2.2.2**
- PHP **5.6 through 8.1**
- single-site and multisite Geeklog installations
- **MySQL/MariaDB only**

MSSQL support has been removed from Documents 1.2.0. The plugin now explicitly rejects unsupported database backends during compatibility checks instead of relying only on the presence of a legacy SQL file.

The code must remain syntactically compatible with PHP 5.6 throughout the 1.x modernization work unless this policy is explicitly changed in a future major release.

## SEO in 1.2.0

Public Documents pages now have a common SEO layer with:

- clean canonical URLs;
- meta descriptions;
- OpenGraph metadata;
- Twitter Card metadata;
- JSON-LD structured data;
- `CreativeWork` structured data for individual documents;
- `CollectionPage` structured data for category/home collection pages.

Categories have a dedicated database field named `metadescription`. It is deliberately separate from `cat_help`: category help text remains an interface/content aid and is not reused as the SEO field.

For individual documents, the plugin derives an excerpt from the first suitable descriptive text field and falls back conservatively when no useful description is available.

Legacy query-string document URLs continue to redirect permanently to their clean canonical form.

## Content interoperability

Documents 1.2.0 exposes content without requiring consumers to know its SQL schema.

### Item Info

```php
plugin_getiteminfo_documents($id, $what, $uid = 0, $options = array())
```

A public addressable document can expose normalized properties including:

```text
id
type
subtype
title
url
description
excerpt
date-created
date-modified
uid
author
image
category
```

A single requested property follows the Geeklog Item Info convention and returns a scalar. Several requested properties are returned in requested order.

### Collections

Use `'*'` as the ID to retrieve a normalized collection:

```php
$items = plugin_getiteminfo_documents(
    '*',
    'id,title,url,excerpt,date-modified,image,category',
    0,
    array(
        'since' => time() - 86400,
        'limit' => 20,
        'order' => 'modified-desc'
    )
);
```

This is the preferred data surface for notification/digest consumers such as Hello. Collection retrieval enforces active state and Geeklog permissions.

Supported modernization options include:

- `since`;
- `limit`;
- `order` with `modified-desc`, `modified-asc`, `created-desc`, `created-asc`;
- Geeklog-style `filter[date-created]`.

### Canonical URL resolution

Documents owns its routing rules and exposes both directions:

```php
plugin_idtourl_documents($sub_type, $item_id)
plugin_urltoid_documents($url)
```

Consumers such as Hub and IndexNow should use these interfaces instead of rebuilding Documents URLs or querying Documents tables.

### Shared capabilities and services

Documents declares provider-owned capabilities through `plugin_getcapabilities_documents()`. The current 1.2.0 contract advertises normalized content read/collection/search, popular-content ordering, canonical URL resolution, lifecycle events, syndication, structured field descriptions, source-field reads and `dashboard.summary`.

Read-only internal services exposed through `PLG_invokeService()` include:

- `dashboard_summary` — bounded administration metrics for consumers such as Eclipse;
- `fields_describe` — category/document field definitions without exposing `documents_fields` directly;
- `source_fields_get` — exact stored field values for one accessible document, explicitly read-only in 1.2.0.

Agent and Hub can continue to use Item Info for normal content. Eclipse can consume `dashboard.summary` instead of querying Documents tables. Specialized consumers that genuinely need the configurable Documents schema can use the field services without learning private table layouts.

The repository also ships a static root-level `plugin.json` manifest for Monitor, Hub, repository catalogs and other tooling that needs identity/compatibility metadata without executing plugin PHP.


### Lifecycle events

Successful public-content transitions emit Geeklog lifecycle events:

```php
PLG_itemSaved($id, 'documents');
PLG_itemDeleted($id, 'documents');
```

The lifecycle bridge distinguishes public content from drafts/submissions. Publishing or updating an active document emits a saved event; withdrawing or deleting previously public content emits a deleted/invalidation event.

The canonical URL is remembered during the mutation request so synchronous consumers can still resolve it when a database row has just been deleted.

### XML Sitemap

Documents provides:

```php
plugin_collectSitemapItems_documents($uid, $limit)
```

The collector returns clean permitted URLs and `date-modified` values using the native Geeklog sitemap contract.

## Syndication and statistics

Documents 1.2.0 also exposes native Geeklog feed and statistics callbacks:

```php
plugin_getfeednames_documents()
plugin_getfeedcontent_documents()
plugin_feedupdatecheck_documents()
plugin_statssummary_documents()
plugin_showstats_documents()
```

RSS/Atom feeds and statistics reuse the same permission-aware item layer as Item Info, Hub, Hello and IndexNow instead of duplicating the content model.

## Hello, Hub and IndexNow

The integrations are intentionally generic and loosely coupled.

**Hello** can request recent Documents content through the Item Info collection interface and build notifications/digests without Documents-specific SQL.

**Hub** can identify a document through `type=documents` plus its document slug, request normalized metadata, resolve canonical URLs and react to save/delete lifecycle events.

**IndexNow** can listen to Geeklog lifecycle events and ask Documents for the canonical URL. Documents does not call IndexNow directly and IndexNow does not need to know the Documents schema.

This follows the interoperability direction documented in the Geeklog memorandum repository.

## Autotags

Documents 1.2.0 provides reusable autotags:

```text
[document:document-id]
[document:document-id card]
[document:document-id Custom link text]
[documents:category-slug]
[documents:category-slug 10]
```

The singular tag links to one permitted document or renders a responsive card. The plural tag renders recent permitted documents from one category.

## Blocks

Two standard Geeklog PHP block functions are available:

```text
phpblock_documents_recent
phpblock_documents_popular
```

They can be selected when an administrator creates a PHP block. Blocks use the same permission-aware content layer as Item Info and autotags.

## Security changes in 1.2.0

The public controller now validates Geeklog CSRF tokens for every mutating Documents action, including ordinary document create/edit/delete operations. Previous forms already generated the token; 1.2.0 enforces it server-side consistently.

Non-admin dynamic input is normalized server-side:

- text/date fields are reduced to plain text;
- select/radio values must exist in their configured option group;
- numeric integration identifiers are normalized;
- non-admin users cannot forge owner/group/permission fields.

The access-controlled image endpoint continues to reject remote fetching, path traversal and unsupported image types while enforcing document visibility.

## Public presentation

The default document template has been modernized around semantic `<article>`, `<header>`, content, footer and comments regions. The obsolete Google+ script has been removed.

Public presentation CSS is separate from administration CSS and follows the configured public Documents folder instead of assuming `/documents/`.

Custom templates remain supported and are not rewritten by the default-template modernization.

## Persistent data and multisite

Documents persistent data must not be stored inside a directory that Geeklog may treat as disposable cache/data content.

The plugin derives its own persistent data directory from the current site's `$_CONF['path_data']`:

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

- `/home/site/data/` becomes `/home/site/data-documents/`
- `/home/site/data-site2/` becomes `/home/site/data-site2-documents/`

This keeps persistent Documents data isolated between sites in a multisite installation.

Historical Documents versions used:

```text
<path_data>/data_documents/
```

The migration is conservative:

- the legacy directory is never deleted automatically;
- existing destination files are never overwritten;
- rerunning migration copies only missing files;
- symlinks are ignored;
- nested directories, including custom templates, are copied recursively;
- the target path is validated against the current site's `$_CONF['path_data']`.

## Configuration

Documents uses Geeklog's configuration system for administrator-adjustable settings, including:

- public Documents folder;
- main header and footer content;
- What's New integration and limits;
- statistics visibility;
- maximum image width, height and file size;
- default item permissions.

Statistics visibility supports hidden, administrators only, authenticated users, or everyone including anonymous visitors.

## Optional integrations

### Maps

Maps remains optional. When Maps is missing or inactive, Documents must not include Maps PHP files, query Maps tables, enqueue Maps JavaScript or render Maps controls.

### MediaGallery

MediaGallery remains optional. When it is missing or inactive, Documents must not include MediaGallery PHP files/classes, query its data or render album controls.

## Validation

The release workflow is a blocking gate for Documents 1.2.0. It runs PHP syntax lint and every autonomous `tests/*_test.php` check under PHP 5.6 and PHP 8.1 before creating the installable archive. The manual compatibility/release matrix remains in [TESTING.md](TESTING.md).

The 1.2.0 regression surface includes `tests/seo_interoperability_test.php`, which checks the static invariants for version metadata, supported database policy, category SEO schema, CSRF enforcement, Item Info, URL resolution, lifecycle events, sitemap contribution, autotags, blocks, syndication/statistics and the modern default template.

Before a public release, require the GitHub Actions regression jobs to pass under PHP 5.6 and PHP 8.1, verify the generated `dist/documents_1.2.0_2.1.1.zip`, and complete the supported Geeklog manual matrix.
