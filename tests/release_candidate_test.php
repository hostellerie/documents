<?php

/* Documents 1.1.9 release-candidate static checks. */

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
$functions = DOCUMENTS_rcRead($root, 'functions.inc', $failures);
$compat = DOCUMENTS_rcRead($root, 'include_compat.php', $failures);
$security = DOCUMENTS_rcRead($root, 'security.php', $failures);
$runtime = DOCUMENTS_rcRead($root, 'runtime.php', $failures);
$storage = DOCUMENTS_rcRead($root, 'storage.php', $failures);
$publicIndex = DOCUMENTS_rcRead($root, 'public_html/index.php', $failures);
$adminAjax = DOCUMENTS_rcRead($root, 'admin/ajax.php', $failures);
$includeHtml = DOCUMENTS_rcRead($root, 'include_html.php', $failures);
$includeEdit = DOCUMENTS_rcRead($root, 'include_edit.php', $failures);
$includeLists = DOCUMENTS_rcRead($root, 'include_lists.php', $failures);

$checks = array(
    array($autoinstall, "'pi_version'      => '1.1.9'", 'Plugin metadata is not set to 1.1.9.'),
    array($autoinstall, "define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1')", 'Minimum Geeklog version is not 2.1.1.'),
    array($autoinstall, "define('DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE', '2.2.3')", 'Maximum Geeklog range does not stop before 2.2.3.'),
    array($autoinstall, "define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0')", 'Minimum PHP version is not 5.6.0.'),
    array($autoinstall, "define('DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE', '8.2.0')", 'Maximum PHP range does not stop before 8.2.0.'),
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
    array($security, 'function DOCUMENTS_prepareDocumentFieldRequest(', 'Dynamic field request normalizer is missing.'),
    array($runtime, 'security.php', 'Runtime does not load the security helper module.'),
    array($publicIndex, 'DOCUMENTS_canViewDocument($documentsViewRow, 2)', 'Public document routes do not use the central visibility guard.'),
    array($publicIndex, 'DOCUMENTS_canEditDocument($documentsExisting)', 'Edit/save routes do not use the central edit guard.'),
    array($publicIndex, '$documentsRequestedCid !== $documentsActualCid', 'Edit/save routes do not bind the submitted category to the real document category.'),
    array($publicIndex, 'DOCUMENTS_normalizeDocumentStatus(', 'Save routes do not normalize workflow state server-side.'),
    array($publicIndex, 'DOCUMENTS_lockSecurityFields(', 'Save routes do not replace forged ownership/permission fields.'),
    array($publicIndex, '$documentsTrustedPermissions = array(', 'Existing document saves do not preserve stored permissions for non-admin users.'),
    array($publicIndex, 'SEC_setDefaultPermissions($documentsDefaults, $_DOCUMENTS_CONF[\'default_permissions\'])', 'New non-admin documents do not use server-side default permissions.'),
    array($functions, 'WHERE d.active = 1', 'Plugin search is not restricted to active documents.'),
    array($functions, "COM_getPermSQL('AND', 0, 2, 'd')", 'Plugin search is missing document permission filtering.'),
    array($includeLists, '$workflowOwnerFilter = \' AND d.owner_id=\' . (int) $_USER[\'uid\'];', 'Draft/submission lists are not restricted to the current owner for non-admin users.'),
    array($includeLists, 'AND d.active=3"\n        . $workflowOwnerFilter', 'Submission list does not apply the workflow owner filter.'),
    array($includeLists, 'AND d.active=2"\n        . $workflowOwnerFilter', 'Draft list does not apply the workflow owner filter.'),
    array($functions, 'function DOCUMENTS_hasMaps()', 'Optional Maps availability helper is missing.'),
    array($functions, 'function DOCUMENTS_hasMediaGallery()', 'Optional MediaGallery availability helper is missing.'),
    array($includeHtml . $includeEdit, 'DOCUMENTS_hasMaps()', 'Maps integration is not guarded by the optional dependency helper.'),
    array($includeHtml . $includeEdit, 'DOCUMENTS_hasMediaGallery()', 'MediaGallery integration is not guarded by the optional dependency helper.')
);

foreach ($checks as $check) {
    DOCUMENTS_rcRequireContains($check[0], $check[1], $check[2], $failures);
}

DOCUMENTS_rcRequireAbsent($storage, 'unlink($source', 'Storage migration appears to delete legacy source data.', $failures);

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

$sourceFiles = array(
    'autoinstall.php', 'functions.inc', 'include_edit.php', 'include_html.php',
    'include_lists.php', 'security.php', 'runtime.php', 'admin/ajax.php', 'admin/index.php',
    'public_html/index.php', 'public_html/image.php'
);

foreach ($sourceFiles as $sourceFile) {
    $source = DOCUMENTS_rcRead($root, $sourceFile, $failures);
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
    'tests/metadata_consistency_test.php'
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

echo "Documents release-candidate static checks: PASS\n";