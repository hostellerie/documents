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
    if (strpos($content, 'SELECT d.doc_url, d.did, d.active, d.created, d.modified') === false) {
        $failures[] = 'Category document query does not select document identity, workflow status and dates.';
    }
    if (strpos($content, 'COALESCE(d.modified,d.created)') === false
        || strpos($content, 'd.did DESC') === false) {
        $failures[] = 'Category document ordering is not deterministic.';
    }
    if (strpos($content, '<nav class="documents-breadcrumb"') === false) {
        $failures[] = 'Category renderer does not include breadcrumb navigation.';
    }
    if (strpos($content, 'DOCUMENTS_renderNavigation()') !== false) {
        $failures[] = 'Category renderer still includes the redundant public plugin menu.';
    }
    if (strpos($content, 'if ($total <= 0)') === false
        || strpos($content, 'documents-empty') === false) {
        $failures[] = 'Empty category state is missing.';
    }
    if (strpos($content, 'documents-list-table') === false
        || strpos($content, 'documents-list-controls') === false) {
        $failures[] = 'Category renderer does not expose the modern public document table and controls.';
    }
    if (strpos($content, "name=\"q\"") === false
        || strpos($content, "name=\"per_page\"") === false
        || strpos($content, "name=\"sort\"") === false) {
        $failures[] = 'Category renderer is missing search, result-count or sorting controls.';
    }
    if (strpos($content, 'f_on_list=1') === false) {
        $failures[] = 'Category renderer does not build columns from fields configured for list display.';
    }
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents category rendering checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents category rendering checks: PASS\n";
