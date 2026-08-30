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
if (strpos($rewrite, '# Documents generated rewrite v1.2.0-r2') === false) {
    $failures[] = 'Generated rewrite rules need a version signature.';
}
if (strpos($rewrite, 'document.php?cat=$1&doc=$2') === false
    || strpos($rewrite, 'category.php?cat=$1') === false) {
    $failures[] = 'Runtime self-repair must recognize earlier 1.2.0 development rules.';
}
if (strpos($runtime, 'DOCUMENTS_runtimeDispatchRewrittenView') !== false) {
    $failures[] = 'runtime.php must not dispatch public views while it is still loading.';
}
if (strpos($index, "if (\$documentsMode === 'view')") === false
    || strpos($index, "require __DIR__ . '/category.php';") === false
    || strpos($index, "require __DIR__ . '/document.php';") === false) {
    $failures[] = 'index.php must dispatch rewritten view requests after runtime/helpers load.';
}
if (strpos($index, "basename(\$documentsRequestPath) === 'index.php'") === false) {
    $failures[] = 'Direct index.php view URLs must redirect to their clean canonical URL.';
}
if (strpos($autoinstall, 'DOCUMENTS_writeHtaccess(true)') === false) {
    $failures[] = 'Install/update must generate the Documents .htaccess file.';
}
if (strpos($package, 'zipinfo') === false) {
    $failures[] = 'Packaging must continue to inspect the archive for forbidden hidden paths.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents rewrite/runtime checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents rewrite/runtime checks: PASS\n";
