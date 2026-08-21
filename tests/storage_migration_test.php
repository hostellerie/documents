<?php

/* Reminder: always indent with 4 spaces (no tabs). */

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'documents-storage-test-' . uniqid('', true);
$GLOBALS['documents_test_root'] = $root;

function DOCUMENTS_legacyDataDir()
{
    return $GLOBALS['documents_test_legacy'];
}

function DOCUMENTS_dataDir()
{
    return $GLOBALS['documents_test_target'];
}

function documents_test_fail($message)
{
    fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
    documents_test_remove_tree($GLOBALS['documents_test_root']);
    exit(1);
}

function documents_test_remove_tree($path)
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $current = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($current) && !is_link($current)) {
            documents_test_remove_tree($current);
        } else {
            @unlink($current);
        }
    }

    @rmdir($path);
}

function documents_test_select_site($root, $dataName)
{
    $data = $root . DIRECTORY_SEPARATOR . $dataName;
    $GLOBALS['documents_test_legacy'] = $data . DIRECTORY_SEPARATOR
        . 'data_documents' . DIRECTORY_SEPARATOR;
    $GLOBALS['documents_test_target'] = $root . DIRECTORY_SEPARATOR
        . $dataName . '-documents' . DIRECTORY_SEPARATOR;
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage.php';

/* Site A: verify copy, nested content, no overwrite and idempotence. */
documents_test_select_site($root, 'data-site-a');
$legacyA = DOCUMENTS_legacyDataDir();
$targetA = DOCUMENTS_dataDir();

if (!mkdir($legacyA . 'templates' . DIRECTORY_SEPARATOR . 'custom', 0755, true)) {
    documents_test_fail('Unable to create site A legacy fixture directory.');
}
if (!mkdir($targetA, 0755, true)) {
    documents_test_fail('Unable to create site A target fixture directory.');
}

file_put_contents($legacyA . 'legacy.txt', 'site-a-legacy');
file_put_contents($legacyA . 'preserve.txt', 'site-a-legacy-version');
file_put_contents(
    $legacyA . 'templates' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'template.thtml',
    'site-a-template'
);
file_put_contents($targetA . 'preserve.txt', 'site-a-target-version');

$first = DOCUMENTS_migrateLegacyData();
if (!is_array($first) || !empty($first['errors'])) {
    documents_test_fail('Site A first migration reported errors.');
}
if (empty($first['source_exists']) || empty($first['target_ready'])) {
    documents_test_fail('Site A migration did not detect source/target correctly.');
}
if (!is_file($targetA . 'legacy.txt')
    || file_get_contents($targetA . 'legacy.txt') !== 'site-a-legacy') {
    documents_test_fail('Site A legacy file was not copied.');
}
if (!is_file($targetA . 'templates' . DIRECTORY_SEPARATOR . 'custom'
    . DIRECTORY_SEPARATOR . 'template.thtml')) {
    documents_test_fail('Site A nested legacy file was not copied.');
}
if (file_get_contents($targetA . 'preserve.txt') !== 'site-a-target-version') {
    documents_test_fail('Site A existing target file was overwritten.');
}
if ((int) $first['copied'] !== 2) {
    documents_test_fail('Unexpected copied-file count on site A first migration.');
}

$second = DOCUMENTS_migrateLegacyData();
if (!is_array($second) || !empty($second['errors'])) {
    documents_test_fail('Site A second migration reported errors.');
}
if ((int) $second['copied'] !== 0) {
    documents_test_fail('Site A second migration was not idempotent.');
}
if (file_get_contents($targetA . 'preserve.txt') !== 'site-a-target-version') {
    documents_test_fail('Site A second migration overwrote an existing target file.');
}
if (!is_file($legacyA . 'legacy.txt')) {
    documents_test_fail('Site A legacy source was modified or deleted.');
}

/* Site B: verify a distinct path_data maps to a distinct Documents store. */
documents_test_select_site($root, 'data-site-b');
$legacyB = DOCUMENTS_legacyDataDir();
$targetB = DOCUMENTS_dataDir();

if ($targetA === $targetB) {
    documents_test_fail('Multisite targets are not isolated.');
}
if (!mkdir($legacyB, 0755, true)) {
    documents_test_fail('Unable to create site B legacy fixture directory.');
}
file_put_contents($legacyB . 'legacy.txt', 'site-b-legacy');

$siteB = DOCUMENTS_migrateLegacyData();
if (!is_array($siteB) || !empty($siteB['errors'])) {
    documents_test_fail('Site B migration reported errors.');
}
if (!is_file($targetB . 'legacy.txt')
    || file_get_contents($targetB . 'legacy.txt') !== 'site-b-legacy') {
    documents_test_fail('Site B legacy file was not copied to its own target.');
}
if (file_get_contents($targetA . 'legacy.txt') !== 'site-a-legacy') {
    documents_test_fail('Site B migration altered site A target data.');
}
if (is_file($targetB . 'preserve.txt')) {
    documents_test_fail('Site B incorrectly received site A target data.');
}

/* Switch back to site A and ensure site B remains untouched. */
documents_test_select_site($root, 'data-site-a');
$siteAThird = DOCUMENTS_migrateLegacyData();
if (!empty($siteAThird['errors']) || (int) $siteAThird['copied'] !== 0) {
    documents_test_fail('Site A rerun after site B was not idempotent.');
}
if (file_get_contents($targetB . 'legacy.txt') !== 'site-b-legacy') {
    documents_test_fail('Site A rerun altered site B target data.');
}

documents_test_remove_tree($root);
echo "Storage migration idempotence and multisite isolation: PASS" . PHP_EOL;
