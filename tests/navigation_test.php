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
$documentRenderer = documents_nav_read($root, 'public_document.php', $failures);
$presentation = documents_nav_read($root, 'presentation.php', $failures);
$css = documents_nav_read($root, 'public_html/css/documents.css', $failures);

documents_nav_require($runtime, "'navigation.php'", 'Runtime does not load the side-effect-free navigation functions.', $failures);
if (strpos($runtime, 'DOCUMENTS_startNavigationBuffer()') !== false
    || strpos($navigation, 'ob_start(') !== false) {
    $failures[] = 'Navigation must not start an output buffer implicitly.';
}
documents_nav_require($navigation, 'function DOCUMENTS_renderNavigation()', 'Shared navigation renderer is missing.', $failures);
documents_nav_require($navigation, "'label' => \$isFrench ? 'Tous les documents' : 'All documents'", 'Public navigation does not provide the all-documents entry.', $failures);
documents_nav_require($navigation, "SEC_hasRights('documents.admin')", 'Administration access is not restricted to Documents administrators.', $failures);
documents_nav_require($navigation, '/plugins/documents/index.php', 'Public navigation does not use the dedicated Geeklog admin entry point.', $failures);
documents_nav_forbid($navigation, 'documents_cat', 'Simplified public navigation must not duplicate category listing/database queries.', $failures);
documents_nav_require($home, 'DOCUMENTS_renderNavigation()', 'Home does not render navigation explicitly.', $failures);
documents_nav_require($category, 'DOCUMENTS_renderNavigation()', 'Category does not render navigation explicitly.', $failures);
documents_nav_require($documentRenderer, 'DOCUMENTS_renderNavigation()', 'Document renderer does not render navigation explicitly.', $failures);
documents_nav_require($presentation, '/css/documents.css', 'Public stylesheet is not registered.', $failures);
documents_nav_require($presentation, "method_exists(\$_SCRIPTS, 'setCSSFile')", 'Public stylesheet loader does not use Geeklog capability detection.', $failures);
documents_nav_require($css, '.documents-navigation', 'Shared navigation styling is missing.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents navigation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents navigation checks: PASS\n";
