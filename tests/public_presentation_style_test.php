<?php

$root = dirname(__DIR__);
$failures = array();

function documents_public_style_read($root, $path, &$failures)
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

$presentation = documents_public_style_read($root, 'presentation.php', $failures);
$home = documents_public_style_read($root, 'public_html/home.php', $failures);
$css = documents_public_style_read($root, 'public_html/css/documents.css', $failures);

if (strpos($presentation, 'function DOCUMENTS_loadPublicStyles()') === false) {
    $failures[] = 'Shared public stylesheet loader is missing.';
}
if (strpos($presentation, "'/css/documents.css?v=1.2.0-4'") === false) {
    $failures[] = 'Public stylesheet is not registered with the current versioned public_html-relative URI.';
}
if (strpos($presentation, 'COM_startBlock(') !== false || strpos($presentation, 'COM_endBlock(') !== false) {
    $failures[] = 'Home statistics must not depend on Geeklog theme blocks.';
}
if (strpos($presentation, 'documents-stat__value') === false) {
    $failures[] = 'Compact statistics component is missing.';
}
if (strpos($home, 'DOCUMENTS_loadPublicStyles()') === false) {
    $failures[] = 'Documents home does not use the shared public stylesheet loader.';
}
if (strpos($home, "rtrim((string) \$_DOCUMENTS_CONF['site_url'], '/') . '/css/documents.css'") !== false) {
    $failures[] = 'Documents home still registers public CSS using an absolute site URL.';
}
if (strpos($css, '.documents-category-card__link') === false) {
    $failures[] = 'Modern category-card styling is missing.';
}
if (strpos($css, '.documents-home__stats') === false) {
    $failures[] = 'Compact statistics styling is missing.';
}
if (strpos($css, '.documents-message') === false) {
    $failures[] = 'Public feedback-message styling is missing.';
}

if (!empty($failures)) {
    fwrite(STDERR, "Documents public presentation checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents public presentation checks: PASS\n";
