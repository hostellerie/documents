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
$storage = DOCUMENTS_rcRead($root, 'storage.php', $failures);
$publicIndex = DOCUMENTS_rcRead($root, 'public_html/index.php', $failures);
$adminAjax = DOCUMENTS_rcRead($root, 'admin/ajax.php', $failures);
$includeHtml = DOCUMENTS_rcRead($root, 'include_html.php', $failures);
$includeEdit = DOCUMENTS_rcRead($root, 'include_edit.php', $failures);

DOCUMENTS_rcRequireContains(
    $autoinstall,
    "define('DOCUMENTS_MIN_GEEKLOG_VERSION', '2.1.1')",
    'Minimum Geeklog version is not 2.1.1.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $autoinstall,
    "define('DOCUMENTS_MAX_GEEKLOG_VERSION_EXCLUSIVE', '2.2.3')",
    'Maximum Geeklog range does not stop before 2.2.3.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $autoinstall,
    "define('DOCUMENTS_MIN_PHP_VERSION', '5.6.0')",
    'Minimum PHP version is not 5.6.0.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $autoinstall,
    "define('DOCUMENTS_MAX_PHP_VERSION_EXCLUSIVE', '8.2.0')",
    'Maximum PHP range does not stop before 8.2.0.',
    $failures
);

DOCUMENTS_rcRequireContains(
    $functions,
    'function DOCUMENTS_dataDir()',
    'Multisite-safe data directory helper is missing.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $functions,
    "basename(\$base) . '-documents'",
    'Documents data directory is not derived from path_data.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $storage,
    'function DOCUMENTS_migrateLegacyData()',
    'Legacy persistent-data migration helper is missing.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $storage,
    'if (file_exists($targetPath))',
    'Migration no-overwrite guard is missing.',
    $failures
);
DOCUMENTS_rcRequireAbsent(
    $storage,
    'unlink($source',
    'Storage migration appears to delete legacy source data.',
    $failures
);

DOCUMENTS_rcRequireContains(
    $publicIndex,
    'SEC_checkToken()',
    'Mutating public/admin controller routes are missing CSRF validation.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $adminAjax,
    "SEC_hasRights('documents.admin')",
    'Admin AJAX endpoint is missing documents.admin protection.',
    $failures
);

DOCUMENTS_rcRequireContains(
    $functions,
    'function DOCUMENTS_hasMaps()',
    'Optional Maps availability helper is missing.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $functions,
    'function DOCUMENTS_hasMediaGallery()',
    'Optional MediaGallery availability helper is missing.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $includeHtml . $includeEdit,
    'DOCUMENTS_hasMaps()',
    'Maps integration is not guarded by the optional dependency helper.',
    $failures
);
DOCUMENTS_rcRequireContains(
    $includeHtml . $includeEdit,
    'DOCUMENTS_hasMediaGallery()',
    'MediaGallery integration is not guarded by the optional dependency helper.',
    $failures
);

if (is_file($root . '/admin/timthumb.php')) {
    $failures[] = 'Legacy admin TimThumb is still present.';
}
if (is_file($root . '/public_html/timthumb-config.php')) {
    $failures[] = 'Legacy public TimThumb configuration is still present.';
}

$sourceFiles = array(
    'autoinstall.php',
    'functions.inc',
    'include_edit.php',
    'include_html.php',
    'include_lists.php',
    'admin/ajax.php',
    'admin/index.php',
    'public_html/index.php',
    'public_html/image.php'
);

foreach ($sourceFiles as $sourceFile) {
    $source = DOCUMENTS_rcRead($root, $sourceFile, $failures);
    DOCUMENTS_rcRequireAbsent(
        strtolower($source),
        'timthumb',
        'Legacy TimThumb reference remains in ' . $sourceFile . '.',
        $failures
    );
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
    'tests/language_sync_test.php'
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
