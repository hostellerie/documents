<?php

$root = dirname(__DIR__);
$failures = array();

$index = file_get_contents($root . '/public_html/index.php');
$home = file_get_contents($root . '/public_html/home.php');

if ($index === false || $home === false) {
    fwrite(STDERR, "Unable to read Documents public home files.\n");
    exit(1);
}

if (strpos($index, "if (\$documentsMode === '')") === false
    || strpos($index, "require __DIR__ . '/home.php';") === false) {
    $failures[] = 'The Documents root does not route empty mode to the modern home page.';
}

if (strpos($home, "FROM {\$_TABLES['documents_cat']} AS c WHERE c.list_index=1") === false) {
    $failures[] = 'Modern home must query categories directly.';
}

foreach (array('documents_fields', 'documents_values', 'documents_docs') as $table) {
    if (strpos($home, 'JOIN {$_TABLES[\'' . $table . '\']}') !== false
        || strpos($home, 'INNER JOIN {$_TABLES[\'' . $table . '\']}') !== false) {
        $failures[] = 'Modern home must not require ' . $table . ' rows to display a category.';
    }
}

if (strpos($home, "COM_getPermSQL('AND', 0, 2, 'c')") === false) {
    $failures[] = 'Modern home must retain category permission filtering.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents public home category checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents public home category checks: PASS\n";
