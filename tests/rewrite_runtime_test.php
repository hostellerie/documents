<?php

$root = dirname(__DIR__);
$failures = array();

function documents_rewrite_test_read($root, $path, &$failures)
{
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($file)) {
        $failures[] = 'Missing file: ' . $path;
        return '';
    }
    $content = file_get_contents($file);
    if ($content === false) {
        $failures[] = 'Unable to read: ' . $path;
        return '';
    }
    return $content;
}

$rewrite = documents_rewrite_test_read($root, 'rewrite.php', $failures);
$runtime = documents_rewrite_test_read($root, 'runtime.php', $failures);
$index = documents_rewrite_test_read($root, 'public_html/index.php', $failures);
$autoinstall = documents_rewrite_test_read($root, 'autoinstall.php', $failures);
$package = documents_rewrite_test_read($root, '.github/workflows/package.yml', $failures);

if (strpos($rewrite, 'index.php?mode=view&cat=$1') === false) {
    $failures[] = 'Category clean URLs must be rewritten through index.php?mode=view.';
}
if (strpos($rewrite, 'index.php?mode=view&cat=$1&doc=$2') === false) {
    $failures[] = 'Document clean URLs must be rewritten through index.php?mode=view.';
}
if (strpos($rewrite, '# Documents generated rewrite v1.2.0-r3') === false) {
    $failures[] = 'Generated rewrite rules need the current version signature.';
}
if (strpos($rewrite, 'document.php?cat=$1&doc=$2') === false
    || strpos($rewrite, 'category.php?cat=$1') === false
    || strpos($rewrite, '# Documents generated rewrite v1.2.0-r2') === false) {
    $failures[] = 'Runtime self-repair must recognize earlier Documents-owned rule sets.';
}
if (strpos($rewrite, 'RewriteCond %{QUERY_STRING} (^|&)mode=edit_cat(&|$)') !== false
    || strpos($rewrite, 'RewriteRule ^index\\.php$ category-editor.php') !== false) {
    $failures[] = 'Structural administration must not be intercepted by public rewrite rules.';
}
if (strpos($runtime, 'DOCUMENTS_runtimeDispatchRewrittenView') !== false) {
    $failures[] = 'runtime.php must not dispatch public views while it is loading.';
}
if (strpos($index, "if (\$mode === 'view')") === false
    || strpos($index, "require __DIR__ . '/category.php';") === false
    || strpos($index, "require __DIR__ . '/document.php';") === false) {
    $failures[] = 'index.php must dispatch rewritten view requests after runtime/helpers load.';
}
if (strpos($index, "basename(\$requestPath) === 'index.php'") === false) {
    $failures[] = 'Direct index.php view URLs must redirect to their clean canonical URL.';
}
if (strpos($index, '$adminModes') === false
    || strpos($index, '/plugins/documents/index.php') === false) {
    $failures[] = 'Historical public admin modes must redirect to dedicated Geeklog administration.';
}
if (strpos($autoinstall, 'DOCUMENTS_writeHtaccess(true)') === false) {
    $failures[] = 'Install/update must generate the Documents .htaccess file.';
}
if (strpos($package, "--exclude '.github/'") === false
    || strpos($package, "--exclude 'tests/'") === false
    || strpos($package, 'unzip -t dist/documents_1.2.0-2.1.1.zip') === false) {
    $failures[] = 'Packaging does not exclude development surfaces and verify ZIP integrity.';
}
if (strpos($package, 'PHP 5.6 regression tests') === false
    || strpos($package, 'PHP 8.1 regression tests') === false) {
    $failures[] = 'Packaging is not gated by both supported regression suites.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents rewrite/runtime checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents rewrite/runtime checks: PASS\n";
