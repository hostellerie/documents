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
$document = documents_mg_read($root, 'public_html/document.php', $failures);
$runtime = documents_mg_read($root, 'runtime.php', $failures);

documents_mg_require($runtime, 'mediagallery_adapter.php', 'Runtime does not load the MediaGallery adapter.', $failures);
documents_mg_require($adapter, 'plugin_getuseroption_mediagallery()', 'Member album root is not obtained through the MediaGallery user callback.', $failures);
documents_mg_require($adapter, "new mgAlbum($rootId)", 'MediaGallery album tree does not start from the member root album.', $failures);
documents_mg_require($adapter, "method_exists($album, 'getChildrenVisible')", 'MediaGallery visible child traversal is missing.', $failures);
documents_mg_require($adapter, "PLG_replaceTags('[gallery:' . $albumId . ']')", 'MediaGallery gallery rendering is not delegated to the gallery autotag.', $failures);
documents_mg_require($editor, 'DOCUMENTS_mediaGalleryAlbumSelect($field[\'var_name\'], $value)', 'Document editor does not use the member album tree selector.', $failures);
documents_mg_require($document, 'DOCUMENTS_mediaGalleryRenderAlbum($value)', 'Public document rendering does not use the MediaGallery adapter.', $failures);

foreach (array('mg_albums', 'mg_media', 'mg_media_albums', 'mediaobjects_url', 'path_mediaobjects') as $needle) {
    documents_mg_forbid($adapter, $needle, 'MediaGallery adapter must not know storage detail: ' . $needle, $failures);
    documents_mg_forbid($editor, $needle, 'Document editor must not know MediaGallery storage detail: ' . $needle, $failures);
    documents_mg_forbid($document, $needle, 'Public document renderer must not know MediaGallery storage detail: ' . $needle, $failures);
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
