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
require_once $pluginPath . 'public_document.php';

function DOCUMENTS_categoryListFields($categoryId)
{
    global $_TABLES;

    $fields = array();
    $result = DB_query(
        "SELECT fid, f_name, f_order, f_type, sel_id, var_name "
        . "FROM {$_TABLES['documents_fields']} "
        . "WHERE cat_id=" . (int) $categoryId . " AND f_on_list=1 "
        . "ORDER BY f_order ASC, fid ASC"
    );

    while ($field = DB_fetchArray($result)) {
        if (is_array($field)) {
            $fields[] = $field;
        }
    }

    return $fields;
}

function DOCUMENTS_categoryListFieldHtml($field, $value, $title)
{
    global $_DOCUMENTS_CONF;

    $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';
    $value = (string) $value;

    if ($type === 'checkbox') {
        return ((int) $value === 1)
            ? '<span class="documents-boolean documents-boolean--true" aria-label="true">&#10003;</span>'
            : '<span class="documents-boolean documents-boolean--false" aria-label="false">&#8212;</span>';
    }

    if ($value === '') {
        return '';
    }

    if ($type === 'select' || $type === 'radio') {
        $value = DOCUMENTS_publicChoiceValue(
            isset($field['sel_id']) ? (int) $field['sel_id'] : 0,
            $value
        );
    } elseif ($type === 'text') {
        $value = DOCUMENTS_formatTextDisplay(
            $value,
            isset($field['sel_id']) ? (int) $field['sel_id'] : 0
        );
    }

    if ($type === 'image') {
        $filename = basename($value);
        if ($filename === '') {
            return '';
        }
        $src = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/image.php?src=' . rawurlencode($filename) . '&amp;w=180';
        return '<img class="documents-card-field__image" src="'
            . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    }

    if ($type === 'marker' || $type === 'album' || $type === 'file' || $type === 'category') {
        return htmlspecialchars(stripslashes($value), ENT_QUOTES, 'UTF-8');
    }

    $safe = htmlspecialchars(stripslashes($value), ENT_QUOTES, 'UTF-8');
    return $type === 'textarea' ? nl2br($safe) : $safe;
}

function DOCUMENTS_categoryListFieldsHtml($documentId, $fields, $title)
{
    global $_TABLES;

    if (!is_array($fields) || empty($fields)) {
        return '';
    }

    $safeDocument = DB_escapeString((string) $documentId);
    $rows = '';
    $titleConsumed = false;

    foreach ($fields as $field) {
        $fieldId = isset($field['fid']) ? (int) $field['fid'] : 0;
        if ($fieldId <= 0) {
            continue;
        }

        $value = (string) DB_getItem(
            $_TABLES['documents_values'],
            'v_value',
            "doc_url='{$safeDocument}' AND field_id={$fieldId}"
        );

        $plain = trim(stripslashes($value));
        $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';
        if (!$titleConsumed
            && $plain !== ''
            && $plain === trim((string) $title)
            && ($type === 'text' || $type === 'textarea')) {
            $titleConsumed = true;
            continue;
        }

        $rendered = DOCUMENTS_categoryListFieldHtml($field, $value, $title);
        if ($rendered === '' && $type !== 'checkbox') {
            continue;
        }

        $label = isset($field['f_name']) ? stripslashes((string) $field['f_name']) : '';
        $rows .= '<div class="documents-card-field">'
            . '<dt class="documents-card-field__label">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</dt><dd class="documents-card-field__value">'
            . $rendered . '</dd></div>';
    }

    return $rows === '' ? '' : '<dl class="documents-card-fields">' . $rows . '</dl>';
}

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
$listFields = DOCUMENTS_categoryListFields($categoryId);
$countSql = "SELECT COUNT(DISTINCT d.doc_url) AS total FROM {$_TABLES['documents_docs']} AS d "
    . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
    . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
    . "WHERE f.cat_id={$categoryId}" . COM_getPermSQL('AND', 0, 2, 'd');
$countRow = DB_fetchArray(DB_query($countSql));
$total = is_array($countRow) && isset($countRow['total']) ? (int) $countRow['total'] : 0;
$totalPages = ($total > 0) ? (int) ceil($total / $perPage) : 1;
if ($pageNumber > $totalPages) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$sql = "SELECT DISTINCT d.doc_url, d.did, d.active, COALESCE(d.modified,d.created) AS changed_at "
    . "FROM {$_TABLES['documents_docs']} AS d "
    . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
    . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
    . "WHERE f.cat_id={$categoryId}" . COM_getPermSQL('AND', 0, 2, 'd')
    . " ORDER BY changed_at DESC, d.did DESC LIMIT {$offset}, {$perPage}";
$result = DB_query($sql);

$categoryName = stripslashes((string) $category['cat_name']);
$documentsLabel = isset($LANG_DOCUMENTS_1['plugin_name'])
    ? (string) $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
$documentsUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/';

$content = '<main class="documents-category">';
$content .= '<nav class="documents-breadcrumb" aria-label="Breadcrumb">'
    . '<a href="' . htmlspecialchars($documentsUrl, ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($documentsLabel, ENT_QUOTES, 'UTF-8') . '</a>'
    . '<span class="documents-breadcrumb__separator" aria-hidden="true"> &gt; </span>'
    . '<span aria-current="page">' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</span>'
    . '</nav>';
$content .= '<header class="documents-page-header"><h1>'
    . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</h1>';
if (!empty($category['cat_help'])) {
    $content .= '<p class="documents-page-description">'
        . htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8') . '</p>';
}
$content .= '</header>';

if (!empty($category['custom_header'])) {
    $customHeader = (string) $category['custom_header'];
    if (function_exists('PLG_replaceTags')) {
        $customHeader = PLG_replaceTags($customHeader);
    }
    $content .= '<div class="documents-category-header">' . $customHeader . '</div>';
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

$isDocumentsAdmin = SEC_hasRights('documents.admin');
$statusLabels = array(
    DOCUMENTS_STATUS_INACTIVE => isset($LANG_DOCUMENTS_1['not_active']) ? $LANG_DOCUMENTS_1['not_active'] : 'Inactive',
    DOCUMENTS_STATUS_ACTIVE => isset($LANG_DOCUMENTS_1['active']) ? $LANG_DOCUMENTS_1['active'] : 'Active',
    DOCUMENTS_STATUS_DRAFT => isset($LANG_DOCUMENTS_1['draft']) ? $LANG_DOCUMENTS_1['draft'] : 'Draft',
    DOCUMENTS_STATUS_SUBMISSION => isset($LANG_DOCUMENTS_1['pending_moderation'])
        ? $LANG_DOCUMENTS_1['pending_moderation'] : 'Pending moderation'
);

$cards = array();
while ($row = DB_fetchArray($result)) {
    if (!is_array($row) || empty($row['doc_url'])) {
        continue;
    }
    $item = DOCUMENTS_interopItem($row['doc_url'], 0);
    if (!empty($item)) {
        $card = DOCUMENTS_renderItemCard($item);
        $listFieldsHtml = DOCUMENTS_categoryListFieldsHtml(
            $row['doc_url'],
            $listFields,
            isset($item['title']) ? $item['title'] : $row['doc_url']
        );
        if ($listFieldsHtml !== '') {
            $card = str_replace(
                '</div></article>',
                $listFieldsHtml . '</div></article>',
                $card
            );
        }
        if ($isDocumentsAdmin) {
            $status = isset($row['active']) ? (int) $row['active'] : DOCUMENTS_STATUS_INACTIVE;
            $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : (string) $status;
            $card = '<div class="documents-admin-list-item">'
                . '<div class="documents-admin-status documents-admin-status--' . $status . '">'
                . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8')
                . '</div>' . $card . '</div>';
        }
        $cards[] = $card;
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
    $customFooter = (string) $category['custom_footer'];
    if (function_exists('PLG_replaceTags')) {
        $customFooter = PLG_replaceTags($customFooter);
    }
    $content .= '<div class="documents-category-footer">' . $customFooter . '</div>';
}
$content .= '</main>';

COM_output(DOCUMENTS_createPublicPage($content, $categoryName));
