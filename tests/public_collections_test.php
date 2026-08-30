<?php

$root = dirname(__DIR__);
$failures = array();

function documents_public_read($root, $path, &$failures)
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

function documents_public_require($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) === false) {
        $failures[] = $message;
    }
}

function documents_public_forbid($content, $needle, $message, &$failures)
{
    if (strpos($content, $needle) !== false) {
        $failures[] = $message;
    }
}

$rewrite = documents_public_read($root, 'rewrite.php', $failures);
$home = documents_public_read($root, 'public_html/home.php', $failures);
$category = documents_public_read($root, 'public_html/category.php', $failures);
$document = documents_public_read($root, 'public_html/document.php', $failures);
$seo = documents_public_read($root, 'seo.php', $failures);
$css = documents_public_read($root, 'public_html/css/documents.css', $failures);
$updates = documents_public_read($root, 'install_updates.php', $failures);

documents_public_require($rewrite, 'RewriteRule ^$ home.php [L]', 'Documents root is not routed to the modern home page.', $failures);
documents_public_require($rewrite, 'category.php?cat=$1', 'Clean category routes are not using the modern category view.', $failures);
documents_public_require($rewrite, 'document.php?cat=$1&doc=$2', 'Clean document routes are not using the modern document view.', $failures);
documents_public_forbid($rewrite, 'DirectoryIndex home.php', 'Public routing should not require an additional DirectoryIndex override.', $failures);

documents_public_require($updates, 'DOCUMENTS_writeHtaccess(true)', '1.2.0 upgrade does not refresh existing rewrite rules.', $failures);

documents_public_require($home, '<main class="documents-home">', 'Modern home semantic main element is missing.', $failures);
documents_public_require($home, "COM_getPermSQL('AND', 0, 2, 'c')", 'Home category permissions are not enforced.', $failures);
documents_public_require($home, 'DOCUMENTS_homeStatsBlock()', 'Configured home statistics were lost on the modern home.', $failures);
documents_public_forbid($home, 'ADMIN_list(', 'Modern home must not depend on ADMIN_list().', $failures);

documents_public_require($category, '<main class="documents-category">', 'Modern category semantic main element is missing.', $failures);
documents_public_require($category, 'COUNT(DISTINCT d.doc_url)', 'Modern category pagination count is missing.', $failures);
documents_public_require($category, 'd.active=1', 'Modern category view is not restricted to active documents.', $failures);
documents_public_require($category, "COM_getPermSQL('AND', 0, 2, 'd')", 'Modern category document permissions are not enforced.', $failures);
documents_public_require($category, 'DOCUMENTS_renderItemCard($item)', 'Modern category cards are not using the common item renderer.', $failures);
documents_public_require($category, 'header(\'Location: \' . $cleanUrl, true, 301)', 'Direct category.php URLs are not canonicalized.', $failures);
documents_public_forbid($category, 'ADMIN_list(', 'Modern category view must not depend on ADMIN_list().', $failures);

documents_public_require($document, 'DOCUMENTS_canViewDocument($document, 2)', 'Modern document view does not use the centralized visibility guard.', $failures);
documents_public_require($document, "f_type='album'", 'Modern document route does not detect MediaGallery album fields.', $failures);
documents_public_require($document, 'DOCUMENTS_mediaGalleryRenderAlbum($value)', 'Public album rendering is not delegated to MediaGallery.', $failures);
documents_public_require($document, "PLG_invokeService(\n        'maps',\n        'marker_render'", 'Public marker rendering is not delegated to Maps.', $failures);
documents_public_require($document, 'function DOCUMENTS_publicMarkerValue(', 'Modern public marker renderer is missing.', $failures);
documents_public_forbid($document, 'maps_markers', 'Modern public document renderer must not access Maps marker storage.', $failures);
documents_public_forbid($document, 'maps_maps', 'Modern public document renderer must not access Maps map storage.', $failures);
documents_public_forbid($document, 'mg_albums', 'Modern public document renderer must not access MediaGallery album storage.', $failures);
documents_public_forbid($document, 'mg_media', 'Modern public document renderer must not access MediaGallery media storage.', $failures);
documents_public_require($document, 'require $pluginPath . \'include_html.php\'', 'Legacy compatibility fallback for remaining custom integrations is missing.', $failures);
documents_public_require($document, '<dl class="documents-fields">', 'Modern default document field definition list is missing.', $failures);
documents_public_require($document, 'htmlspecialchars(stripslashes($value)', 'Modern default document text output is not escaped.', $failures);
documents_public_require($document, 'CMT_userComments(', 'Modern default document comments integration is missing.', $failures);
documents_public_require($document, 'SET hits=hits+1', 'Modern default document hit counting is missing.', $failures);
documents_public_forbid($document, 'addslashes(', 'Modern default document route must not use addslashes().', $failures);
documents_public_forbid($document, '<table', 'Modern default document route must not build table-based field markup.', $failures);

documents_public_require($seo, 'DOCUMENTS_seoRemoveManagedTags', 'SEO duplicate-tag cleanup is missing.', $failures);
documents_public_require($seo, '\'?page=\' . $page', 'Paginated category canonical support is missing.', $failures);
documents_public_require($css, '.documents-category-grid', 'Modern category-grid CSS is missing.', $failures);
documents_public_require($css, '.documents-pagination', 'Modern pagination CSS is missing.', $failures);
documents_public_require($css, '.documents-fields', 'Semantic document-field CSS is missing.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents public collection checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents public collection checks: PASS\n";
