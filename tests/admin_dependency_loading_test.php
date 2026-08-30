<?php

$root = dirname(__DIR__);
$endpoint = file_get_contents($root . '/public_html/admin-save.php');
$integrity = file_get_contents($root . '/integrity.php');
$compat = file_get_contents($root . '/include_compat.php');

$failures = array();

if (strpos($integrity, 'function DOCUMENTS_normalizeRouteSlug') === false) {
    $failures[] = 'integrity.php does not define DOCUMENTS_normalizeRouteSlug().';
}
if (strpos($compat, 'function DOCUMENTS_templateName') === false) {
    $failures[] = 'include_compat.php does not define DOCUMENTS_templateName().';
}
if (strpos($endpoint, "require_once $pluginPath . 'integrity.php';") === false) {
    $failures[] = 'admin-save.php does not load integrity.php for slug normalization.';
}
if (strpos($endpoint, "require_once $pluginPath . 'include_compat.php';") === false) {
    $failures[] = 'admin-save.php does not load include_compat.php for admin mutation helpers.';
}

$integrityPos = strpos($endpoint, "require_once $pluginPath . 'integrity.php';");
$mutationsPos = strpos($endpoint, "require_once $pluginPath . 'admin_mutations.php';");
if ($integrityPos === false || $mutationsPos === false || $integrityPos > $mutationsPos) {
    $failures[] = 'integrity.php must be loaded before admin_mutations.php.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents admin dependency checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents admin dependency checks: PASS\n";
