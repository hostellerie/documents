<?php

$root = dirname(__DIR__);
$failures = array();

function documents_nav_read($root, $path, &$failures)
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

function documents_nav_require($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function documents_nav_forbid($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

$navigation = documents_nav_read($root, 'navigation.php', $failures);
$runtime = documents_nav_read($root, 'runtime.php', $failures);
$home = documents_nav_read($root, 'public_html/home.php', $failures);
$category = documents_nav_read($root, 'public_html/category.php', $failures);
$documentController = documents_nav_read($root, 'public_html/document.php', $failures);
$presentation = documents_nav_read($root, 'presentation.php', $failures);

/* Keep the legacy renderer side-effect free for compatibility, but public pages
 * now use page content and breadcrumbs instead of the redundant plugin menu. */
documents_nav_require($runtime, "'navigation.php'", 'Runtime does not load the side-effect-free navigation compatibility functions.', $failures);
if (strpos($runtime, 'DOCUMENTS_startNavigationBuffer()') !== false
    || strpos($navigation, 'ob_start(') !== false) {
    $failures[] = 'Navigation compatibility code must not start output buffering implicitly.';
}
documents_nav_require($navigation, 'function DOCUMENTS_renderNavigation()', 'Legacy navigation renderer compatibility helper is missing.', $failures);
documents_nav_forbid($home, 'DOCUMENTS_renderNavigation()', 'Documents home must not render the redundant plugin menu.', $failures);
documents_nav_forbid($category, 'DOCUMENTS_renderNavigation()', 'Category pages must use the breadcrumb instead of the redundant plugin menu.', $failures);
documents_nav_require($category, '<nav class="documents-breadcrumb"', 'Category breadcrumb is missing.', $failures);
documents_nav_require($documentController, '<nav class="documents-breadcrumb"', 'Document breadcrumb is missing.', $failures);
documents_nav_forbid($documentController, '$content = $navigation', 'Document page still prepends the redundant plugin menu.', $failures);
documents_nav_require($presentation, '/css/documents.css', 'Public stylesheet is not registered.', $failures);
documents_nav_require($presentation, "method_exists(\$_SCRIPTS, 'setCSSFile')", 'Public stylesheet loader does not use Geeklog capability detection.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents navigation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents navigation checks: PASS\n";
