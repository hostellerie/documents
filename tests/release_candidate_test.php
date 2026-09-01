<?php

/* Documents 1.2.0 release-candidate architecture and compatibility checks. */

$root = dirname(__DIR__);
$failures = array();

function DOCUMENTS_rcRead($root, $path, &$failures)
{
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($file)) {
        $failures[] = 'Missing required file: ' . $path;
        return '';
    }
    $content = file_get_contents($file);
    if ($content === false) {
        $failures[] = 'Unable to read required file: ' . $path;
        return '';
    }
    return $content;
}

function DOCUMENTS_rcRequireContains($content, $needle, $label, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $label;
    }
}

function DOCUMENTS_rcRequireAbsent($content, $needle, $label, &$failures)
{
    if (strpos($content, $needle) !== false) {
        $failures[] = $label;
    }
}

$autoinstall = DOCUMENTS_rcRead($root, 'autoinstall.php', $failures);
$installUpdates = DOCUMENTS_rcRead($root, 'install_updates.php', $failures);
$mysqlInstall = DOCUMENTS_rcRead($root, 'sql/mysql_install.php', $failures);
$functions = DOCUMENTS_rcRead($root, 'functions.inc', $failures);
$compat = DOCUMENTS_rcRead($root, 'include_compat.php', $failures);
$security = DOCUMENTS_rcRead($root, 'security.php', $failures);
$runtime = DOCUMENTS_rcRead($root, 'runtime.php', $failures);
$navigation = DOCUMENTS_rcRead($root, 'navigation.php', $failures);
$presentation = DOCUMENTS_rcRead($root, 'presentation.php', $failures);
$interop = DOCUMENTS_rcRead($root, 'interoperability.php', $failures);
$indexability = DOCUMENTS_rcRead($root, 'indexability.php', $failures);
$seo = DOCUMENTS_rcRead($root, 'seo.php', $failures);
$storage = DOCUMENTS_rcRead($root, 'storage.php', $failures);
$distribution = DOCUMENTS_rcRead($root, 'distribution.php', $failures);
$embeds = DOCUMENTS_rcRead($root, 'embeds.php', $failures);
$maps = DOCUMENTS_rcRead($root, 'maps_adapter.php', $failures);
$mediaGallery = DOCUMENTS_rcRead($root, 'mediagallery_adapter.php', $failures);
$publicIndex = DOCUMENTS_rcRead($root, 'public_html/index.php', $failures);
$home = DOCUMENTS_rcRead($root, 'public_html/home.php', $failures);
$category = DOCUMENTS_rcRead($root, 'public_html/category.php', $failures);
$documentController = DOCUMENTS_rcRead($root, 'public_html/document.php', $failures);
$documentRenderer = DOCUMENTS_rcRead($root, 'public_document.php', $failures);
$documentForm = DOCUMENTS_rcRead($root, 'public_form.php', $failures);
$documentSave = DOCUMENTS_rcRead($root, 'public_html/document-save.php', $failures);
$documentDelete = DOCUMENTS_rcRead($root, 'document_delete.php', $failures);
$adminIndex = DOCUMENTS_rcRead($root, 'admin/index.php', $failures);
$categoryEditor = DOCUMENTS_rcRead($root, 'admin_category_editor.php', $failures);
$imageEndpoint = DOCUMENTS_rcRead($root, 'public_html/image.php', $failures);
$categoryTemplate = DOCUMENTS_rcRead($root, 'templates/cat_form.thtml', $failures);
$documentTemplate = DOCUMENTS_rcRead($root, 'templates/document.thtml', $failures);
$package = DOCUMENTS_rcRead($root, '.github/workflows/package.yml', $failures);

$checks = array(
    array($autoinstall, "'pi_version' => '1.2.0'", 'Plugin metadata is not set to 1.2.0.'),
    array($autoinstall, "define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1')", 'Minimum Geeklog version is not 2.1.1.'),
    array($autoinstall, "define('DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE', '2.2.3')", 'Geeklog compatibility range is incorrect.'),
    array($autoinstall, "define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0')", 'Minimum PHP version is not 5.6.0.'),
    array($autoinstall, "define('DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE', '8.2.0')", 'PHP compatibility range is incorrect.'),
    array($autoinstall, "define('DOCUMENTS_SUPPORTED_DBMS', 'mysql')", 'Supported DBMS declaration is missing.'),
    array($installUpdates, 'function DOCUMENTS_updateSchema_1_2_0()', '1.2.0 schema upgrade helper is missing.'),
    array($mysqlInstall, 'metadescription varchar(255)', 'Fresh schema is missing category metadescription.'),

    array($compat, 'function DOCUMENTS_canViewDocument(', 'Central visibility guard is missing.'),
    array($compat, 'function DOCUMENTS_canEditDocument(', 'Central edit guard is missing.'),
    array($compat, 'function DOCUMENTS_normalizeDocumentStatus(', 'Workflow state normalizer is missing.'),
    array($security, 'function DOCUMENTS_lockSecurityFields(', 'Security-field lock helper is missing.'),
    array($security, 'function DOCUMENTS_prepareDocumentFieldRequest(', 'Dynamic field request normalizer is missing.'),

    array($runtime, "'security.php'", 'Runtime does not load security services.'),
    array($runtime, "'navigation.php'", 'Runtime does not expose navigation helpers.'),
    array($runtime, "'interoperability.php'", 'Runtime does not load interoperability services.'),
    array($runtime, "'indexability.php'", 'Runtime does not load indexability services.'),
    array($navigation, 'function DOCUMENTS_renderNavigation()', 'Navigation renderer is missing.'),
    array($presentation, 'function DOCUMENTS_loadPublicStyles()', 'Public stylesheet compatibility loader is missing.'),
    array($presentation, 'function DOCUMENTS_homeStatsBlock(', 'Home statistics helper is missing.'),
    array($home, 'DOCUMENTS_homeStatsBlock()', 'Home no longer renders statistics explicitly.'),

    array($publicIndex, "if (\$mode === 'view')", 'Public read routing is missing.'),
    array($publicIndex, "if (\$mode === 'save')", 'Secure public save routing is missing.'),
    array($publicIndex, "require __DIR__ . '/document-save.php';", 'Public saves do not use the secure dispatcher.'),
    array($adminIndex, '$adminViews', 'Dedicated admin view router is missing.'),
    array($adminIndex, '$adminSaveModes', 'Dedicated admin mutation router is missing.'),
    array($adminIndex, "SEC_hasRights('documents.admin')", 'Dedicated administration is not access controlled.'),
    array($categoryEditor, 'SEC_createToken()', 'Category editor does not create a Geeklog CSRF token.'),

    array($documentController, 'DOCUMENTS_renderPublicDocument(', 'Document controller does not use the unified renderer.'),
    array($documentRenderer, 'function DOCUMENTS_renderPublicDocument(', 'Unified document renderer is missing.'),
    array($documentRenderer, 'DOCUMENTS_canViewDocument($document, 2)', 'Unified renderer does not enforce visibility.'),
    array($documentRenderer, 'DOCUMENTS_customTemplateReadDir(', 'Custom document templates are no longer supported.'),
    array($documentRenderer, "\$type === 'marker'", 'Maps marker fields are no longer represented.'),
    array($documentRenderer, "\$type === 'album'", 'MediaGallery album fields are no longer represented.'),
    array($documentRenderer, 'CMT_userComments(', 'Document comments are missing.'),
    array($documentRenderer, 'SET hits=hits+1', 'Document hit counting is missing.'),
    array($documentRenderer, '<dl class="documents-properties">', 'Editorial structured-property rendering is missing.'),

    array($documentSave, 'SEC_checkToken()', 'Document writes are not CSRF protected.'),
    array($documentSave, 'DOCUMENTS_canEditDocument($existing)', 'Document save does not enforce edit permissions.'),
    array($documentSave, 'DOCUMENTS_lockSecurityFields(', 'Document save does not lock forged security fields.'),
    array($documentSave, 'DOCUMENTS_isPubliclyIndexable(', 'Document save does not track public transitions.'),
    array($documentSave, 'DOCUMENTS_notifyPublicTransition(', 'Document save does not emit public lifecycle transitions.'),
    array($documentDelete, 'DOCUMENTS_deleteDocumentSecure(', 'Secure document deletion is missing.'),

    array($imageEndpoint, 'DOCUMENTS_canViewImageReference(', 'Image endpoint does not resolve document references.'),
    array($imageEndpoint, 'DOCUMENTS_canViewDocument($document, 2)', 'Protected images do not enforce document visibility.'),
    array($imageEndpoint, 'Cache-Control: private, no-store, max-age=0', 'Protected image caching remains unsafe.'),

    array($functions, 'function DOCUMENTS_dataDir()', 'Multisite-safe data directory helper is missing.'),
    array($functions, 'function DOCUMENTS_hasMaps()', 'Maps availability helper is missing.'),
    array($functions, 'function DOCUMENTS_hasMediaGallery()', 'MediaGallery availability helper is missing.'),
    array($functions, 'function plugin_whatsnewsupported_documents()', 'Geeklog What\'s New support is missing.'),
    array($functions, 'function plugin_getwhatsnew_documents()', 'Geeklog What\'s New content callback is missing.'),

    array($interop, 'function plugin_getiteminfo_documents(', 'Generic Item Info callback is missing.'),
    array($interop, 'function plugin_idtourl_documents(', 'Canonical ID-to-URL callback is missing.'),
    array($interop, 'function plugin_urltoid_documents(', 'Canonical URL-to-ID callback is missing.'),
    array($interop, 'function plugin_collectSitemapItems_documents(', 'Sitemap callback is missing.'),
    array($indexability, 'function DOCUMENTS_isPubliclyIndexable(', 'Indexability service is missing.'),
    array($indexability, 'function DOCUMENTS_notifyPublicTransition(', 'Public lifecycle transition service is missing.'),
    array($seo, 'application/ld+json', 'JSON-LD output is missing.'),
    array($seo, 'BreadcrumbList', 'Breadcrumb structured data is missing.'),
    array($seo, 'metadescription', 'Document/category meta description support is missing.'),

    array($embeds, 'function plugin_autotags_documents(', 'Documents autotags are missing.'),
    array($embeds, 'function phpblock_documents_recent()', 'Recent Documents block callback is missing.'),
    array($embeds, 'function phpblock_documents_popular()', 'Popular Documents block callback is missing.'),
    array($distribution, 'function plugin_getfeednames_documents()', 'Feed names callback is missing.'),
    array($distribution, 'function plugin_getfeedcontent_documents(', 'Feed content callback is missing.'),
    array($distribution, 'function plugin_statssummary_documents()', 'Statistics summary callback is missing.'),
    array($distribution, 'function plugin_showstats_documents()', 'Statistics ranking callback is missing.'),
    array($storage, 'function DOCUMENTS_migrateLegacyData()', 'Legacy data migration helper is missing.'),
    array($storage, 'if (file_exists($targetPath))', 'Storage migration overwrite guard is missing.'),

    array($maps, "PLG_invokeService('maps', 'marker_save'", 'Maps mutations are not delegated to the Maps service.'),
    array($maps, 'function DOCUMENTS_mapsDeactivateMarker(', 'Maps marker withdrawal service boundary is missing.'),
    array($mediaGallery, 'function DOCUMENTS_mediaGalleryAlbumSelect(', 'MediaGallery selector adapter is missing.'),
    array($documentForm, 'DOCUMENTS_mediaGalleryAlbumSelect(', 'Document form does not use the MediaGallery adapter.'),

    array($categoryTemplate, 'name="metadescription"', 'Dedicated category metadescription is missing.'),
    array($categoryTemplate, 'name="cat_help"', 'Category help field must remain distinct from metadescription.'),
    array($documentTemplate, '<article', 'Default document template is not semantic article markup.'),
    array($documentTemplate, '{main_image}', 'Default editorial template does not expose its main image zone.'),
    array($documentTemplate, '{main_content}', 'Default editorial template does not expose its main content zone.'),
    array($documentTemplate, '{properties}', 'Default editorial template does not expose structured properties.'),

    array($package, 'PHP 5.6 regression tests', 'Release package is not gated by PHP 5.6 regression tests.'),
    array($package, 'PHP 8.1 regression tests', 'Release package is not gated by PHP 8.1 regression tests.'),
    array($package, 'unzip -t dist/documents_1.2.0-2.1.1.zip', 'Release archive integrity check is missing.')
);

foreach ($checks as $check) {
    DOCUMENTS_rcRequireContains($check[0], $check[1], $check[2], $failures);
}

DOCUMENTS_rcRequireAbsent($runtime, 'presentation.php', 'Runtime must not initialize presentation implicitly.', $failures);
DOCUMENTS_rcRequireAbsent($runtime, 'DOCUMENTS_startNavigationBuffer()', 'Runtime must not start navigation buffering.', $failures);
DOCUMENTS_rcRequireAbsent($navigation, 'ob_start(', 'Navigation must not mutate output through buffering.', $failures);
DOCUMENTS_rcRequireAbsent($publicIndex, 'DOCUMENTS_LEGACY_SAVE_DISPATCH', 'Legacy document save bypass has reappeared.', $failures);
DOCUMENTS_rcRequireAbsent($documentController, 'include_html.php', 'Document controller must not fall back to the removed historical renderer.', $failures);
DOCUMENTS_rcRequireAbsent($maps, 'maps_markers', 'Maps adapter must not access Maps marker storage directly.', $failures);
DOCUMENTS_rcRequireAbsent($maps, 'maps_maps', 'Maps adapter must not access Maps map storage directly.', $failures);
DOCUMENTS_rcRequireAbsent($maps, 'COM_makeSid(', 'Documents must not allocate Maps marker IDs.', $failures);
DOCUMENTS_rcRequireAbsent($mediaGallery, "\$_TABLES['mg_", 'MediaGallery adapter must not access MediaGallery tables directly.', $failures);
DOCUMENTS_rcRequireAbsent($storage, 'unlink($source', 'Storage migration appears to delete legacy source data.', $failures);
DOCUMENTS_rcRequireAbsent($documentTemplate, 'plusone.js', 'Obsolete Google+ script remains.', $failures);

if (substr_count($compat, 'function DOCUMENTS_canViewDocument(') !== 1) {
    $failures[] = 'DOCUMENTS_canViewDocument must have exactly one implementation.';
}
if (substr_count($security, 'function DOCUMENTS_lockSecurityFields(') !== 1) {
    $failures[] = 'DOCUMENTS_lockSecurityFields must have exactly one implementation.';
}
if (is_file($root . '/sql/mssql_install.php')) {
    $failures[] = 'Obsolete MSSQL support is still present.';
}
if (is_file($root . '/include_edit.php') || is_file($root . '/include_html.php')) {
    $failures[] = 'Removed historical document controllers have reappeared.';
}
if (is_file($root . '/admin/timthumb.php') || is_file($root . '/public_html/timthumb-config.php')) {
    $failures[] = 'Legacy TimThumb files remain.';
}

$requiredTests = array(
    'tests/storage_migration_test.php',
    'tests/config_upgrade_test.php',
    'tests/language_sync_test.php',
    'tests/document_visibility_test.php',
    'tests/document_edit_security_test.php',
    'tests/comment_security_test.php',
    'tests/presentation_test.php',
    'tests/integration_surface_test.php',
    'tests/metadata_consistency_test.php',
    'tests/seo_interoperability_test.php',
    'tests/navigation_test.php',
    'tests/marker_integration_test.php',
    'tests/mediagallery_adapter_test.php'
);
foreach ($requiredTests as $requiredTest) {
    if (!is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $requiredTest))) {
        $failures[] = 'Missing stabilization test: ' . $requiredTest;
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents release-candidate checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents 1.2.0 release-candidate static checks: PASS\n";
