<?php

$root = dirname(__DIR__);
$failures = array();
$path = $root . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'category.php';
$content = is_file($path) ? file_get_contents($path) : false;

if ($content === false) {
    $failures[] = 'Unable to read public_html/category.php.';
} else {
    if (strpos($content, 'submitable, css, custom_header') === false) {
        $failures[] = 'Category renderer does not select the category CSS column.';
    }
    if (strpos($content, 'SELECT DISTINCT d.doc_url, d.did, d.active, COALESCE') === false) {
        $failures[] = 'Category document query does not select d.did and workflow status used by rendering/order.';
    }
    if (strpos($content, 'ORDER BY changed_at DESC, d.did DESC') === false) {
        $failures[] = 'Category document ordering is not deterministic.';
    }
    if (strpos($content, 'DOCUMENTS_renderNavigation()') === false) {
        $failures[] = 'Category renderer does not include shared Documents navigation explicitly.';
    }
    if (strpos($content, "if (empty(\$cards))") === false) {
        $failures[] = 'Empty category state is missing.';
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents empty category rendering checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents empty category rendering checks: PASS\n";
