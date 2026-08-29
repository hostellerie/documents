<?php

/* Modern public Documents category page. Compatible with Geeklog 2.1.1/PHP 5.6+. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'seo.php';

$categorySlug = isset($_GET['cat']) ? DOCUMENTS_normalizeRouteSlug((string) $_GET['cat']) : '';
if ($categorySlug === '') {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$safeSlug = DB_escapeString($categorySlug);
$category = DB_fetchArray(DB_query(
    "SELECT cid, cat_name, cat_url, cat_help, metadescription, submitable, custom_header, custom_footer, "
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

$_REQUEST['mode'] = 'view';
$_REQUEST['cat'] = $categorySlug;
$_REQUEST['doc'] = '';

if (isset($_SCRIPTS) && is_object($_SCRIPTS)) {
    $_SCRIPTS->setCSSFile(
        'documents_public',
        rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/css/documents.css'
    );
}

if (!defined('DOCUMENTS_SEO_BUFFER_STARTED')) {
    define('DOCUMENTS_SEO_BUFFER_STARTED', true);
    ob_start('DOCUMENTS_seoOutputFilter');
}

$pageNumber = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20;
$offset = ($pageNumber - 1) * $perPage;
$categoryId = (int) $category['cid'];

$countSql = "SELECT COUNT(DISTINCT d.doc_url) AS total "
    . "FROM {$_TABLES['documents_docs']} AS d "
    . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
    . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
    . "WHERE f.cat_id={$categoryId} AND d.active=1"
    . COM_getPermSQL('AND', 0, 2, 'd');
$countRow = DB_fetchArray(DB_query($countSql));
$total = is_array($countRow) && isset($countRow['total']) ? (int) $countRow['total'] : 0;

$sql = "SELECT DISTINCT d.doc_url, COALESCE(d.modified,d.created) AS changed_at "
    . "FROM {$_TABLES['documents_docs']} AS d "
    . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
    . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
    . "WHERE f.cat_id={$categoryId} AND d.active=1"
    . COM_getPermSQL('AND', 0, 2, 'd')
    . " ORDER BY changed_at DESC, d.did DESC LIMIT {$offset}, {$perPage}";
$result = DB_query($sql);

$categoryName = stripslashes((string) $category['cat_name']);
$content = '<main class="documents-category">';
$content .= '<header class="documents-page-header"><h1>'
    . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</h1>';

if (!empty($category['cat_help'])) {
    $content .= '<p class="documents-page-description">'
        . htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8')
        . '</p>';
}
$content .= '</header>';

if (!empty($category['custom_header'])) {
    $content .= '<div class="documents-category__custom-header">'
        . (string) $category['custom_header'] . '</div>';
}

if ((int) $category['submitable'] === 1 && !COM_isAnonUser()) {
    $newUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
        . '/index.php?mode=new&cat=' . rawurlencode($categorySlug);
    $content .= '<p class="documents-category__actions"><a class="documents-action" href="'
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
    $content .= '<p class="documents-empty">'
        . htmlspecialchars(isset($LANG_DOCUMENTS_1['none']) ? $LANG_DOCUMENTS_1['none'] : 'No documents.', ENT_QUOTES, 'UTF-8')
        . '</p>';
} else {
    $content .= '<div class="documents-card-list">' . implode('', $cards) . '</div>';
}

$totalPages = ($total > 0) ? (int) ceil($total / $perPage) : 1;
if ($totalPages > 1) {
    $baseUrl = DOCUMENTS_interopCanonicalUrl($categorySlug);
    $content .= '<nav class="documents-pagination" aria-label="Pagination">';
    if ($pageNumber > 1) {
        $prevUrl = $baseUrl . (($pageNumber - 1) > 1 ? '?page=' . ($pageNumber - 1) : '');
        $content .= '<a class="documents-pagination__prev" href="'
            . htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') . '">&laquo; ';
        $content .= htmlspecialchars(isset($LANG_DOCUMENTS_1['previous']) ? $LANG_DOCUMENTS_1['previous'] : 'Previous', ENT_QUOTES, 'UTF-8') . '</a>';
    }
    $content .= '<span class="documents-pagination__status">'
        . (int) $pageNumber . ' / ' . (int) $totalPages . '</span>';
    if ($pageNumber < $totalPages) {
        $nextUrl = $baseUrl . '?page=' . ($pageNumber + 1);
        $content .= '<a class="documents-pagination__next" href="'
            . htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars(isset($LANG_DOCUMENTS_1['next']) ? $LANG_DOCUMENTS_1['next'] : 'Next', ENT_QUOTES, 'UTF-8')
            . ' &raquo;</a>';
    }
    $content .= '</nav>';
}

if (!empty($category['custom_footer'])) {
    $content .= '<div class="documents-category__custom-footer">'
        . (string) $category['custom_footer'] . '</div>';
}
$content .= '</main>';

$page = COM_createHTMLDocument($content, array('pagetitle' => $categoryName));
COM_output($page);
