# Documents 1.1.9 release-candidate test matrix

This checklist defines the manual validation required before promoting the stabilization line to Documents 1.2.0.

## Automated checks

The permanent workflow must pass before any manual package is considered testable:

- PHP syntax lint on PHP 5.6;
- PHP syntax lint on PHP 8.1;
- legacy persistent-data migration and multisite isolation test;
- configuration-upgrade idempotence test;
- preservation of customized image limits;
- English/French language-key parity;
- release-candidate security and architecture invariants;
- metadata/version consistency checks;
- non-mutating packaging verification.

Automated checks are necessary but do not replace real Geeklog installation tests.

## Environment matrix

Validate viable combinations within the supported range:

| Geeklog | PHP | Fresh install | Upgrade | Front end | Admin | Status |
| --- | --- | --- | --- | --- | --- | --- |
| 2.1.1 | 5.6 | Pending | Pending | Pending | Pending | Pending |
| 2.1.x | 7.x | Pending | Pending | Pending | Pending | Pending |
| 2.2.0/2.2.1 | 7.x/8.0 | Pending | Pending | Pending | Pending | Pending |
| 2.2.2 | 8.1 | Pending | Pending | Pending | Pending | Pending |

Only combinations that Geeklog itself can run on need to be tested. Record the exact Geeklog and PHP version used for each manual test.

## Fresh installation

- Install Documents from the generated `documents-1.1.9.zip` package.
- Confirm plugin installation completes without PHP warnings or fatal errors.
- Confirm the plugin version is registered as 1.1.9.
- Confirm the public Documents directory and rewrite rules are created correctly.
- Confirm the site-specific persistent directory is derived from `$_CONF['path_data']` as `<basename(path_data)>-documents/`.
- Confirm no legacy `data_documents/` directory is required on a clean installation.
- Open Documents configuration and verify all fields and fieldsets render with translated labels.

## Upgrade validation

Test at least one installation originating from the historical 1.1.0 line and one recent stabilization build.

- Back up database, images and persistent Documents data before upgrading.
- Confirm schema migrations from old versions still execute when required.
- Confirm the persistent-data migration runs before the new plugin version is recorded.
- Confirm files from `data_documents/` are copied recursively.
- Confirm existing files in the destination are never overwritten.
- Confirm the legacy directory remains present after migration.
- Run the upgrade a second time or re-run the migration test path and confirm no duplicate data is created.
- Confirm customized image limits survive the upgrade.
- Confirm custom templates are available from the new persistent directory.

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

## Core functional tests

For each primary supported environment, validate:

### Categories

- create, edit, reorder and delete a category;
- duplicate or invalid slugs are rejected or normalized safely;
- hidden categories do not leak through public listings.

### Fields and selections

- create, edit and delete common field types;
- create selection groups and options;
- required fields are enforced;
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

- admin AJAX refuses users without `documents.admin`;
- mutating controller actions reject invalid/missing CSRF tokens;
- image endpoint does not allow remote fetching or path traversal;
- no TimThumb runtime remains;
- no install/upgrade telemetry email is sent;
- request values used in SQL, filesystem paths and HTML remain normalized/escaped on tested paths.

## Release decision

Documents 1.2.0 can be prepared only when:

- all automated checks pass;
- no blocking PHP warning/fatal error remains in supported test environments;
- fresh install and upgrade tests pass;
- multisite isolation is confirmed manually;
- Maps and MediaGallery optional-dependency states are validated;
- no data-loss or permission regression is found;
- any remaining cosmetic/header cleanup is explicitly documented and cannot affect runtime behavior.
