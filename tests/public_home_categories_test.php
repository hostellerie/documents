<?php

$root = dirname(__DIR__);
$failures = array();

$index = file_get_contents($root . '/public_html/index.php');
$home = file_get_contents($root . '/public_html/home.php');

if ($index === false || $home === false) {
    fwrite(STDERR, "Unable to read Documents public home files.\n");
    exit(1);
}

if (strpos($index, "if (\$mode === '')") === false
    || strpos($index, "require __DIR__ . '/home.php';") === false) {
    $failures[] = 'The Documents root does not route empty mode to the public home page.';
}

if (strpos($home, "FROM {\$_TABLES['documents_cat']} AS c WHERE c.list_index=1") === false) {
    $failures[] = 'Public home must query categories directly.';
}

/* Recent-document preview helpers may legitimately join document/field tables.
 * Category discovery itself must remain a direct documents_cat query so an
 * empty category can still be displayed. */
if (strpos($home, "COM_getPermSQL('AND', 0, 2, 'c')") === false) {
    $failures[] = 'Public home must retain category permission filtering.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents public home category checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents public home category checks: PASS\n";
