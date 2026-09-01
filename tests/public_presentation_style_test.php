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
$category = documents_public_style_read($root, 'public_html/category.php', $failures);
$document = documents_public_style_read($root, 'public_html/document.php', $failures);
$css = documents_public_style_read($root, 'public_html/css/documents.css', $failures);

if (strpos($presentation, 'function DOCUMENTS_loadPublicStyles()') === false) {
    $failures[] = 'Shared public stylesheet loader is missing.';
}
if (strpos($presentation, 'function DOCUMENTS_preparePublicPresentation(') === false) {
    $failures[] = 'Explicit public presentation bootstrap is missing.';
}
if (strpos($presentation, 'function DOCUMENTS_createPublicPage(') === false) {
    $failures[] = 'Unified public page builder is missing.';
}
if (strpos($presentation, '/css/documents.css') === false) {
    $failures[] = 'Public stylesheet is not registered.';
}
if (strpos($presentation, "method_exists(\$_SCRIPTS, 'setCSSFile')") === false) {
    $failures[] = 'Public stylesheet loader does not guard the Geeklog resource API.', $failures;
}
if (strpos($presentation, 'COM_startBlock(') !== false || strpos($presentation, 'COM_endBlock(') !== false) {
    $failures[] = 'Home statistics must not depend on Geeklog theme blocks.';
}
if (strpos($presentation, 'documents-stat__value') === false) {
    $failures[] = 'Compact statistics component is missing.';
}
if (strpos($presentation, 'ob_start(') !== false) {
    $failures[] = 'Presentation still relies on implicit output buffering.';
}
foreach (array('home' => $home, 'category' => $category, 'document' => $document) as $name => $source) {
    if (strpos($source, 'DOCUMENTS_preparePublicPresentation(') === false) {
        $failures[] = 'Documents ' . $name . ' does not prepare presentation explicitly.';
    }
    if (strpos($source, 'DOCUMENTS_createPublicPage(') === false) {
        $failures[] = 'Documents ' . $name . ' does not use the unified public page builder.';
    }
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
