# Documents for Geeklog

Documents is a configurable structured-content plugin for Geeklog. A category defines fields and presentation rules, while individual documents store values for those fields.

## Development status

The current development line is **1.1.x**, focused on stabilizing the historical 1.1.0 codebase before the 1.2.0 stable release.

The current stabilization milestone is **1.1.10 release candidate**. This version formalizes the configuration upgrade required for the display/integration settings introduced during the 1.1.9 development cycle and adds an option to expose Documents statistics to anonymous visitors.

See [ROADMAP.md](ROADMAP.md) for the complete stabilization, architecture and feature roadmap.

## Compatibility target

- Geeklog **2.1.1 through 2.2.2**
- PHP **5.6 through 8.1**
- single-site and multisite Geeklog installations

The code must remain syntactically compatible with PHP 5.6 throughout the 1.x modernization work unless this policy is explicitly changed in a future major release.

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

This makes the directory unique for each site in a multisite installation while keeping the same behavior in a normal single-site installation.

Historical Documents versions used:

```text
<path_data>/data_documents/
```

### Legacy data migration

The 1.1.7 stabilization work implements an idempotent migration from the historical `data_documents/` directory to the site-specific sibling directory returned by `DOCUMENTS_dataDir()`.

The migration deliberately follows conservative rules:

- the legacy directory is never deleted automatically;
- existing files in the new directory are never overwritten;
- rerunning the migration copies only files that are still missing;
- symlinks are ignored;
- nested directories, including custom templates, are copied recursively;
- the target path is validated against the current site's `$_CONF['path_data']` before any copy is attempted;
- an upgrade is not recorded as successful when routing or persistent-data migration fails.

For example, a site using:

```text
/home/account/g2data/
```

migrates its Documents persistent files to:

```text
/home/account/g2data-documents/
```

A second multisite instance using `/home/account/g3data/` therefore receives `/home/account/g3data-documents/` and cannot share the first site's Documents persistent directory through the normal path derivation logic.

The legacy directory remains available as a temporary read fallback during the stabilization series. New writes must use the site-specific Documents directory.

Do not manually remove `data_documents/` until the upgraded site has been checked and all expected custom templates or other persistent Documents files are present in the new directory.

## Configuration

Documents uses Geeklog's configuration system for administrator-adjustable settings.

Current settings include:

- public Documents folder;
- main header and footer content;
- Documents integration in Geeklog's What's New block;
- What's New time window and maximum item count;
- statistics visibility;
- maximum image width;
- maximum image height;
- maximum image file size;
- default item permissions.

Statistics visibility supports four levels:

- **Hidden**;
- **Administrators only** (default);
- **Logged-in users and administrators**;
- **Everyone, including anonymous visitors**.

The image limits introduced in the 1.1.8 stabilization work default to:

- width: **3000 px**;
- height: **3000 px**;
- file size: **4,194,304 bytes**.

During upgrades, existing customized values are preserved. Missing records are added without resetting existing configuration entries. Version 1.1.10 explicitly reruns the idempotent integration/display configuration registration so an installed 1.1.9 site receives any missing settings during the upgrade.

## Optional integrations

### Maps

The Geeklog Maps plugin is optional.

When Maps is missing or inactive, Documents must:

- not include Maps PHP files;
- not query Maps tables;
- not enqueue Google Maps or Maps-specific JavaScript;
- not render map or marker fields/controls;
- continue to operate normally for all other field types.

### MediaGallery

The Geeklog MediaGallery plugin is optional.

When MediaGallery is missing or inactive, Documents must:

- not include MediaGallery PHP files;
- not instantiate MediaGallery classes;
- not query MediaGallery data;
- not render album-related controls;
- continue to operate normally for all other field types.

## Validation and test archives

GitHub Actions are intentionally not used during normal Documents development.

The repository keeps the standalone regression tests in `tests/`, but they are run only when a compatibility or release-validation milestone requires them. Test failures therefore do not create automatic GitHub workflow noise during day-to-day development.

Installable test archives are built directly from the current source at meaningful manual-test milestones. They are not committed to `test-artifacts/` and no GitHub packaging workflow is required.

Before a public release, the regression suite should still be executed against the supported PHP/Geeklog matrix and complemented by the manual compatibility checklist in `TESTING.md`.

## Stabilization work

The 1.1.x stabilization line includes:

- removal of unsolicited installation/upgrade telemetry;
- stronger Geeklog/PHP compatibility checks;
- protection of administrative and AJAX mutations;
- multisite-safe persistent data path handling and migration;
- strict optional Maps and MediaGallery behavior;
- removal of TimThumb and replacement with local-only image handling;
- JPEG, PNG, GIF and WebP upload handling;
- persistent image previews;
- data-integrity checks and safer deletion handling;
- input/output hardening without changing the historical database model;
- administrator-configurable image limits;
- configurable text display normalization without modifying stored values;
- native Geeklog What's New integration;
- richer Geeklog search descriptions;
- configurable Documents statistics visibility, including optional anonymous access;
- synchronized English and French configuration and integrity-audit labels.

## Upgrade policy

The 1.1.x series must preserve existing Documents installations and data. Major database restructuring is intentionally deferred until 1.3.0.

The legacy `data_documents` directory must not be deleted manually before the migrated installation has been validated. During stabilization, it remains available as a compatibility source/read fallback.

Before installing a development or release-candidate build on a production site, back up both the Geeklog database and the Documents image/data directories.

## License

GNU General Public License v2.0 or later, consistent with the existing plugin source headers and LICENSE file.
