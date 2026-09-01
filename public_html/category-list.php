<?php

/* Responsive public category list. Geeklog 2.1.1-2.2.2 / PHP 5.6+. */

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'integrity.php';
require_once $pluginPath . 'presentation.php';
require_once $pluginPath . 'public_document.php';

function DOCUMENTS_listFieldsForCategory($categoryId)
{
    global $_TABLES;
    $fields = array();
    $result = DB_query(
        "SELECT fid,f_name,f_order,f_type,sel_id,var_name,display_empty "
        . "FROM {$_TABLES['documents_fields']} WHERE cat_id=" . (int) $categoryId
        . " AND f_on_list=1 ORDER BY f_order ASC,fid ASC"
    );
    while ($row = DB_fetchArray($result)) {
        if (is_array($row)) {
            $fields[] = $row;
        }
    }
    return $fields;
}

function DOCUMENTS_listTitleField($categoryId)
{
    global $_TABLES;
    $row = DB_fetchArray(DB_query(
        "SELECT fid,f_name,f_type,sel_id,var_name,f_on_list,display_empty "
        . "FROM {$_TABLES['documents_fields']} WHERE cat_id=" . (int) $categoryId
        . " AND f_type IN ('text','textarea') "
        . "AND LOWER(var_name) NOT IN ('metadescription','schema_type') "
        . "ORDER BY f_order ASC,fid ASC LIMIT 1"
    ));
    return is_array($row) ? $row : array();
}

function DOCUMENTS_listValues($documentId, $fields)
{
    global $_TABLES;
    $ids = array();
    foreach ($fields as $field) {
        if (!empty($field['fid'])) {
            $ids[] = (int) $field['fid'];
        }
    }
    if (empty($ids)) {
        return array();
    }
    $values = array();
    $safe = DB_escapeString((string) $documentId);
    $result = DB_query(
        "SELECT field_id,v_value FROM {$_TABLES['documents_values']} "
        . "WHERE doc_url='{$safe}' AND field_id IN (" . implode(',', $ids) . ")"
    );
    while ($row = DB_fetchArray($result)) {
        $values[(int) $row['field_id']] = isset($row['v_value']) ? (string) $row['v_value'] : '';
    }
    return $values;
}

function DOCUMENTS_listShortText($value, $limit)
{
    $value = preg_replace('/\s+/u', ' ', trim(strip_tags(stripslashes((string) $value))));
    if ($value === '') {
        return '';
    }
    $limit = max(30, (int) $limit);
    if (function_exists('MBYTE_strlen') && function_exists('MBYTE_substr')) {
        return MBYTE_strlen($value) > $limit ? rtrim(MBYTE_substr($value, 0, $limit - 1)) . '…' : $value;
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value, 'UTF-8') > $limit
            ? rtrim(mb_substr($value, 0, $limit - 1, 'UTF-8')) . '…' : $value;
    }
    return strlen($value) > $limit ? rtrim(substr($value, 0, $limit - 3)) . '...' : $value;
}

function DOCUMENTS_listFieldHtml($field, $value, $title)
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
        $value = DOCUMENTS_publicChoiceValue(isset($field['sel_id']) ? (int) $field['sel_id'] : 0, $value);
    } elseif ($type === 'text') {
        $value = DOCUMENTS_formatTextDisplay($value, isset($field['sel_id']) ? (int) $field['sel_id'] : 0);
    }
    if ($type === 'image') {
        $filename = basename($value);
        if ($filename === '') {
            return '';
        }
        $src = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/image.php?src=' . rawurlencode($filename) . '&amp;w=180&amp;h=120';
        return '<img class="documents-list-image" src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
            . '" alt="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    }
    $value = DOCUMENTS_listShortText($value, $type === 'textarea' ? 180 : 120);
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function DOCUMENTS_listUrl($base, $parameters)
{
    $clean = array();
    foreach ($parameters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $clean[$key] = $value;
        }
    }
    return $base . (empty($clean) ? '' : '?' . http_build_query($clean, '', '&'));
}

function DOCUMENTS_listSortLink($base, $label, $field, $sort, $direction, $parameters)
{
    $parameters['sort'] = $field;
    $parameters['direction'] = ($sort === $field && $direction === 'asc') ? 'desc' : 'asc';
    $parameters['page'] = 1;
    $mark = '';
    if ($sort === $field) {
        $mark = $direction === 'asc' ? ' &#9650;' : ' &#9660;';
    }
    return '<a class="documents-list-sort" href="'
        . htmlspecialchars(DOCUMENTS_listUrl($base, $parameters), ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $mark . '</a>';
}

$categorySlug = isset($_GET['cat']) ? DOCUMENTS_normalizeRouteSlug((string) $_GET['cat']) : '';
if ($categorySlug === '') {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$safeSlug = DB_escapeString($categorySlug);
$category = DB_fetchArray(DB_query(
    "SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeSlug}' LIMIT 1"
));
if (!is_array($category) || empty($category['cid'])) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}
if (SEC_hasAccess(
    (int) $category['owner_id'], (int) $category['group_id'],
    (int) $category['perm_owner'], (int) $category['perm_group'],
    (int) $category['perm_members'], (int) $category['perm_anon']
) < 2) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$_REQUEST['mode'] = 'view';
$_REQUEST['cat'] = $categorySlug;
$_REQUEST['doc'] = '';
DOCUMENTS_preparePublicPresentation(false);
if (function_exists('DOCUMENTS_loadCategoryStyle') && !empty($category['css'])) {
    DOCUMENTS_loadCategoryStyle($category['css']);
}

if (isset($_SCRIPTS) && is_object($_SCRIPTS) && method_exists($_SCRIPTS, 'setCSSFile')) {
    $folder = !empty($_DOCUMENTS_CONF['documents_folder'])
        ? trim((string) $_DOCUMENTS_CONF['documents_folder'], '/') : 'documents';
    if (strtolower(get_class($_SCRIPTS)) === 'scripts') {
        $_SCRIPTS->setCSSFile('documents_list', '/' . $folder . '/css/documents-list.css', false);
    } else {
        $_SCRIPTS->setCSSFile(
            'documents_list',
            rtrim((string) $_CONF['site_url'], '/') . '/' . rawurlencode($folder) . '/css/documents-list.css'
        );
    }
}

$isFrench = isset($_CONF['language']) && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$isAdmin = SEC_hasRights('documents.admin');
$categoryId = (int) $category['cid'];
$fields = DOCUMENTS_listFieldsForCategory($categoryId);
$titleField = DOCUMENTS_listTitleField($categoryId);
$baseUrl = DOCUMENTS_interopCanonicalUrl($categorySlug);

$query = isset($_GET['q']) && !is_array($_GET['q']) ? trim((string) $_GET['q']) : '';
$query = function_exists('MBYTE_substr') ? MBYTE_substr($query, 0, 100) : substr($query, 0, 100);
$perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
if (!in_array($perPage, array(20, 50, 100), true)) {
    $perPage = 20;
}
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$direction = isset($_GET['direction']) && strtolower((string) $_GET['direction']) === 'asc' ? 'asc' : 'desc';
$sort = isset($_GET['sort']) && !is_array($_GET['sort']) ? trim((string) $_GET['sort']) : 'modified';

$sortable = array();
foreach ($fields as $field) {
    $type = strtolower((string) $field['f_type']);
    if (!in_array($type, array('image', 'marker', 'album'), true)) {
        $sortable[(string) $field['var_name']] = $field;
    }
}
if ($sort !== 'modified' && !isset($sortable[$sort])) {
    $sort = 'modified';
}

$where = " WHERE EXISTS (SELECT 1 FROM {$_TABLES['documents_values']} cv "
    . "INNER JOIN {$_TABLES['documents_fields']} cf ON cf.fid=cv.field_id "
    . "WHERE cv.doc_url=d.doc_url AND cf.cat_id={$categoryId})";
if (!$isAdmin) {
    $where .= " AND d.active=1";
}
$where .= COM_getPermSQL('AND', 0, 2, 'd');
if ($query !== '') {
    $safeQuery = DB_escapeString($query);
    $where .= " AND EXISTS (SELECT 1 FROM {$_TABLES['documents_values']} sv "
        . "INNER JOIN {$_TABLES['documents_fields']} sf ON sf.fid=sv.field_id "
        . "WHERE sv.doc_url=d.doc_url AND sf.cat_id={$categoryId} "
        . "AND sv.v_value LIKE '%{$safeQuery}%')";
}

$count = DB_fetchArray(DB_query("SELECT COUNT(*) total FROM {$_TABLES['documents_docs']} d" . $where));
$total = is_array($count) && isset($count['total']) ? (int) $count['total'] : 0;
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$join = '';
$order = "COALESCE(d.modified,d.created) " . strtoupper($direction) . ",d.did DESC";
if ($sort !== 'modified' && isset($sortable[$sort])) {
    $fid = (int) $sortable[$sort]['fid'];
    $join = " LEFT JOIN {$_TABLES['documents_values']} sort_value "
        . "ON sort_value.doc_url=d.doc_url AND sort_value.field_id={$fid}";
    $order = "sort_value.v_value " . strtoupper($direction) . ",d.did DESC";
}
$result = DB_query(
    "SELECT d.doc_url,d.did,d.active,d.created,d.modified FROM {$_TABLES['documents_docs']} d"
    . $join . $where . " ORDER BY {$order} LIMIT {$offset},{$perPage}"
);

$titleOnList = false;
if (!empty($titleField['fid'])) {
    foreach ($fields as $field) {
        if ((int) $field['fid'] === (int) $titleField['fid']) {
            $titleOnList = true;
            break;
        }
    }
}

$categoryName = stripslashes((string) $category['cat_name']);
$documentsLabel = isset($LANG_DOCUMENTS_1['plugin_name']) ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
$content = '<main class="documents-category"><nav class="documents-breadcrumb" aria-label="Breadcrumb">'
    . '<a href="' . htmlspecialchars(rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($documentsLabel, ENT_QUOTES, 'UTF-8') . '</a>'
    . '<span class="documents-breadcrumb__separator" aria-hidden="true"> &gt; </span>'
    . '<span aria-current="page">' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</span></nav>'
    . '<header class="documents-page-header"><h1>' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</h1>';
if (!empty($category['cat_help'])) {
    $content .= '<p class="documents-page-description">'
        . htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8') . '</p>';
}
$content .= '</header>';

if (!empty($category['custom_header'])) {
    $header = (string) $category['custom_header'];
    $content .= '<div class="documents-category-header">'
        . (function_exists('PLG_replaceTags') ? PLG_replaceTags($header) : $header) . '</div>';
}

$block = '';
if ((int) $category['submitable'] === 1 && !COM_isAnonUser()) {
    $newUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
        . '/index.php?mode=new&cat=' . rawurlencode($categorySlug);
    $block .= '<p class="documents-category__actions"><a class="documents-action" href="'
        . htmlspecialchars($newUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars(isset($LANG_DOCUMENTS_1['create_new_doc']) ? $LANG_DOCUMENTS_1['create_new_doc'] : 'Add a document', ENT_QUOTES, 'UTF-8')
        . '</a></p>';
}

$searchLabel = $isFrench ? 'Recherche' : 'Search';
$resultsLabel = $isFrench ? 'Résultats' : 'Results';
$applyLabel = $isFrench ? 'Soumettre' : 'Apply';
$recordsLabel = $isFrench ? 'Enregistrements' : 'Records';

$block .= '<form class="documents-list-controls" method="get" action="' . htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') . '">'
    . '<label>' . $searchLabel . ' <input type="search" name="q" value="' . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . '"></label>'
    . '<label>' . $resultsLabel . ' <select name="per_page">';
foreach (array(20, 50, 100) as $option) {
    $block .= '<option value="' . $option . '"' . ($perPage === $option ? ' selected="selected"' : '') . '>' . $option . '</option>';
}
$block .= '</select></label><input type="hidden" name="sort" value="' . htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') . '">'
    . '<input type="hidden" name="direction" value="' . $direction . '">'
    . '<button type="submit">' . $applyLabel . '</button>'
    . '<span class="documents-list-count">' . $recordsLabel . ' : ' . COM_numberFormat($total) . '</span></form>';

$params = array('q' => $query, 'per_page' => $perPage, 'sort' => $sort, 'direction' => $direction, 'page' => $page);
$statusLabels = array(
    DOCUMENTS_STATUS_INACTIVE => isset($LANG_DOCUMENTS_1['not_active']) ? $LANG_DOCUMENTS_1['not_active'] : 'Inactive',
    DOCUMENTS_STATUS_ACTIVE => isset($LANG_DOCUMENTS_1['active']) ? $LANG_DOCUMENTS_1['active'] : 'Active',
    DOCUMENTS_STATUS_DRAFT => isset($LANG_DOCUMENTS_1['draft']) ? $LANG_DOCUMENTS_1['draft'] : 'Draft',
    DOCUMENTS_STATUS_SUBMISSION => isset($LANG_DOCUMENTS_1['pending_moderation']) ? $LANG_DOCUMENTS_1['pending_moderation'] : 'Pending moderation'
);

if ($total === 0) {
    $block .= '<p class="documents-empty">' . ($isFrench ? 'Aucun document.' : 'No documents.') . '</p>';
} else {
    $block .= '<div class="documents-list-wrap"><table class="documents-list-table"><thead><tr>';
    if (!$titleOnList) {
        $block .= '<th scope="col">' . DOCUMENTS_listSortLink($baseUrl, 'Document', 'modified', $sort, $direction, $params) . '</th>';
    }
    foreach ($fields as $field) {
        $label = stripslashes((string) $field['f_name']);
        $type = strtolower((string) $field['f_type']);
        $block .= '<th scope="col">';
        $block .= in_array($type, array('image', 'marker', 'album'), true)
            ? htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            : DOCUMENTS_listSortLink($baseUrl, $label, (string) $field['var_name'], $sort, $direction, $params);
        $block .= '</th>';
    }
    $block .= '</tr></thead><tbody>';

    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['doc_url'])) {
            continue;
        }
        $item = DOCUMENTS_interopItem($row['doc_url'], 0);
        if (empty($item)) {
            continue;
        }
        $title = isset($item['title']) ? (string) $item['title'] : (string) $row['doc_url'];
        $url = isset($item['url']) ? $item['url'] : DOCUMENTS_interopCanonicalUrl($categorySlug, $row['doc_url']);
        $values = DOCUMENTS_listValues($row['doc_url'], $fields);
        $status = isset($row['active']) ? (int) $row['active'] : DOCUMENTS_STATUS_INACTIVE;
        $statusHtml = $isAdmin
            ? '<span class="documents-list-status documents-admin-status--' . $status . '">'
                . htmlspecialchars(isset($statusLabels[$status]) ? $statusLabels[$status] : (string) $status, ENT_QUOTES, 'UTF-8') . '</span>'
            : '';

        $block .= '<tr>';
        if (!$titleOnList) {
            $block .= '<td data-label="Document"><a class="documents-list-title" href="'
                . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
                . '</a>' . $statusHtml . '</td>';
        }
        foreach ($fields as $field) {
            $fid = (int) $field['fid'];
            $value = isset($values[$fid]) ? $values[$fid] : '';
            $label = stripslashes((string) $field['f_name']);
            $rendered = DOCUMENTS_listFieldHtml($field, $value, $title);
            $isTitle = $titleOnList && !empty($titleField['fid']) && $fid === (int) $titleField['fid'];
            $block .= '<td data-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';
            if ($isTitle) {
                $linkText = trim(strip_tags($rendered));
                $block .= '<a class="documents-list-title" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($linkText !== '' ? $linkText : $title, ENT_QUOTES, 'UTF-8') . '</a>' . $statusHtml;
            } else {
                $block .= $rendered;
            }
            $block .= '</td>';
        }
        $block .= '</tr>';
    }
    $block .= '</tbody></table></div>';
}

if ($totalPages > 1) {
    $block .= '<nav class="documents-pagination" aria-label="Pagination">';
    if ($page > 1) {
        $p = $params;
        $p['page'] = $page - 1;
        $block .= '<a href="' . htmlspecialchars(DOCUMENTS_listUrl($baseUrl, $p), ENT_QUOTES, 'UTF-8') . '">&laquo; '
            . ($isFrench ? 'Précédent' : 'Previous') . '</a>';
    }
    $block .= '<span class="documents-pagination__status">' . $page . ' / ' . $totalPages . '</span>';
    if ($page < $totalPages) {
        $p = $params;
        $p['page'] = $page + 1;
        $block .= '<a href="' . htmlspecialchars(DOCUMENTS_listUrl($baseUrl, $p), ENT_QUOTES, 'UTF-8') . '">'
            . ($isFrench ? 'Suivant' : 'Next') . ' &raquo;</a>';
    }
    $block .= '</nav>';
}

$content .= DOCUMENTS_sectionBlock(
    isset($LANG_DOCUMENTS_1['documents']) ? $LANG_DOCUMENTS_1['documents'] : 'Documents',
    $block
);

if (!empty($category['custom_footer'])) {
    $footer = (string) $category['custom_footer'];
    $content .= '<div class="documents-category-footer">'
        . (function_exists('PLG_replaceTags') ? PLG_replaceTags($footer) : $footer) . '</div>';
}
$content .= '</main>';
COM_output(DOCUMENTS_createPublicPage($content, $categoryName));
