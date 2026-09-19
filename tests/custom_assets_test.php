<?php

$root = dirname(__DIR__);
$failures = array();

function documents_assets_read($root, $path, &$failures)
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

function documents_assets_require($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

$assets = documents_assets_read($root, 'custom_assets.php', $failures);
$styleEndpoint = documents_assets_read($root, 'public_html/style.php', $failures);
$guide = documents_assets_read($root, 'public_html/presentation-help.php', $failures);
$presentation = documents_assets_read($root, 'presentation.php', $failures);
$mutations = documents_assets_read($root, 'admin_mutations.php', $failures);
$template = documents_assets_read($root, 'templates/cat_form.thtml', $failures);

// Persistence: custom assets must derive from Documents data storage, never plugin/public_html.
documents_assets_require($assets, "DOCUMENTS_dataDir()", 'Custom asset roots do not derive from persistent Documents data storage.', $failures);
documents_assets_require($assets, "'templates' . DIRECTORY_SEPARATOR", 'Persistent templates directory is missing.', $failures);
documents_assets_require($assets, "'styles' . DIRECTORY_SEPARATOR", 'Persistent styles directory is missing.', $failures);
documents_assets_require($assets, 'DOCUMENTS_ensureCustomAssetDirectories', 'Persistent asset directory creation helper is missing.', $failures);

// CSS contract and secure serving.
documents_assets_require($assets, "*\\.css$/", 'Custom CSS validator does not require a .css filename.', $failures);
documents_assets_require($assets, 'basename($name) !== $name', 'Custom CSS validator does not reject paths.', $failures);
documents_assets_require($styleEndpoint, 'DOCUMENTS_customStyleName', 'CSS endpoint does not validate the requested filename.', $failures);
documents_assets_require($styleEndpoint, 'X-Content-Type-Options: nosniff', 'CSS endpoint lacks nosniff protection.', $failures);
documents_assets_require($styleEndpoint, 'Content-Type: text/css', 'CSS endpoint does not return CSS MIME type.', $failures);
documents_assets_require($presentation, 'DOCUMENTS_loadRequestedCategoryStyle', 'Public presentation does not load the category-specific CSS.', $failures);

// Template contract.
documents_assets_require($assets, "'document.thtml'", 'Custom template validation does not require document.thtml.', $failures);
documents_assets_require($assets, "'doccomments.thtml'", 'Custom template validation does not require doccomments.thtml.', $failures);
documents_assets_require($mutations, 'DOCUMENTS_customTemplateIsReady', 'Category save does not validate custom template readiness.', $failures);
documents_assets_require($mutations, 'DOCUMENTS_customStylePath', 'Category save does not verify custom CSS file existence.', $failures);

// User guidance and discoverability.
documents_assets_require($guide, 'DOCUMENTS_customTemplatesRoot()', 'Setup guide does not show the real template storage root.', $failures);
documents_assets_require($guide, 'DOCUMENTS_customStylesRoot()', 'Setup guide does not show the real CSS storage root.', $failures);
documents_assets_require($guide, 'DOCUMENTS_customTemplateIsReady', 'Setup guide does not report template readiness.', $failures);
documents_assets_require($template, '/presentation-help.php', 'Category editor does not link to the custom presentation setup guide.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents custom presentation asset checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents custom presentation asset checks: PASS\n";
