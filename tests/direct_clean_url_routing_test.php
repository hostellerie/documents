<?php

$root = dirname(__DIR__);
$failures = array();

$rewrite = file_get_contents($root . '/rewrite.php');
$route = file_get_contents($root . '/public_html/category-route.php');

if (strpos($rewrite, '# Documents generated rewrite v1.2.0-r4') === false) {
    $failures[] = 'Rewrite signature must be r4.';
}
if (strpos($rewrite, 'document.php?cat=$1&doc=$2') === false) {
    $failures[] = 'Document clean URLs must route directly to document.php.';
}
if (strpos($rewrite, 'category-route.php?cat=$1') === false) {
    $failures[] = 'Category clean URLs must route directly to category-route.php.';
}
if (strpos($rewrite, 'index.php?mode=view&cat=$1') !== false) {
    $failures[] = 'Public clean URLs must no longer depend on index.php mode=view.';
}
if (strpos($rewrite, 'v1.2.0-r3') === false) {
    $failures[] = 'r3 generated files must be recognized for automatic upgrade.';
}
if (strpos($route, "require_once '../lib-common.php';") === false
    || strpos($route, "require __DIR__ . '/category-list.php';") === false) {
    $failures[] = 'Direct category route must bootstrap Geeklog and delegate to category-list.php.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Direct clean URL routing checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Direct clean URL routing checks: PASS\n";
