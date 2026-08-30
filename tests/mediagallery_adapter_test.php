<?php

$root = dirname(__DIR__);
$failures = array();

function documents_mg_read($root, $path, &$failures)
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

function documents_mg_require($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function documents_mg_forbid($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

$adapter = documents_mg_read($root, 'mediagallery_adapter.php', $failures);
$editor = documents_mg_read($root, 'include_edit.php', $failures);
$controller = documents_mg_read($root, 'public_html/document.php', $failures);
$renderer = documents_mg_read($root, 'public_document.php', $failures);
$runtime = documents_mg_read($root, 'runtime.php', $failures);

documents_mg_require($runtime, 'mediagallery_adapter.php', 'Runtime does not load the MediaGallery adapter.', $failures);
documents_mg_require($adapter, 'plugin_getuseroption_mediagallery()', 'Member album root is not obtained through the MediaGallery user callback.', $failures);
documents_mg_require($adapter, 'new mgAlbum($rootId)', 'MediaGallery album tree does not start from the member root album.', $failures);
documents_mg_require($adapter, "method_exists(\$album, 'getChildrenVisible')", 'MediaGallery visible child traversal is missing.', $failures);
documents_mg_require($adapter, "PLG_replaceTags('[gallery:' . \$albumId . ']')", 'MediaGallery gallery rendering is not delegated to the gallery autotag.', $failures);
documents_mg_require($editor, 'DOCUMENTS_mediaGalleryAlbumSelect($field[\'var_name\'], $value)', 'Document editor does not use the member album tree selector.', $failures);
documents_mg_require($controller, 'DOCUMENTS_renderPublicDocument(', 'Public document controller does not use the unified renderer.', $failures);
documents_mg_require($renderer, 'DOCUMENTS_mediaGalleryRenderAlbum($value)', 'Unified public document renderer does not use the MediaGallery adapter.', $failures);

$forbiddenStoragePatterns = array(
    "\$_TABLES['mg_albums']",
    "\$_TABLES['mg_media']",
    "\$_TABLES['mg_media_albums']",
    "\$_MG_CONF['mediaobjects_url']",
    "\$_MG_CONF['path_mediaobjects']"
);
foreach ($forbiddenStoragePatterns as $needle) {
    documents_mg_forbid($adapter, $needle, 'MediaGallery adapter must not access MediaGallery storage directly: ' . $needle, $failures);
    documents_mg_forbid($editor, $needle, 'Document editor must not access MediaGallery storage directly: ' . $needle, $failures);
    documents_mg_forbid($renderer, $needle, 'Public document renderer must not access MediaGallery storage directly: ' . $needle, $failures);
}

documents_mg_forbid($editor, 'new mgAlbum(0)', 'Document editor must not build a site-wide MediaGallery selector.', $failures);
documents_mg_forbid($editor, 'buildJumpBox(', 'Document editor must not build the MediaGallery jump box directly.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents MediaGallery adapter checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents MediaGallery adapter checks: PASS\n";
