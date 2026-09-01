<?php

$root = dirname(__DIR__);
$failures = array();

function documents_public_route_read($root, $path, &$failures)
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

$category = documents_public_route_read($root, 'public_html/category.php', $failures);
$home = documents_public_route_read($root, 'public_html/home.php', $failures);
$runtime = documents_public_route_read($root, 'runtime.php', $failures);
$presentation = documents_public_route_read($root, 'presentation.php', $failures);

if (strpos($category, "require_once \$pluginPath . 'integrity.php';") === false) {
    $failures[] = 'category.php must load integrity.php before normalizing category slugs.';
}
if (strpos($category, 'DOCUMENTS_normalizeRouteSlug') === false) {
    $failures[] = 'category.php no longer normalizes category slugs.';
}
if (strpos($home, "isset(\$_GET['msg'])") === false
    || strpos($home, 'class="documents-message"') === false) {
    $failures[] = 'Documents home must render redirect feedback messages.';
}
if (strpos($home, 'DOCUMENTS_homeStatsBlock()') === false) {
    $failures[] = 'Documents home must render statistics explicitly.';
}
if (strpos($runtime, "\$_DOCUMENTS_CONF['documents_main_footer'] =") !== false) {
    $failures[] = 'runtime.php must not inject home statistics into configuration output.';
}
if (strpos($presentation, '/css/documents.css') === false
    || strpos($presentation, "method_exists(\$_SCRIPTS, 'setCSSFile')") === false) {
    $failures[] = 'Public stylesheet compatibility registration is missing.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents public route regression checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents public route regression checks: PASS\n";
