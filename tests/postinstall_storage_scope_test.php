<?php

$root = dirname(__DIR__);
$failures = array();

function documents_postinstall_read($root, $path, &$failures)
{
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($file)) {
        $failures[] = 'Missing file: ' . $path;
        return '';
    }
    $content = file_get_contents($file);
    if ($content === false) {
        $failures[] = 'Unable to read: ' . $path;
        return '';
    }
    return $content;
}

function documents_postinstall_code_only($content)
{
    $tokens = token_get_all($content);
    $code = '';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                continue;
            }
            $code .= $token[1];
        } else {
            $code .= $token;
        }
    }
    return $code;
}

$autoinstall = documents_postinstall_read($root, 'autoinstall.php', $failures);
$storage = documents_postinstall_read($root, 'storage.php', $failures);
$autoinstallCode = documents_postinstall_code_only($autoinstall);
$storageCode = documents_postinstall_code_only($storage);

$start = strpos($autoinstallCode, 'function DOCUMENTS_runStorageMigration()');
$end = ($start !== false) ? strpos($autoinstallCode, 'function DOCUMENTS_runUpgradeSteps', $start) : false;
$section = ($start !== false && $end !== false)
    ? substr($autoinstallCode, $start, $end - $start)
    : '';

if ($section === '') {
    $failures[] = 'Unable to isolate DOCUMENTS_runStorageMigration().';
} else {
    if (strpos($section, 'functions.inc') !== false) {
        $failures[] = 'Post-install storage migration must not require functions.inc from function scope.';
    }
    if (strpos($section, "plugins/documents/storage.php") === false) {
        $failures[] = 'Post-install storage migration must load storage.php directly.';
    }
}

if (strpos($storageCode, "if (!function_exists('DOCUMENTS_dataDir'))") === false
    || strpos($storageCode, "if (!function_exists('DOCUMENTS_legacyDataDir'))") === false) {
    $failures[] = 'storage.php must provide guarded path helpers for autoinstall use.';
}

if (strpos($storageCode, '$_DB_table_prefix') !== false) {
    $failures[] = 'storage.php must not depend on the Geeklog database table prefix.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents post-install storage scope checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents post-install storage scope checks: PASS\n";
