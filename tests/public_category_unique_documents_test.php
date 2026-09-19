<?php

$root = dirname(__DIR__);
$failures = array();

$categoryList = file_get_contents($root . '/public_html/category-list.php');
if ($categoryList === false) {
    fwrite(STDERR, "Unable to read public category list.\n");
    exit(1);
}

if (strpos($categoryList, 'COUNT(DISTINCT d.doc_url)') === false) {
    $failures[] = 'Category pagination must count distinct document URLs.';
}

if (strpos($categoryList, 'GROUP BY d.doc_url') === false) {
    $failures[] = 'Category result rows must be grouped by logical document URL.';
}

if (strpos($categoryList, 'LEFT JOIN {$_TABLES[\'documents_values\']} sort_value') !== false) {
    $failures[] = 'Sorting must not use a value join that can multiply legacy rows.';
}

if (strpos($categoryList, 'SELECT MAX(sort_value.v_value)') === false) {
    $failures[] = 'Field sorting must collapse duplicate legacy values to one sortable value.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents public category uniqueness checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents public category uniqueness checks: PASS\n";
