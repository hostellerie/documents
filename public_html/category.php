<?php

/* Public Documents category page. Compatible with Geeklog 2.1.1/PHP 5.6+. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'integrity.php';
require_once $pluginPath . 'presentation.php';

$categorySlug = isset($_GET['cat']) ? DOCUMENTS_normalizeRouteSlug((string) $_GET['cat']) : '';
if ($categorySlug === '') {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$safeSlug = DB_escapeString($categorySlug);
$category = DB_fetchArray(DB_query(
    "SELECT cid, cat_name, cat_url, cat_help, metadescription, submitable, css, custom_header, custom_footer, "
    . "owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
    . "FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeSlug}' LIMIT 1"
));

if (!is_array($category) || empty($category['cid'])) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$access = SEC_hasAccess(
    (int) $category['owner_id'],
    (int) $category['group_id'],
    (int) $category['perm_owner'],
    (int) $category['perm_group'],
    (int) $category['perm_members'],
    (int) $category['perm_anon']
);
if ($access < 2) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$pageNumber = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$requestPath = isset($_SERVER['REQUEST_URI']) ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if (is_string($requestPath) && basename($requestPath) === 'category.php') {
    $cleanUrl = DOCUMENTS_interopCanonicalUrl($categorySlug);
    if ($pageNumber > 1) {
        $cleanUrl .= '?page=' . $pageNumber;
    }
    header('Location: ' . $cleanUrl, true, 301);
    exit;
}

$_REQUEST['mode'] = 'view';
$_REQUEST['cat'] = $categorySlug;
$_REQUEST['doc'] = '';

DOCUMENTS_preparePublicPresentation(false);
if (function_exists('DOCUMENTS_loadCategoryStyle') && !empty($category['css'])) {
    DOCUMENTS_loadCategoryStyle($category['css']);
}

$perPage = 20;
$offset = ($pageNumber - 1) * $perPage;
$categoryId = (int) $category['cid'];
$countSql = "SELECT COUNT(DISTINCT d.doc_url) AS total FROM {$_TABLES['documents_docs']} AS d "
    . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
    . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
    . "WHERE f.cat_id={$categoryId} AND d.active=1" . COM_getPermSQL('AND', 0, 2, 'd');
$countRow = DB_fetchArray(DB_query($countSql));
$total = is_array($countRow) && isset($countRow['total']) ? (int) $countRow['total'] : 0;
$totalPages = ($total > 0) ? (int) ceil($total / $perPage) : 1;
if ($pageNumber > $totalPages) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$sql = "SELECT DISTINCT d.doc_url, d.did, COALESCE(d.modified,d.created) AS changed_at "
    . "FROM {$_TABLES['documents_docs']} AS d "
    . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
    . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
    . "WHERE f.cat_id={$categoryId} AND d.active=1" . COM_getPermSQL('AND', 0, 2, 'd')
    . " ORDER BY changed_at DESC, d.did DESC LIMIT {$offset}, {$perPage}";
$result = DB_query($sql);

$categoryName = stripslashes((string) $category['cat_name']);
$content = DOCUMENTS_renderNavigation();
$content .= '<main class="documents-category">';
$content .= '<header class="documents-page-header"><h1>'
    . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</h1>';
if (!empty($category['cat_help'])) {
    $content .= '<p class="documents-page-description">'
        . htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8') . '</p>';
}
$content .= '</header>';

if (!empty($category['custom_header'])) {
    $content .= DOCUMENTS_sectionBlock(
        isset($LANG_DOCUMENTS_1['introduction']) ? $LANG_DOCUMENTS_1['introduction'] : 'Introduction',
        '<div class="documents-category__custom-header">' . (string) $category['custom_header'] . '</div>'
    );
}

$documentsBlock = '';
if ((int) $category['submitable'] === 1 && !COM_isAnonUser()) {
    $newUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
        . '/index.php?mode=new&cat=' . rawurlencode($categorySlug);
    $documentsBlock .= '<p class="documents-category__actions"><a class="documents-action" href="'
        . htmlspecialchars($newUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars(isset($LANG_DOCUMENTS_1['create_new_doc']) ? $LANG_DOCUMENTS_1['create_new_doc'] : 'Add a document', ENT_QUOTES, 'UTF-8')
        . '</a></p>';
}

$cards = array();
while ($row = DB_fetchArray($result)) {
    if (!is_array($row) || empty($row['doc_url'])) {
        continue;
    }
    $item = DOCUMENTS_interopItem($row['doc_url'], 0);
    if (!empty($item)) {
        $cards[] = DOCUMENTS_renderItemCard($item);
    }
}
if (empty($cards)) {
    $documentsBlock .= '<p class="documents-empty">'
        . htmlspecialchars(isset($LANG_DOCUMENTS_1['none']) ? $LANG_DOCUMENTS_1['none'] : 'No documents.', ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    $documentsBlock .= '<div class="documents-card-list">' . implode('', $cards) . '</div>';
}

if ($totalPages > 1) {
    $baseUrl = DOCUMENTS_interopCanonicalUrl($categorySlug);
    $documentsBlock .= '<nav class="documents-pagination" aria-label="Pagination">';
    if ($pageNumber > 1) {
        $prevUrl = $baseUrl . (($pageNumber - 1) > 1 ? '?page=' . ($pageNumber - 1) : '');
        $documentsBlock .= '<a href="' . htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') . '">&laquo; Previous</a>';
    }
    $documentsBlock .= '<span class="documents-pagination__status">' . $pageNumber . ' / ' . $totalPages . '</span>';
    if ($pageNumber < $totalPages) {
        $documentsBlock .= '<a href="' . htmlspecialchars($baseUrl . '?page=' . ($pageNumber + 1), ENT_QUOTES, 'UTF-8') . '">Next &raquo;</a>';
    }
    $documentsBlock .= '</nav>';
}
$content .= DOCUMENTS_sectionBlock(
    isset($LANG_DOCUMENTS_1['documents']) ? $LANG_DOCUMENTS_1['documents'] : 'Documents',
    $documentsBlock
);

if (!empty($category['custom_footer'])) {
    $content .= DOCUMENTS_sectionBlock(
        isset($LANG_DOCUMENTS_1['more_information']) ? $LANG_DOCUMENTS_1['more_information'] : 'More information',
        '<div class="documents-category__custom-footer">' . (string) $category['custom_footer'] . '</div>'
    );
}
$content .= '</main>';

COM_output(DOCUMENTS_createPublicPage($content, $categoryName));
