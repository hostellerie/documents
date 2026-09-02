<?php

$root = dirname(__DIR__);
$failures = array();

$home = file_get_contents($root . '/public_html/home.php');
$category = file_get_contents($root . '/public_html/category-list.php');

if ($home === false || $category === false) {
    fwrite(STDERR, "Unable to read Documents public rendering files.\n");
    exit(1);
}

if (strpos($home, "PLG_replaceTags((string) \$_DOCUMENTS_CONF['documents_main_header'])") === false) {
    $failures[] = 'Main Documents header must expand Geeklog autotags.';
}
if (strpos($home, "PLG_replaceTags((string) \$_DOCUMENTS_CONF['documents_main_footer'])") === false) {
    $failures[] = 'Main Documents footer must expand Geeklog autotags.';
}
if (strpos($category, "PLG_replaceTags(\$header)") === false) {
    $failures[] = 'Category custom header must expand Geeklog autotags.';
}
if (strpos($category, "PLG_replaceTags(\$footer)") === false) {
    $failures[] = 'Category custom footer must expand Geeklog autotags.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents autotag rendering checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents autotag rendering checks: PASS\n";
