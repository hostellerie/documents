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
        "SELECT fid, f_name, f_order, f_type, sel_id, var_name, display_empty "
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

function DOCUMENTS_categoryTitleField($categoryId)
{
    global $_TABLES;

    $categoryId = (int) $categoryId;
    $result = DB_query(
        "SELECT fid, f_name, f_order, f_type, sel_id, var_name, f_on_list, display_empty "
        . "FROM {$_TABLES['documents_fields']} "
        . "WHERE cat_id={$categoryId} AND f_type IN ('text','textarea') "
        . "AND LOWER(var_name) NOT IN ('metadescription','schema_type') "
        . "ORDER BY f_order ASC, fid ASC LIMIT 1"
    );
    $field = DB_fetchArray($result);

    return is_array($field) ? $field : array();
}

function DOCUMENTS_categoryListValues($documentId, $fields)
{
    global $_TABLES;

    $values = array();
    $fieldIds = array();
    foreach ($fields as $field) {
        if (isset($field['fid']) && (int) $field['fid'] > 0) {
            $fieldIds[] = (int) $field['fid'];
        }
    }
    if (empty($fieldIds)) {
        return $values;
    }

    $safeDocument = DB_escapeString((string) $documentId);
    $result = DB_query(
        "SELECT field_id, v_value FROM {$_TABLES['documents_values']} "
        . "WHERE doc_url='{$safeDocument}' AND field_id IN (" . implode(',', $fieldIds) . ")"
    );
    while ($row = DB_fetchArray($result)) {
        if (is_array($row)) {
            $values[(int) $row['field_id']] = isset($row['v_value']) ? (string) $row['v_value'] : '';
        }
    }

    return $values;
}

function DOCUMENTS_categoryListExcerpt($value, $length)
{
    $value = preg_replace('/\s+/u', ' ', trim(strip_tags(stripslashes((string) $value))));
    if ($value === '') {
        return '';
    }
    $length = max(20, (int) $length);
    if (function_exists('MBYTE_strlen') && function_exists('MBYTE_substr')) {
        return MBYTE_strlen($value) > $length
            ? rtrim(MBYTE_substr($value, 0, $length - 1)) . '…'
            : $value;
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value, 'UTF-8') > $length
            ? rtrim(mb_substr($value, 0, $length - 1, 'UTF-8')) . '…'
            : $value;
    }

    return strlen($value) > $length ? rtrim(substr($value, 0, $length - 3)) . '...' : $value;
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
        return !empty($field['display_empty']) ? '<span class="documents-list-empty">&#8212;</span>' : '';
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
            . '/image.php?src=' . rawurlencode($filename) . '&amp;w=180&amp;h=120';
        return '<img class="documents-list-image" src="'
            . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    }

    $value = DOCUMENTS_categoryListExcerpt($value, $type === 'textarea' ? 180 : 120);
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function DOCUMENTS_categoryListUrl($baseUrl, $parameters)
{
    $clean = array();
    foreach ($parameters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $clean[$key] = $value;
        }
    }

    return $baseUrl . (empty($clean) ? '' : '?' . http_build_query($clean, '', '&'));
}

function DOCUMENTS_categorySortHeader($baseUrl, $label, $field, $currentSort, $currentDirection, $parameters)
{
    $direction = ($currentSort === $field && $currentDirection === 'asc') ? 'desc' : 'asc';
    $parameters['sort'] = $field;
    $parameters['direction'] = $direction;
    $parameters['page'] = 1;
    $indicator = '';
    if ($currentSort === $field) {
        $indicator = $currentDirection === 'asc' ? ' &#9650;' : ' &#9660;';
    }

    return '<a class="documents-list-sort" href="'
        . htmlspecialchars(DOCUMENTS_categoryListUrl($baseUrl, $parameters), ENT_QUOTES, 'UTF-8')
        . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $indicator . '</a>';
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

$baseUrl = DOCUMENTS_interopCanonicalUrl($categorySlug);
$requestPath = isset($_SERVER['REQUEST_URI']) ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if (is_string($requestPath) && basename($requestPath) === 'category.php') {
    $redirectParameters = $_GET;
    unset($redirectParameters['cat']);
    header('Location: ' . DOCUMENTS_categoryListUrl($baseUrl, $redirectParameters), true, 301);
    exit;
}

$_REQUEST['mode'] = 'view';
$_REQUEST['cat'] = $categorySlug;
$_REQUEST['doc'] = '';

DOCUMENTS_preparePublicPresentation(false);
if (function_exists('DOCUMENTS_loadCategoryStyle') && !empty($category['css'])) {
    DOCUMENTS_loadCategoryStyle($category['css']);
}

$isFrench = isset($_CONF['language']) && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$isDocumentsAdmin = SEC_hasRights('documents.admin');
$categoryId = (int) $category['cid'];
$listFields = DOCUMENTS_categoryListFields($categoryId);
$titleField = DOCUMENTS_categoryTitleField($categoryId);

$query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if (function_exists('MBYTE_substr')) {
    $query = MBYTE_substr($query, 0, 100);
} else {
    $query = substr($query, 0, 100);
}
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
if (!in_array($perPage, array(20, 50, 100), true)) {
    $perPage = 20;
}
$pageNumber = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$direction = isset($_GET['direction']) && strtolower((string) $_GET['direction']) === 'asc' ? 'asc' : 'desc';
$sort = isset($_GET['sort']) ? trim((string) $_GET['sort']) : 'modified';

$sortableFields = array();
foreach ($listFields as $field) {
    $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';
    if (!in_array($type, array('image', 'marker', 'album'), true)) {
        $sortableFields[(string) $field['var_name']] = $field;
    }
}
if ($sort !== 'modified' && !isset($sortableFields[$sort])) {
    $sort = 'modified';
}

$where = " WHERE EXISTS (SELECT 1 FROM {$_TABLES['documents_values']} AS cv "
    . "INNER JOIN {$_TABLES['documents_fields']} AS cf ON cf.fid=cv.field_id "
    . "WHERE cv.doc_url=d.doc_url AND cf.cat_id={$categoryId})";
if (!$isDocumentsAdmin) {
    $where .= " AND d.active=1";
}
$where .= COM_getPermSQL('AND', 0, 2, 'd');

if ($query !== '') {
    $safeQuery = DB_escapeString($query);
    $where .= " AND EXISTS (SELECT 1 FROM {$_TABLES['documents_values']} AS sv "
        . "INNER JOIN {$_TABLES['documents_fields']} AS sf ON sf.fid=sv.field_id "
        . "WHERE sv.doc_url=d.doc_url AND sf.cat_id={$categoryId} "
        . "AND sv.v_value LIKE '%{$safeQuery}%')";
}

$countRow = DB_fetchArray(DB_query(
    "SELECT COUNT(*) AS total FROM {$_TABLES['documents_docs']} AS d" . $where
));
$total = is_array($countRow) && isset($countRow['total']) ? (int) $countRow['total'] : 0;
$totalPages = max(1, (int) ceil($total / $perPage));
if ($pageNumber > $totalPages) {
    $pageNumber = $totalPages;
}
$offset = ($pageNumber - 1) * $perPage;

$sortJoin = '';
$orderSql = "COALESCE(d.modified,d.created) " . strtoupper($direction) . ", d.did DESC";
if ($sort !== 'modified' && isset($sortableFields[$sort])) {
    $sortFid = (int) $sortableFields[$sort]['fid'];
    $sortJoin = " LEFT JOIN {$_TABLES['documents_values']} AS sort_value "
        . "ON sort_value.doc_url=d.doc_url AND sort_value.field_id={$sortFid}";
    $orderSql = "sort_value.v_value " . strtoupper($direction) . ", d.did DESC";
}

$result = DB_query(
    "SELECT d.doc_url, d.did, d.active, d.created, d.modified "
    . "FROM {$_TABLES['documents_docs']} AS d"
    . $sortJoin . $where
    . " ORDER BY {$orderSql} LIMIT {$offset}, {$perPage}"
);

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

$searchLabel = $isFrench ? 'Recherche' : 'Search';
$resultsLabel = $isFrench ? 'Résultats' : 'Results';
$submitLabel = $isFrench ? 'Soumettre' : 'Apply';
$recordsLabel = $isFrench ? 'Enregistrements' : 'Records';

$documentsBlock .= '<form class="documents-list-controls" method="get" action="'
    . htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') . '">'
    . '<label>' . htmlspecialchars($searchLabel, ENT_QUOTES, 'UTF-8')
    . ' <input type="search" name="q" value="' . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . '"></label>'
    . '<label>' . htmlspecialchars($resultsLabel, ENT_QUOTES, 'UTF-8') . ' <select name="per_page">';
foreach (array(20, 50, 100) as $option) {
    $documentsBlock .= '<option value="' . $option . '"' . ($perPage === $option ? ' selected="selected"' : '') . '>'
        . $option . '</option>';
}
$documentsBlock .= '</select></label>'
    . '<input type="hidden" name="sort" value="' . htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') . '">'
    . '<input type="hidden" name="direction" value="' . htmlspecialchars($direction, ENT_QUOTES, 'UTF-8') . '">'
    . '<button type="submit">' . htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') . '</button>'
    . '<span class="documents-list-count">' . htmlspecialchars($recordsLabel, ENT_QUOTES, 'UTF-8')
    . ' : ' . COM_numberFormat($total) . '</span></form>';

$titleFieldOnList = false;
if (!empty($titleField['fid'])) {
    foreach ($listFields as $field) {
        if ((int) $field['fid'] === (int) $titleField['fid']) {
            $titleFieldOnList = true;
            break;
        }
    }
}

$statusLabels = array(
    DOCUMENTS_STATUS_INACTIVE => isset($LANG_DOCUMENTS_1['not_active']) ? $LANG_DOCUMENTS_1['not_active'] : 'Inactive',
    DOCUMENTS_STATUS_ACTIVE => isset($LANG_DOCUMENTS_1['active']) ? $LANG_DOCUMENTS_1['active'] : 'Active',
    DOCUMENTS_STATUS_DRAFT => isset($LANG_DOCUMENTS_1['draft']) ? $LANG_DOCUMENTS_1['draft'] : 'Draft',
    DOCUMENTS_STATUS_SUBMISSION => isset($LANG_DOCUMENTS_1['pending_moderation'])
        ? $LANG_DOCUMENTS_1['pending_moderation'] : 'Pending moderation'
);

$commonParameters = array(
    'q' => $query,
    'per_page' => $perPage,
    'sort' => $sort,
    'direction' => $direction,
    'page' => $pageNumber
);

if ($total <= 0) {
    $documentsBlock .= '<p class="documents-empty">'
        . htmlspecialchars(isset($LANG_DOCUMENTS_1['none']) ? $LANG_DOCUMENTS_1['none'] : 'No documents.', ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    $documentsBlock .= '<div class="documents-list-wrap"><table class="documents-list-table"><thead><tr>';
    if (!$titleFieldOnList) {
        $documentsBlock .= '<th scope="col">'
            . DOCUMENTS_categorySortHeader($baseUrl, 'Document', 'modified', $sort, $direction, $commonParameters)
            . '</th>';
    }
    foreach ($listFields as $field) {
        $label = isset($field['f_name']) ? stripslashes((string) $field['f_name']) : '';
        $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';
        if (in_array($type, array('image', 'marker', 'album'), true)) {
            $documentsBlock .= '<th scope="col">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</th>';
        } else {
            $documentsBlock .= '<th scope="col">'
                . DOCUMENTS_categorySortHeader(
                    $baseUrl,
                    $label,
                    (string) $field['var_name'],
                    $sort,
                    $direction,
                    $commonParameters
                ) . '</th>';
        }
    }
    $documentsBlock .= '</tr></thead><tbody>';

    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['doc_url'])) {
            continue;
        }
        $item = DOCUMENTS_interopItem($row['doc_url'], 0);
        if (empty($item)) {
            continue;
        }
        $title = isset($item['title']) ? (string) $item['title'] : (string) $row['doc_url'];
        $url = isset($item['url']) ? (string) $item['url'] : DOCUMENTS_interopCanonicalUrl($categorySlug, $row['doc_url']);
        $values = DOCUMENTS_categoryListValues($row['doc_url'], $listFields);

        $documentsBlock .= '<tr>';
        if (!$titleFieldOnList) {
            $documentsBlock .= '<td data-label="Document">'
                . '<a class="documents-list-title" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a>';
            if ($isDocumentsAdmin) {
                $status = isset($row['active']) ? (int) $row['active'] : DOCUMENTS_STATUS_INACTIVE;
                $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : (string) $status;
                $documentsBlock .= '<span class="documents-list-status documents-admin-status--' . $status . '">'
                    . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</span>';
            }
            $documentsBlock .= '</td>';
        }

        foreach ($listFields as $field) {
            $fid = isset($field['fid']) ? (int) $field['fid'] : 0;
            $value = isset($values[$fid]) ? $values[$fid] : '';
            $label = isset($field['f_name']) ? stripslashes((string) $field['f_name']) : '';
            $rendered = DOCUMENTS_categoryListFieldHtml($field, $value, $title);
            $isTitleCell = $titleFieldOnList && !empty($titleField['fid']) && $fid === (int) $titleField['fid'];

            $documentsBlock .= '<td data-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';
            if ($isTitleCell) {
                $linkText = trim(strip_tags($rendered));
                if ($linkText === '') {
                    $linkText = $title;
                }
                $documentsBlock .= '<a class="documents-list-title" href="'
                    . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8') . '</a>';
                if ($isDocumentsAdmin) {
                    $status = isset($row['active']) ? (int) $row['active'] : DOCUMENTS_STATUS_INACTIVE;
                    $statusLabel = isset($statusLabels[$status]) ? $statusLabels[$status] : (string) $status;
                    $documentsBlock .= '<span class="documents-list-status documents-admin-status--' . $status . '">'
                        . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</span>';
                }
            } else {
                $documentsBlock .= $rendered;
            }
            $documentsBlock .= '</td>';
        }
        $documentsBlock .= '</tr>';
    }
    $documentsBlock .= '</tbody></table></div>';
}

if ($totalPages > 1) {
    $documentsBlock .= '<nav class="documents-pagination" aria-label="Pagination">';
    if ($pageNumber > 1) {
        $parameters = $commonParameters;
        $parameters['page'] = $pageNumber - 1;
        $documentsBlock .= '<a href="'
            . htmlspecialchars(DOCUMENTS_categoryListUrl($baseUrl, $parameters), ENT_QUOTES, 'UTF-8')
            . '">&laquo; ' . ($isFrench ? 'Précédent' : 'Previous') . '</a>';
    }
    $documentsBlock .= '<span class="documents-pagination__status">' . $pageNumber . ' / ' . $totalPages . '</span>';
    if ($pageNumber < $totalPages) {
        $parameters = $commonParameters;
        $parameters['page'] = $pageNumber + 1;
        $documentsBlock .= '<a href="'
            . htmlspecialchars(DOCUMENTS_categoryListUrl($baseUrl, $parameters), ENT_QUOTES, 'UTF-8')
            . '">' . ($isFrench ? 'Suivant' : 'Next') . ' &raquo;</a>';
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
