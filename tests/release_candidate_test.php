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
$installDefaults = DOCUMENTS_rcRead($root, 'install_defaults.php', $failures);
$installUpdates = DOCUMENTS_rcRead($root, 'install_updates.php', $failures);
$mysqlInstall = DOCUMENTS_rcRead($root, 'sql/mysql_install.php', $failures);
$functions = DOCUMENTS_rcRead($root, 'functions.inc', $failures);
$compat = DOCUMENTS_rcRead($root, 'include_compat.php', $failures);
$security = DOCUMENTS_rcRead($root, 'security.php', $failures);
$runtime = DOCUMENTS_rcRead($root, 'runtime.php', $failures);
$presentation = DOCUMENTS_rcRead($root, 'presentation.php', $failures);
$navigation = DOCUMENTS_rcRead($root, 'navigation.php', $failures);
$interop = DOCUMENTS_rcRead($root, 'interoperability.php', $failures);
$indexability = DOCUMENTS_rcRead($root, 'indexability.php', $failures);
$seo = DOCUMENTS_rcRead($root, 'seo.php', $failures);
$storage = DOCUMENTS_rcRead($root, 'storage.php', $failures);
$distribution = DOCUMENTS_rcRead($root, 'distribution.php', $failures);
$embeds = DOCUMENTS_rcRead($root, 'embeds.php', $failures);
$publicIndex = DOCUMENTS_rcRead($root, 'public_html/index.php', $failures);
$home = DOCUMENTS_rcRead($root, 'public_html/home.php', $failures);
$category = DOCUMENTS_rcRead($root, 'public_html/category.php', $failures);
$documentController = DOCUMENTS_rcRead($root, 'public_html/document.php', $failures);
$documentRenderer = DOCUMENTS_rcRead($root, 'public_document.php', $failures);
$documentSave = DOCUMENTS_rcRead($root, 'public_html/document-save.php', $failures);
$adminIndex = DOCUMENTS_rcRead($root, 'admin/index.php', $failures);
$adminAjax = DOCUMENTS_rcRead($root, 'admin/ajax.php', $failures);
$imageEndpoint = DOCUMENTS_rcRead($root, 'public_html/image.php', $failures);
$includeEdit = DOCUMENTS_rcRead($root, 'include_edit.php', $failures);
$includeLists = DOCUMENTS_rcRead($root, 'include_lists.php', $failures);
$categoryTemplate = DOCUMENTS_rcRead($root, 'templates/cat_form.thtml', $failures);
$documentTemplate = DOCUMENTS_rcRead($root, 'templates/document.thtml', $failures);

$checks = array(
    array($autoinstall, "'pi_version' => '1.2.0'", 'Plugin metadata is not set to 1.2.0.'),
    array($autoinstall, "define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1')", 'Minimum Geeklog version is not 2.1.1.'),
    array($autoinstall, "define('DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE', '2.2.3')", 'Geeklog compatibility range is incorrect.'),
    array($autoinstall, "define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0')", 'Minimum PHP version is not 5.6.0.'),
    array($autoinstall, "define('DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE', '8.2.0')", 'PHP compatibility range is incorrect.'),
    array($autoinstall, "define('DOCUMENTS_SUPPORTED_DBMS', 'mysql')", 'Supported DBMS declaration is missing.'),
    array($installUpdates, 'function DOCUMENTS_updateSchema_1_2_0()', '1.2.0 schema upgrade helper is missing.'),
    array($mysqlInstall, 'metadescription varchar(255)', 'Fresh schema is missing category metadescription.'),
    array($installDefaults, "method_exists(\$c, 'get_config')", 'Older Geeklog configuration fallback is missing.'),

    array($compat, 'function DOCUMENTS_canViewDocument(', 'Central visibility guard is missing.'),
    array($compat, 'function DOCUMENTS_canEditDocument(', 'Central edit guard is missing.'),
    array($compat, 'function DOCUMENTS_normalizeDocumentStatus(', 'Workflow state normalizer is missing.'),
    array($security, 'function DOCUMENTS_lockSecurityFields(', 'Security-field lock helper is missing.'),
    array($security, 'function DOCUMENTS_prepareDocumentFieldRequest(', 'Dynamic field request normalizer is missing.'),

    array($runtime, "'security.php'", 'Runtime does not load security services.'),
    array($runtime, "'navigation.php'", 'Runtime does not expose navigation helpers.'),
    array($runtime, "'interoperability.php'", 'Runtime does not load interoperability services.'),
    array($runtime, "'indexability.php'", 'Runtime does not load indexability services.'),
    array($home, "'presentation.php'", 'Home does not explicitly load presentation helpers.'),
    array($category, "'presentation.php'", 'Category does not explicitly load presentation helpers.'),
    array($documentController, "'presentation.php'", 'Document controller does not explicitly load presentation helpers.'),
    array($presentation, 'function DOCUMENTS_formatTextDisplay(', 'Text presentation helper is missing.'),
    array($presentation, 'function DOCUMENTS_homeStatsBlock(', 'Home statistics helper is missing.'),
    array($home, 'DOCUMENTS_homeStatsBlock()', 'Home no longer renders statistics explicitly.'),

    array($navigation, 'function DOCUMENTS_renderNavigation()', 'Navigation renderer is missing.'),
    array($navigation, '/plugins/documents/index.php', 'Public navigation is not linked to dedicated administration.'),
    array($publicIndex, "if (\$mode === 'view')", 'Public read routing is missing.'),
    array($publicIndex, "if (\$mode === 'new' || \$mode === 'edit')", 'Public contribution routing is missing.'),
    array($publicIndex, '$adminModes', 'Historical admin URL compatibility bridge is missing.'),
    array($adminIndex, '$adminViews', 'Dedicated admin view router is missing.'),
    array($adminIndex, '$adminSaveModes', 'Dedicated admin mutation router is missing.'),
    array($adminIndex, "SEC_hasRights('documents.admin')", 'Dedicated administration is not access controlled.'),
    array($adminIndex, 'SEC_checkToken()', 'Dedicated admin writes are not CSRF protected.'),

    array($documentController, 'DOCUMENTS_renderPublicDocument(', 'Document controller does not use the unified renderer.'),
    array($documentRenderer, 'function DOCUMENTS_renderPublicDocument(', 'Unified document renderer is missing.'),
    array($documentRenderer, 'DOCUMENTS_canViewDocument($document, 2)', 'Unified renderer does not enforce visibility.'),
    array($documentRenderer, 'DOCUMENTS_customTemplateReadDir(', 'Custom document templates are no longer supported.'),
    array($documentRenderer, "\$type === 'marker'", 'Maps marker fields are no longer represented.'),
    array($documentRenderer, "\$type === 'album'", 'MediaGallery album fields are no longer represented.'),
    array($documentRenderer, 'CMT_userComments(', 'Document comments are missing.'),
    array($documentRenderer, 'SET hits=hits+1', 'Document hit counting is missing.'),
    array($documentRenderer, 'DOCUMENTS_renderNavigation()', 'Document navigation is not explicit.'),

    array($documentSave, 'SEC_checkToken()', 'Document writes are not CSRF protected.'),
    array($documentSave, 'DOCUMENTS_canEditDocument($existing)', 'Document save does not enforce edit permissions.'),
    array($documentSave, 'DOCUMENTS_lockSecurityFields(', 'Document save does not lock forged security fields.'),
    array($documentSave, 'SEC_setDefaultPermissions(', 'New document permissions are not server-derived.'),
    array($documentSave, 'DOCUMENTS_isPubliclyIndexable(', 'Document save does not track public transitions.'),

    array($imageEndpoint, 'DOCUMENTS_canViewImageReference(', 'Image endpoint does not resolve document references.'),
    array($imageEndpoint, 'DOCUMENTS_canViewDocument($document, 2)', 'Protected images do not enforce document visibility.'),
    array($imageEndpoint, 'Cache-Control: private, no-store, max-age=0', 'Protected image caching remains unsafe.'),

    array($functions, 'function DOCUMENTS_dataDir()', 'Multisite-safe data directory helper is missing.'),
    array($functions, 'function DOCUMENTS_hasMaps()', 'Maps availability helper is missing.'),
    array($functions, 'function DOCUMENTS_hasMediaGallery()', 'MediaGallery availability helper is missing.'),
    array($functions, 'function plugin_whatsnewsupported_documents()', 'Geeklog What\'s New support is missing.'),
    array($functions, 'function plugin_getwhatsnew_documents()', 'Geeklog What\'s New content callback is missing.'),
    array($functions, "COM_getPermSQL('AND', 0, 2, 'd')", 'Plugin discovery lacks document permission filtering.'),

    array($interop, 'function plugin_getiteminfo_documents(', 'Generic Item Info callback is missing.'),
    array($interop, 'function plugin_idtourl_documents(', 'Canonical ID-to-URL callback is missing.'),
    array($interop, 'function plugin_urltoid_documents(', 'Canonical URL-to-ID callback is missing.'),
    array($interop, 'function plugin_collectSitemapItems_documents(', 'Sitemap callback is missing.'),
    array($interop, "PLG_itemSaved((string) \$id, 'documents')", 'Saved lifecycle event is missing.'),
    array($interop, "PLG_itemDeleted((string) \$id, 'documents')", 'Deleted lifecycle event is missing.'),
    array($indexability, 'function DOCUMENTS_isPubliclyIndexable(', 'Indexability service is missing.'),
    array($seo, 'application/ld+json', 'JSON-LD output is missing.'),
    array($seo, "'schema_type' => 'CreativeWork'", 'CreativeWork schema is missing.'),
    array($seo, "'schema_type' => 'CollectionPage'", 'CollectionPage schema is missing.'),

    array($embeds, 'function plugin_autotags_documents(', 'Documents autotags are missing.'),
    array($embeds, 'function phpblock_documents_recent()', 'Recent Documents block callback is missing.'),
    array($embeds, 'function phpblock_documents_popular()', 'Popular Documents block callback is missing.'),
    array($distribution, 'function plugin_getfeednames_documents()', 'Feed names callback is missing.'),
    array($distribution, 'function plugin_getfeedcontent_documents(', 'Feed content callback is missing.'),
    array($distribution, 'function plugin_statssummary_documents()', 'Statistics summary callback is missing.'),
    array($storage, 'function DOCUMENTS_migrateLegacyData()', 'Legacy data migration helper is missing.'),
    array($storage, 'if (file_exists($targetPath))', 'Storage migration overwrite guard is missing.'),

    array($includeEdit, 'DOCUMENTS_textFormatOptions(', 'Text display format selector is missing.'),
    array($includeLists, '$sql_submissions = ', 'Submission workflow query is missing.'),
    array($includeLists, '$sql_drafts = ', 'Draft workflow query is missing.'),
    array($categoryTemplate, 'name="metadescription"', 'Dedicated category metadescription is missing.'),
    array($categoryTemplate, 'name="cat_help"', 'Category help field must remain distinct from metadescription.'),
    array($documentTemplate, '<article', 'Default document template is not semantic article markup.'),
    array($adminAjax, "SEC_hasRights('documents.admin')", 'Admin AJAX endpoint lacks admin protection.')
);

foreach ($checks as $check) {
    DOCUMENTS_rcRequireContains($check[0], $check[1], $check[2], $failures);
}

DOCUMENTS_rcRequireAbsent($runtime, 'presentation.php', 'Runtime must not initialize presentation implicitly.', $failures);
DOCUMENTS_rcRequireAbsent($runtime, 'DOCUMENTS_startNavigationBuffer()', 'Runtime must not start navigation buffering.', $failures);
DOCUMENTS_rcRequireAbsent($navigation, 'ob_start(', 'Navigation must not mutate output through buffering.', $failures);
DOCUMENTS_rcRequireAbsent($documentController, 'DOCUMENTS_applyDocumentSeo(', 'Obsolete undefined document SEO call remains.', $failures);
DOCUMENTS_rcRequireAbsent($documentController, '$useLegacyRenderer', 'Document controller still selects between two renderers.', $failures);
DOCUMENTS_rcRequireAbsent($storage, 'unlink($source', 'Storage migration appears to delete legacy source data.', $failures);
DOCUMENTS_rcRequireAbsent($documentTemplate, 'plusone.js', 'Obsolete Google+ script remains.', $failures);
DOCUMENTS_rcRequireAbsent($adminAjax, 'SEC_checkToken()', 'Read-only admin AJAX must not consume form CSRF tokens.', $failures);

if (substr_count($compat, 'function DOCUMENTS_canViewDocument(') !== 1) {
    $failures[] = 'DOCUMENTS_canViewDocument must have exactly one implementation.';
}
if (substr_count($security, 'function DOCUMENTS_lockSecurityFields(') !== 1) {
    $failures[] = 'DOCUMENTS_lockSecurityFields must have exactly one implementation.';
}
if (is_file($root . '/sql/mssql_install.php')) {
    $failures[] = 'Obsolete MSSQL support is still present.';
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
    'tests/navigation_test.php'
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
