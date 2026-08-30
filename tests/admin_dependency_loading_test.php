<?php

$root = dirname(__DIR__);
$endpoint = file_get_contents($root . '/public_html/admin-save.php');
$mutations = file_get_contents($root . '/admin_mutations.php');
$integrity = file_get_contents($root . '/integrity.php');
$compat = file_get_contents($root . '/include_compat.php');

$failures = array();

if (strpos($integrity, 'function DOCUMENTS_normalizeRouteSlug') === false) {
    $failures[] = 'integrity.php does not define DOCUMENTS_normalizeRouteSlug().';
}
if (strpos($compat, 'function DOCUMENTS_templateName') === false) {
    $failures[] = 'include_compat.php does not define DOCUMENTS_templateName().';
}

$integrityNeedle = "require_once \$pluginPath . 'integrity.php';";
$compatNeedle = "require_once \$pluginPath . 'include_compat.php';";
$mutationsNeedle = "require_once \$pluginPath . 'admin_mutations.php';";

if (strpos($endpoint, $integrityNeedle) === false) {
    $failures[] = 'admin-save.php does not load integrity.php for slug normalization.';
}
if (strpos($endpoint, $compatNeedle) === false) {
    $failures[] = 'admin-save.php does not load include_compat.php for admin mutation helpers.';
}

$integrityPos = strpos($endpoint, $integrityNeedle);
$mutationsPos = strpos($endpoint, $mutationsNeedle);
if ($integrityPos === false || $mutationsPos === false || $integrityPos > $mutationsPos) {
    $failures[] = 'integrity.php must be loaded before admin_mutations.php in admin-save.php.';
}

if (strpos($mutations, "plugins/documents/integrity.php") === false
    || strpos($mutations, "function_exists('DOCUMENTS_normalizeRouteSlug')") === false) {
    $failures[] = 'admin_mutations.php must self-load integrity.php when slug normalization is unavailable.';
}
if (strpos($mutations, "plugins/documents/include_compat.php") === false
    || strpos($mutations, "function_exists('DOCUMENTS_templateName')") === false) {
    $failures[] = 'admin_mutations.php must self-load include_compat.php when compatibility helpers are unavailable.';
}
if (strpos($mutations, "if (!function_exists('DOCUMENTS_normalizeRouteSlug'))") === false) {
    $failures[] = 'DOCUMENTS_adminSlug() must guard against a missing normalization helper.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents admin dependency checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents admin dependency checks: PASS\n";
