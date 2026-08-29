<?php

/* Documents 1.2.0 release-candidate static checks. */

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
$integrity = DOCUMENTS_rcRead($root, 'integrity.php', $failures);
$security = DOCUMENTS_rcRead($root, 'security.php', $failures);
$presentation = DOCUMENTS_rcRead($root, 'presentation.php', $failures);
$runtime = DOCUMENTS_rcRead($root, 'runtime.php', $failures);
$storage = DOCUMENTS_rcRead($root, 'storage.php', $failures);
$interop = DOCUMENTS_rcRead($root, 'interoperability.php', $failures);
$embeds = DOCUMENTS_rcRead($root, 'embeds.php', $failures);
$distribution = DOCUMENTS_rcRead($root, 'distribution.php', $failures);
$seo = DOCUMENTS_rcRead($root, 'seo.php', $failures);
$publicIndex = DOCUMENTS_rcRead($root, 'public_html/index.php', $failures);
$imageEndpoint = DOCUMENTS_rcRead($root, 'public_html/image.php', $failures);
$adminAjax = DOCUMENTS_rcRead($root, 'admin/ajax.php', $failures);
$includeHtml = DOCUMENTS_rcRead($root, 'include_html.php', $failures);
$includeEdit = DOCUMENTS_rcRead($root, 'include_edit.php', $failures);
$includeLists = DOCUMENTS_rcRead($root, 'include_lists.php', $failures);
$categoryTemplate = DOCUMENTS_rcRead($root, 'templates/cat_form.thtml', $failures);
$documentTemplate = DOCUMENTS_rcRead($root, 'templates/document.thtml', $failures);

$checks = array(
    array($autoinstall, "'pi_version' => '1.2.0'", 'Plugin metadata is not set to 1.2.0.'),
    array($autoinstall, "define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1')", 'Minimum Geeklog version is not 2.1.1.'),
    array($autoinstall, "define('DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE', '2.2.3')", 'Maximum Geeklog range does not stop before 2.2.3.'),
    array($autoinstall, "define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0')", 'Minimum PHP version is not 5.6.0.'),
    array($autoinstall, "define('DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE', '8.2.0')", 'Maximum PHP range does not stop before 8.2.0.'),
    array($autoinstall, "define('DOCUMENTS_SUPPORTED_DBMS', 'mysql')", 'MySQL/MariaDB-only database support is not declared.'),
    array($autoinstall, '$_DB_dbms !== DOCUMENTS_SUPPORTED_DBMS', 'Unsupported DBMS values are not explicitly rejected.'),
    array($autoinstall, "version_compare(\$installedVersion, '1.2.0', '<')", '1.2.0 schema upgrade step is missing.'),
    array($installUpdates, 'function DOCUMENTS_updateSchema_1_2_0()', '1.2.0 schema upgrade helper is missing.'),
    array($mysqlInstall, 'metadescription varchar(255)', 'Fresh install schema does not include category metadescription.'),
    array($installDefaults, "method_exists(\$c, 'get_config')", 'Older Geeklog configuration compatibility fallback is missing.'),
    array($functions, 'function DOCUMENTS_dataDir()', 'Multisite-safe data directory helper is missing.'),
    array($functions, "basename(\$base) . '-documents'", 'Documents data directory is not derived from path_data.'),
    array($storage, 'function DOCUMENTS_migrateLegacyData()', 'Legacy persistent-data migration helper is missing.'),
    array($storage, 'if (file_exists($targetPath))', 'Migration no-overwrite guard is missing.'),
    array($publicIndex, 'SEC_checkToken()', 'Mutating public/admin controller routes are missing CSRF validation.'),
    array($adminAjax, "SEC_hasRights('documents.admin')", 'Admin AJAX endpoint is missing documents.admin protection.'),
    array($compat, 'function DOCUMENTS_canViewDocument(', 'Central document visibility guard is missing.'),
    array($compat, 'function DOCUMENTS_canEditDocument(', 'Central document edit guard is missing.'),
    array($compat, 'function DOCUMENTS_normalizeDocumentStatus(', 'Server-side workflow state normalizer is missing.'),
    array($security, 'function DOCUMENTS_lockSecurityFields(', 'Server-side ownership/permission lock helper is missing.'),
    array($security, 'function DOCUMENTS_normalizeFieldInput(', 'Dynamic scalar field normalizer is missing.'),
    array($security, 'function DOCUMENTS_plainTextInput(', 'Plain-text input sanitizer is missing.'),
    array($security, 'function DOCUMENTS_prepareDocumentFieldRequest(', 'Dynamic field request normalizer is missing.'),
    array($runtime, 'security.php', 'Runtime does not load the security helper module.'),
    array($runtime, 'presentation.php', 'Runtime does not load presentation helpers.'),
    array($presentation, 'function DOCUMENTS_formatTextDisplay(', 'Text display normalization helper is missing.'),
    array($presentation, 'function DOCUMENTS_homeStatsBlock(', 'Documents home statistics helper is missing.'),
    array($presentation, 'if ($visibility >= 3)', 'Anonymous/public statistics visibility is missing.'),
    array($includeEdit, 'DOCUMENTS_textFormatOptions(', 'Text-field display format selector is missing.'),
    array($includeHtml, 'DOCUMENTS_formatTextDisplay(', 'Text-field display formatting is not applied when rendering.'),
    array($functions, 'function plugin_whatsnewsupported_documents()', 'Geeklog What\'s New support callback is missing.'),
    array($functions, 'function plugin_getwhatsnew_documents()', 'Geeklog What\'s New content callback is missing.'),
    array($functions, ' AS description', 'Geeklog search result description is missing.'),
    array($runtime, 'DOCUMENTS_homeStatsBlock()', 'Documents home page does not inject the configured statistics block.'),
    array($publicIndex, 'DOCUMENTS_canViewDocument($documentsViewRow, 2)', 'Public document routes do not use the central visibility guard.'),
    array($publicIndex, 'DOCUMENTS_canEditDocument($documentsExisting)', 'Edit/save routes do not use the central edit guard.'),
    array($publicIndex, '$documentsRequestedCid !== $documentsActualCid', 'Edit/save routes do not bind the submitted category to the real document category.'),
    array($publicIndex, 'DOCUMENTS_normalizeDocumentStatus(', 'Save routes do not normalize workflow state server-side.'),
    array($publicIndex, 'DOCUMENTS_lockSecurityFields(', 'Save routes do not replace forged ownership/permission fields.'),
    array($publicIndex, '$documentsTrustedPermissions = array(', 'Existing document saves do not preserve stored permissions for non-admin users.'),
    array($publicIndex, 'SEC_setDefaultPermissions($documentsDefaults, $_DOCUMENTS_CONF[\'default_permissions\'])', 'New non-admin documents do not use server-side default permissions.'),
    array($imageEndpoint, 'DOCUMENTS_canViewImageReference(', 'Image endpoint does not resolve image references to Documents rows.'),
    array($imageEndpoint, 'DOCUMENTS_canViewDocument($document, 2)', 'Image endpoint does not enforce document visibility.'),
    array($imageEndpoint, 'Cache-Control: private, no-store, max-age=0', 'Image endpoint still permits public caching of protected images.'),
    array($functions, 'WHERE d.active=1', 'Plugin search / What\'s New is not restricted to active documents.'),
    array($functions, "COM_getPermSQL('AND', 0, 2, 'd')", 'Plugin search / What\'s New is missing document permission filtering.'),
    array($includeLists, '$workflowOwnerFilter = \' AND d.owner_id=\' . (int) $_USER[\'uid\'];', 'Draft/submission lists are not restricted to the current owner for non-admin users.'),
    array($includeLists, '$sql_submissions = ', 'Submission workflow query is missing.'),
    array($includeLists, '$sql_drafts = ', 'Draft workflow query is missing.'),
    array($functions, 'function DOCUMENTS_hasMaps()', 'Optional Maps availability helper is missing.'),
    array($functions, 'function DOCUMENTS_hasMediaGallery()', 'Optional MediaGallery availability helper is missing.'),
    array($includeHtml . $includeEdit, 'DOCUMENTS_hasMaps()', 'Maps integration is not guarded by the optional dependency helper.'),
    array($includeHtml . $includeEdit, 'DOCUMENTS_hasMediaGallery()', 'MediaGallery integration is not guarded by the optional dependency helper.'),
    array($interop, 'function plugin_getiteminfo_documents(', 'Generic Item Info callback is missing.'),
    array($interop, 'function plugin_idtourl_documents(', 'Canonical ID-to-URL callback is missing.'),
    array($interop, 'function plugin_urltoid_documents(', 'Canonical URL-to-ID callback is missing.'),
    array($interop, 'function plugin_collectSitemapItems_documents(', 'Sitemap collection callback is missing.'),
    array($interop, 'PLG_itemSaved((string) $id, \'documents\')', 'Documents saved lifecycle event is missing.'),
    array($interop, 'PLG_itemDeleted((string) $id, \'documents\')', 'Documents deleted lifecycle event is missing.'),
    array($embeds, 'function plugin_autotags_documents(', 'Documents autotags are missing.'),
    array($embeds, 'function phpblock_documents_recent()', 'Recent Documents block callback is missing.'),
    array($embeds, 'function phpblock_documents_popular()', 'Popular Documents block callback is missing.'),
    array($embeds, "'distribution.php'", 'Syndication/statistics callbacks are not loaded by the plugin API chain.'),
    array($distribution, 'function plugin_getfeednames_documents()', 'Documents feed names callback is missing.'),
    array($distribution, 'function plugin_getfeedcontent_documents(', 'Documents feed content callback is missing.'),
    array($distribution, 'function plugin_feedupdatecheck_documents(', 'Documents feed update callback is missing.'),
    array($distribution, 'function plugin_statssummary_documents()', 'Documents statistics summary callback is missing.'),
    array($distribution, 'function plugin_showstats_documents()', 'Documents detailed statistics callback is missing.'),
    array($seo, 'application/ld+json', 'JSON-LD output is missing.'),
    array($seo, "'schema_type' => 'CreativeWork'", 'CreativeWork schema is missing.'),
    array($seo, "'schema_type' => 'CollectionPage'", 'CollectionPage schema is missing.'),
    array($categoryTemplate, 'name="metadescription"', 'Dedicated category metadescription field is missing.'),
    array($categoryTemplate, 'name="cat_help"', 'Category help field must remain separate from metadescription.'),
    array($documentTemplate, '<article', 'Default document template is not semantic article markup.')
);

foreach ($checks as $check) {
    DOCUMENTS_rcRequireContains($check[0], $check[1], $check[2], $failures);
}

if (substr_count($compat . $integrity, 'function DOCUMENTS_canViewDocument(') !== 1) {
    $failures[] = 'DOCUMENTS_canViewDocument must have exactly one runtime implementation.';
}
if (substr_count($compat . $security, 'function DOCUMENTS_lockSecurityFields(') !== 1) {
    $failures[] = 'DOCUMENTS_lockSecurityFields must have exactly one runtime implementation.';
}
if (substr_count($includeLists, '. $workflowOwnerFilter;') < 2) {
    $failures[] = 'Draft/submission workflow queries do not both apply the owner filter.';
}

DOCUMENTS_rcRequireAbsent(
    $adminAjax,
    'SEC_checkToken()',
    'Read-only admin AJAX must not consume the one-time form CSRF token.',
    $failures
);

DOCUMENTS_rcRequireAbsent($storage, 'unlink($source', 'Storage migration appears to delete legacy source data.', $failures);
DOCUMENTS_rcRequireAbsent($documentTemplate, 'plusone.js', 'Obsolete Google+ script remains in default template.', $failures);

if (is_file($root . '/sql/mssql_install.php')) {
    $failures[] = 'Obsolete MSSQL support is still present.';
}
if (is_file($root . '/admin/timthumb.php')) {
    $failures[] = 'Legacy admin TimThumb is still present.';
}
if (is_file($root . '/public_html/timthumb-config.php')) {
    $failures[] = 'Legacy public TimThumb configuration is still present.';
}
DOCUMENTS_rcRequireAbsent(
    strtolower($publicIndex . $includeHtml . $includeEdit),
    'timthumb.php',
    'A runtime reference to timthumb.php remains.',
    $failures
);

$installSources = array(
    'autoinstall.php' => $autoinstall,
    'install_defaults.php' => $installDefaults,
    'install_updates.php' => $installUpdates
);
foreach ($installSources as $sourceFile => $source) {
    DOCUMENTS_rcRequireAbsent(
        $source,
        'mail($_CONF',
        'Possible direct install/upgrade telemetry mail remains in ' . $sourceFile . '.',
        $failures
    );
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
    'tests/seo_interoperability_test.php'
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
