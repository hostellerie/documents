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
$index = documents_public_read($root, 'public_html/index.php', $failures);
$home = documents_public_read($root, 'public_html/home.php', $failures);
$category = documents_public_read($root, 'public_html/category-list.php', $failures);
$controller = documents_public_read($root, 'public_html/document.php', $failures);
$renderer = documents_public_read($root, 'public_document.php', $failures);
$seo = documents_public_read($root, 'seo.php', $failures);
$css = documents_public_read($root, 'public_html/css/documents.css', $failures);
$listCss = documents_public_read($root, 'public_html/css/documents-list.css', $failures);
$updates = documents_public_read($root, 'install_updates.php', $failures);

documents_public_require($rewrite, 'RewriteRule ^$ home.php [L]', 'Documents root is not routed to the public home page.', $failures);
documents_public_require($rewrite, 'index.php?mode=view&cat=$1', 'Clean category routes are not using the public router.', $failures);
documents_public_require($rewrite, 'index.php?mode=view&cat=$1&doc=$2', 'Clean document routes are not using the public router.', $failures);
documents_public_forbid($rewrite, 'DirectoryIndex home.php', 'Public routing should not require an additional DirectoryIndex override.', $failures);

documents_public_require($updates, 'DOCUMENTS_writeHtaccess(true)', '1.2.0 upgrade does not refresh existing rewrite rules.', $failures);
documents_public_require($index, "require __DIR__ . '/category-list.php';", 'Public router does not dispatch clean category URLs to the sortable list.', $failures);
documents_public_require($index, "require __DIR__ . '/document.php';", 'Public router does not dispatch clean document URLs.', $failures);

documents_public_require($home, '<main class="documents-home">', 'Modern home semantic main element is missing.', $failures);
documents_public_require($home, "COM_getPermSQL('AND', 0, 2, 'c')", 'Home category permissions are not enforced.', $failures);
documents_public_require($home, 'DOCUMENTS_homeStatsBlock()', 'Configured home statistics were lost on the modern home.', $failures);
documents_public_forbid($home, 'ADMIN_list(', 'Modern home must not depend on ADMIN_list().', $failures);

documents_public_require($category, '<main class="documents-category">', 'Modern category semantic main element is missing.', $failures);
documents_public_require($category, '<nav class="documents-breadcrumb"', 'Category breadcrumb is missing.', $failures);
documents_public_require($category, 'aria-current="page"', 'Category breadcrumb does not identify the current category.', $failures);
documents_public_require($category, 'SELECT COUNT(*) total', 'Modern category pagination count is missing.', $failures);
documents_public_require($category, "COM_getPermSQL('AND', 0, 2, 'd')", 'Modern category document permissions are not enforced.', $failures);
documents_public_require($category, '$where .= " AND d.active=1";', 'Public category list does not restrict ordinary visitors to active documents.', $failures);
documents_public_require($category, 'DOCUMENTS_listFieldsForCategory(', 'Category list is not driven by f_on_list fields.', $failures);
documents_public_require($category, 'class="documents-list-table"', 'Sortable public document table is missing.', $failures);
documents_public_require($category, 'class="documents-list-controls"', 'Public document search/results controls are missing.', $failures);
documents_public_require($category, "array(20, 50, 100)", 'Public list result-count choices are missing.', $failures);
documents_public_require($category, 'DOCUMENTS_listSortLink(', 'Public list sortable column links are missing.', $failures);
documents_public_require($category, 'class="documents-pagination"', 'Public list pagination is missing.', $failures);
documents_public_require($category, 'PLG_replaceTags($header)', 'Category custom header does not expand Geeklog autotags.', $failures);
documents_public_require($category, 'PLG_replaceTags($footer)', 'Category custom footer does not expand Geeklog autotags.', $failures);
documents_public_require($category, 'class="documents-category-header"', 'Category custom header is not rendered as free HTML content.', $failures);
documents_public_require($category, 'class="documents-category-footer"', 'Category custom footer is not rendered as free HTML content.', $failures);
documents_public_forbid($category, 'ADMIN_list(', 'Modern category view must not depend on ADMIN_list().', $failures);

documents_public_require($controller, 'DOCUMENTS_renderPublicDocument(', 'Document controller does not use the unified public renderer.', $failures);
documents_public_require($renderer, 'DOCUMENTS_canViewDocument($document, 2)', 'Unified document view does not use the centralized visibility guard.', $failures);
documents_public_require($renderer, "\$type === 'album'", 'Unified document renderer does not recognize MediaGallery album fields.', $failures);
documents_public_require($renderer, 'DOCUMENTS_mediaGalleryRenderAlbum($value)', 'Public album rendering is not delegated to MediaGallery.', $failures);
documents_public_require($renderer, "'marker_render'", 'Public marker rendering is not delegated to Maps.', $failures);
documents_public_forbid($renderer, 'maps_markers', 'Public document renderer must not access Maps marker storage.', $failures);
documents_public_forbid($renderer, 'mg_albums', 'Public document renderer must not access MediaGallery album storage.', $failures);
documents_public_require($renderer, '<dl class="documents-properties">', 'Default structured-property definition list is missing.', $failures);
documents_public_require($renderer, 'documents-document__prose', 'Default document main-content area is missing.', $failures);
documents_public_require($renderer, 'CMT_userComments(', 'Default document comments integration is missing.', $failures);
documents_public_require($renderer, 'SET hits=hits+1', 'Default document hit counting is missing.', $failures);

documents_public_require($seo, 'DOCUMENTS_seoRemoveManagedTags', 'SEO duplicate-tag cleanup is missing.', $failures);
documents_public_require($css, '.documents-category-grid', 'Modern category-grid CSS is missing.', $failures);
documents_public_require($css, '.documents-properties', 'Structured document-property CSS is missing.', $failures);
documents_public_require($listCss, '.documents-list-table', 'Responsive document-list table CSS is missing.', $failures);
documents_public_require($listCss, '@media', 'Responsive document-list mobile rules are missing.', $failures);

if (!empty($failures)) {
    fwrite(STDERR, "Documents public collection checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents public collection checks: PASS\n";
