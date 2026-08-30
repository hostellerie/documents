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

$navigation = documents_nav_read($root, 'navigation.php', $failures);
$runtime = documents_nav_read($root, 'runtime.php', $failures);
$presentation = documents_nav_read($root, 'presentation.php', $failures);
$css = documents_nav_read($root, 'public_html/css/documents.css', $failures);

documents_nav_require($runtime, "plugins/documents/navigation.php", 'Runtime does not load shared navigation.', $failures);
documents_nav_require($runtime, 'DOCUMENTS_startNavigationBuffer()', 'Runtime does not start the shared navigation buffer.', $failures);
documents_nav_require($navigation, 'function DOCUMENTS_renderNavigation()', 'Shared navigation renderer is missing.', $failures);
documents_nav_require($navigation, 'SEC_hasAccess(', 'Category links are not filtered by effective access rights.', $failures);
documents_nav_require($navigation, 'WHERE list_index=1', 'Navigation does not respect category index visibility.', $failures);
documents_nav_require($navigation, "SEC_hasRights('documents.admin')", 'Administration links are not restricted to Documents administrators.', $failures);
documents_nav_require($navigation, "'image.php', 'style.php'", 'Non-HTML endpoints are not excluded from navigation buffering.', $failures);
documents_nav_require($navigation, '<main class="documents-', 'Modern Documents pages are not targeted by navigation injection.', $failures);
documents_nav_require($navigation, '<div class="user_menu">', 'Legacy Documents pages are not targeted by navigation injection.', $failures);
documents_nav_require($presentation, 'documents.css?v=1.2.0-4', 'Public stylesheet cache-buster was not updated for navigation.', $failures);
documents_nav_require($css, '.documents-navigation', 'Shared navigation styling is missing.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents navigation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents navigation checks: PASS\n";
