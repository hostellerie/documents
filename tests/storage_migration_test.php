<?php

/* Reminder: always indent with 4 spaces (no tabs). */

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'documents-storage-test-' . uniqid('', true);
$legacy = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'data_documents' . DIRECTORY_SEPARATOR;
$target = $root . DIRECTORY_SEPARATOR . 'data-documents' . DIRECTORY_SEPARATOR;

$GLOBALS['documents_test_legacy'] = $legacy;
$GLOBALS['documents_test_target'] = $target;

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

$GLOBALS['documents_test_root'] = $root;

if (!mkdir($legacy . 'templates' . DIRECTORY_SEPARATOR . 'custom', 0755, true)) {
    documents_test_fail('Unable to create legacy fixture directory.');
}
if (!mkdir($target, 0755, true)) {
    documents_test_fail('Unable to create target fixture directory.');
}

file_put_contents($legacy . 'legacy.txt', 'legacy-data');
file_put_contents($legacy . 'preserve.txt', 'legacy-version');
file_put_contents($legacy . 'templates' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'template.thtml', 'template-data');
file_put_contents($target . 'preserve.txt', 'target-version');

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage.php';

$first = DOCUMENTS_migrateLegacyData();
if (!is_array($first) || !empty($first['errors'])) {
    documents_test_fail('First migration reported errors.');
}
if (empty($first['source_exists']) || empty($first['target_ready'])) {
    documents_test_fail('Migration did not detect source/target correctly.');
}
if (!is_file($target . 'legacy.txt') || file_get_contents($target . 'legacy.txt') !== 'legacy-data') {
    documents_test_fail('Legacy file was not copied.');
}
if (!is_file($target . 'templates' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'template.thtml')) {
    documents_test_fail('Nested legacy file was not copied.');
}
if (file_get_contents($target . 'preserve.txt') !== 'target-version') {
    documents_test_fail('Existing target file was overwritten.');
}
if ((int) $first['copied'] !== 2) {
    documents_test_fail('Unexpected copied-file count on first migration.');
}

$second = DOCUMENTS_migrateLegacyData();
if (!is_array($second) || !empty($second['errors'])) {
    documents_test_fail('Second migration reported errors.');
}
if ((int) $second['copied'] !== 0) {
    documents_test_fail('Second migration was not idempotent.');
}
if (file_get_contents($target . 'preserve.txt') !== 'target-version') {
    documents_test_fail('Second migration overwrote an existing target file.');
}

if (!is_file($legacy . 'legacy.txt')) {
    documents_test_fail('Legacy source was modified or deleted.');
}

documents_test_remove_tree($root);
echo "Storage migration idempotence: PASS" . PHP_EOL;
