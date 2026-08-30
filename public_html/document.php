<?php

/* Modern default public document renderer. Geeklog 2.1.1/PHP 5.6+. */

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
$documentSlug = isset($_GET['doc']) ? DOCUMENTS_normalizeRouteSlug((string) $_GET['doc']) : '';
if ($categorySlug === '' || $documentSlug === '') {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$safeCategory = DB_escapeString($categorySlug);
$safeDocument = DB_escapeString($documentSlug);
$category = DB_fetchArray(DB_query(
    "SELECT cid, cat_name, cat_url, template, custom_header, custom_footer, "
    . "owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
    . "FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeCategory}' LIMIT 1"
));
if (!is_array($category) || empty($category['cid'])) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$categoryAccess = SEC_hasAccess(
    (int) $category['owner_id'],
    (int) $category['group_id'],
    (int) $category['perm_owner'],
    (int) $category['perm_group'],
    (int) $category['perm_members'],
    (int) $category['perm_anon']
);
if ($categoryAccess < 2) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$categoryId = (int) $category['cid'];
$document = DB_fetchArray(DB_query(
    "SELECT d.* FROM {$_TABLES['documents_docs']} AS d "
    . "WHERE d.doc_url='{$safeDocument}' AND EXISTS ("
    . "SELECT 1 FROM {$_TABLES['documents_values']} AS v "
    . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
    . "WHERE v.doc_url=d.doc_url AND f.cat_id={$categoryId}) LIMIT 1"
));

if (!is_array($document) || empty($document['doc_url']) || !DOCUMENTS_canViewDocument($document, 2)) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$requestPath = isset($_SERVER['REQUEST_URI'])
    ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)
    : '';
if (is_string($requestPath) && basename($requestPath) === 'document.php') {
    header('Location: ' . DOCUMENTS_interopCanonicalUrl($categorySlug, $documentSlug), true, 301);
    exit;
}

/* Marker and MediaGallery album fields stay on the modern renderer. Their
 * owning plugins keep responsibility for storage and public rendering. */
$albumCount = (int) DB_getItem(
    $_TABLES['documents_fields'],
    'COUNT(*)',
    "cat_id={$categoryId} AND f_type='album'"
);
$markerCount = (int) DB_getItem(
    $_TABLES['documents_fields'],
    'COUNT(*)',
    "cat_id={$categoryId} AND f_type='marker'"
);
$templateName = isset($category['template']) ? DOCUMENTS_templateName($category['template']) : '';
$templateDir = ($templateName !== '') ? DOCUMENTS_customTemplateReadDir($templateName) : '';
$externalRendererCount = $albumCount + $markerCount;
$useLegacyRenderer = ($templateDir !== '' && $externalRendererCount === 0)
    || ((int) $document['active'] !== DOCUMENTS_STATUS_ACTIVE && $externalRendererCount === 0);

$_REQUEST['mode'] = 'view';
$_REQUEST['cat'] = $categorySlug;
$_REQUEST['doc'] = $documentSlug;

if ($useLegacyRenderer) {
    DOCUMENTS_initializeRequestDefaults($_REQUEST);
    if ((int) $document['active'] === DOCUMENTS_STATUS_ACTIVE
        && !defined('DOCUMENTS_SEO_BUFFER_STARTED')) {
        define('DOCUMENTS_SEO_BUFFER_STARTED', true);
        ob_start('DOCUMENTS_seoOutputFilter');
    }
    require $pluginPath . 'include_html.php';
    exit;
}

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

function DOCUMENTS_publicDocumentSelectValue($groupId, $storedValue)
{
    global $_TABLES;

    $groupId = (int) $groupId;
    $storedValue = (string) $storedValue;
    if ($groupId <= 0 || $storedValue === '') {
        return $storedValue;
    }

    $safeValue = DB_escapeString($storedValue);
    $value = DB_getItem(
        $_TABLES['documents_selects'],
        's_value',
        "s_group={$groupId} AND s_name='{$safeValue}'"
    );

    return $value === '' ? $storedValue : (string) $value;
}

function DOCUMENTS_publicMarkerValue($markerId)
{
    $markerId = preg_replace('/[^0-9]/', '', (string) $markerId);
    if ($markerId === '' || !DOCUMENTS_hasMaps() || !function_exists('PLG_invokeService')) {
        return '';
    }

    $output = '';
    $svcMsg = array();
    $result = PLG_invokeService(
        'maps',
        'marker_render',
        array(
            'marker_id' => $markerId,
            'width' => '100%',
            'height' => '400px',
            'zoom' => 14
        ),
        $output,
        $svcMsg
    );

    return ($result === PLG_RET_OK) ? (string) $output : '';
}

function DOCUMENTS_publicDocumentValue($field, $value, $title)
{
    global $_DOCUMENTS_CONF;

    $type = isset($field['f_type']) ? (string) $field['f_type'] : '';
    $value = (string) $value;

    if ($type === 'checkbox') {
        return ((int) $value === 1)
            ? '<span class="documents-boolean documents-boolean--true" aria-label="true">&#10003;</span>'
            : '<span class="documents-boolean documents-boolean--false" aria-label="false">&#8212;</span>';
    }

    if ($value === '') {
        return '';
    }

    if ($type === 'marker') {
        return DOCUMENTS_publicMarkerValue($value);
    }

    if ($type === 'album') {
        return function_exists('DOCUMENTS_mediaGalleryRenderAlbum')
            ? DOCUMENTS_mediaGalleryRenderAlbum($value)
            : '';
    }

    if ($type === 'select') {
        $value = DOCUMENTS_publicDocumentSelectValue(
            isset($field['sel_id']) ? (int) $field['sel_id'] : 0,
            $value
        );
    } elseif ($type === 'text' && function_exists('DOCUMENTS_formatTextDisplay')) {
        $value = DOCUMENTS_formatTextDisplay($value, isset($field['sel_id']) ? $field['sel_id'] : 0);
    }

    if ($type === 'image') {
        $filename = basename($value);
        if ($filename === '') {
            return '';
        }
        $src = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/image.php?src=' . rawurlencode($filename) . '&amp;w=900';
        return '<img class="documents-document-image" src="'
            . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    }

    $safe = htmlspecialchars(stripslashes($value), ENT_QUOTES, 'UTF-8');
    if ($type === 'textarea') {
        return nl2br($safe);
    }

    return $safe;
}

$fieldsResult = DB_query(
    "SELECT f.fid, f.f_name, f.f_order, f.f_type, f.sel_id, f.var_name, f.f_required, f.f_on_list, "
    . "v.v_value FROM {$_TABLES['documents_fields']} AS f "
    . "LEFT JOIN {$_TABLES['documents_values']} AS v "
    . "ON v.field_id=f.fid AND v.doc_url='{$safeDocument}' "
    . "WHERE f.cat_id={$categoryId} ORDER BY f.f_order ASC, f.fid ASC"
);

$fields = array();
while ($field = DB_fetchArray($fieldsResult)) {
    if (is_array($field)) {
        $fields[] = $field;
    }
}

$title = $documentSlug;
if (!empty($fields)) {
    $candidate = trim(stripslashes((string) $fields[0]['v_value']));
    if ($candidate !== '') {
        $title = $candidate;
    }
}

$details = '<dl class="documents-fields">';
foreach ($fields as $index => $field) {
    if ($index === 0) {
        continue;
    }
    $value = isset($field['v_value']) ? $field['v_value'] : '';
    $rendered = DOCUMENTS_publicDocumentValue($field, $value, $title);
    if ($rendered === '' && $field['f_type'] !== 'checkbox') {
        continue;
    }
    $details .= '<div class="documents-field documents-field--'
        . htmlspecialchars((string) $field['f_type'], ENT_QUOTES, 'UTF-8') . '">'
        . '<dt class="documents-field__label">'
        . htmlspecialchars(stripslashes((string) $field['f_name']), ENT_QUOTES, 'UTF-8')
        . '</dt><dd class="documents-field__value">' . $rendered . '</dd></div>';
}
$details .= '</dl>';

$access = SEC_hasAccess(
    (int) $document['owner_id'],
    (int) $document['group_id'],
    (int) $document['perm_owner'],
    (int) $document['perm_group'],
    (int) $document['perm_members'],
    (int) $document['perm_anon']
);

$edit = '';
if ($access >= 3) {
    $editUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
        . '/index.php?mode=edit&doc_url=' . rawurlencode($documentSlug)
        . '&cat=' . $categoryId;
    $edit = ' <a class="documents-edit-link" href="'
        . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars(isset($LANG_DOCUMENTS_1['edit']) ? $LANG_DOCUMENTS_1['edit'] : 'Edit', ENT_QUOTES, 'UTF-8')
        . '</a>';
}

DB_query(
    "UPDATE {$_TABLES['documents_docs']} SET hits=hits+1 WHERE doc_url='{$safeDocument}'"
);
$hits = isset($document['hits']) ? ((int) $document['hits'] + 1) : 1;

$template = COM_newTemplate($pluginPath . 'templates');
$template->set_file(array('doc' => 'document.thtml'));
$template->set_var('doc_name', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'));
$template->set_var('active', '');
$template->set_var('editor', $edit);
$template->set_var('raws', $details);

$authorUrl = $_CONF['site_url'] . '/users.php?mode=profile&uid=' . (int) $document['owner_id'];
$template->set_var(
    'user_name',
    COM_createLink(COM_getDisplayName((int) $document['owner_id']), $authorUrl)
);
$template->set_var('doc_by', isset($LANG_DOCUMENTS_1['doc_by']) ? $LANG_DOCUMENTS_1['doc_by'] : 'By');
$template->set_var('displayed', isset($LANG_DOCUMENTS_1['displayed']) ? $LANG_DOCUMENTS_1['displayed'] : 'Viewed');
$template->set_var('times', isset($LANG_DOCUMENTS_1['times']) ? $LANG_DOCUMENTS_1['times'] : 'times');
$template->set_var('hits', COM_numberFormat($hits));
$template->set_var('document_url', DOCUMENTS_interopCanonicalUrl($categorySlug, $documentSlug));

require_once $_CONF['path_system'] . 'lib-comment.php';
$template->set_var(
    'commentbar',
    CMT_userComments(
        $documentSlug,
        $title,
        'documents',
        'ASC',
        'nested',
        0,
        1,
        false,
        false,
        0
    )
);

$body = '';
if (!empty($category['custom_header'])) {
    $body .= '<div class="documents-category-header">'
        . PLG_replaceTags((string) $category['custom_header']) . '</div>';
}
$body .= $template->finish($template->parse('output', 'doc'));
if (!empty($category['custom_footer'])) {
    $body .= '<div class="documents-category-footer">'
        . PLG_replaceTags((string) $category['custom_footer']) . '</div>';
}

DOCUMENTS_applyDocumentSeo($documentSlug);
COM_output(COM_createHTMLDocument($body, array('pagetitle' => $title)));
