<?php

/* Modern public document form controller. Geeklog 2.1.1-2.2.2 / PHP 5.6+. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'presentation.php';
require_once $pluginPath . 'include_edit.php';

DOCUMENTS_preparePublicPresentation();

$mode = isset($_REQUEST['mode']) && !is_array($_REQUEST['mode'])
    ? trim((string) $_REQUEST['mode']) : 'new';
if ($mode !== 'new' && $mode !== 'edit') {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

if ($mode === 'new' && COM_isAnonUser()) {
    echo COM_refresh($_CONF['site_url'] . '/users.php?mode=login');
    exit;
}

$doc = array();
$category = array();

if ($mode === 'new') {
    $categorySlug = isset($_REQUEST['cat']) && !is_array($_REQUEST['cat'])
        ? trim((string) $_REQUEST['cat']) : '';
    if ($categorySlug === '') {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    $categorySql = DB_escapeString($categorySlug);
    $result = DB_query(
        "SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url='{$categorySql}' LIMIT 1"
    );
    $category = DB_fetchArray($result);
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
    if ($categoryAccess < 2
        || ((int) $category['submitable'] === 0 && !SEC_hasRights('documents.admin'))) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    $doc = array(
        'cid' => (int) $category['cid'],
        'cat_name' => $category['cat_name'],
        'cat_url' => $category['cat_url'],
        'cat_order' => $category['cat_order'],
        'css' => $category['css'],
        'template' => $category['template'],
        'list_index' => $category['list_index'],
        'submitable' => $category['submitable'],
        'cat_help' => $category['cat_help'],
        'custom_header' => $category['custom_header'],
        'custom_footer' => $category['custom_footer'],
        'owner_id' => isset($_USER['uid']) ? (int) $_USER['uid'] : (int) $category['owner_id'],
        'group_id' => $category['group_id'],
        'perm_owner' => $category['perm_owner'],
        'perm_group' => $category['perm_group'],
        'perm_members' => $category['perm_members'],
        'perm_anon' => $category['perm_anon'],
        'active' => 1
    );
} else {
    $documentSlug = isset($_REQUEST['doc_url']) && !is_array($_REQUEST['doc_url'])
        ? trim((string) $_REQUEST['doc_url']) : '';
    if ($documentSlug === '') {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    $documentSql = DB_escapeString($documentSlug);
    $result = DB_query(
        "SELECT d.doc_url, d.active, d.owner_id, d.group_id, d.perm_owner, d.perm_group, "
        . "d.perm_members, d.perm_anon, f.cat_id, c.* "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "LEFT JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE d.doc_url='{$documentSql}' ORDER BY f.f_order ASC LIMIT 1"
    );
    $row = DB_fetchArray($result);
    if (!is_array($row) || empty($row['doc_url']) || empty($row['cid'])) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    $access = SEC_hasAccess(
        (int) $row['owner_id'],
        (int) $row['group_id'],
        (int) $row['perm_owner'],
        (int) $row['perm_group'],
        (int) $row['perm_members'],
        (int) $row['perm_anon']
    );
    if ($access < 3) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
    if ((int) $row['submitable'] === 0 && !SEC_hasRights('documents.admin')) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    $category = $row;
    $doc = array(
        'cid' => (int) $row['cid'],
        'doc_url' => $row['doc_url'],
        'cat_name' => $row['cat_name'],
        'cat_url' => $row['cat_url'],
        'cat_order' => $row['cat_order'],
        'css' => $row['css'],
        'template' => $row['template'],
        'list_index' => $row['list_index'],
        'submitable' => $row['submitable'],
        'cat_help' => $row['cat_help'],
        'custom_header' => $row['custom_header'],
        'custom_footer' => $row['custom_footer'],
        'active' => $row['active'],
        'owner_id' => $row['owner_id'],
        'group_id' => $row['group_id'],
        'perm_owner' => $row['perm_owner'],
        'perm_group' => $row['perm_group'],
        'perm_members' => $row['perm_members'],
        'perm_anon' => $row['perm_anon'],
        'v_value' => array()
    );

    $values = DB_query(
        "SELECT field_id, v_value FROM {$_TABLES['documents_values']} "
        . "WHERE doc_url='{$documentSql}' ORDER BY vid ASC"
    );
    while ($valueRow = DB_fetchArray($values)) {
        if (is_array($valueRow) && isset($valueRow['field_id'])) {
            $doc['v_value'][(int) $valueRow['field_id']] = $valueRow['v_value'];
        }
    }
}

if (!defined('CAT_NAME')) {
    define('CAT_NAME', stripslashes((string) $category['cat_name']));
}
if (!defined('CAT_URL')) {
    define('CAT_URL', (string) $category['cat_url']);
}
if ($mode === 'edit' && !defined('DOC_URL')) {
    define('DOC_URL', (string) $doc['doc_url']);
}

$form = DOCUMENTS_editDoc($doc);
$title = $mode === 'new'
    ? ($isFrench ? 'Créer un document' : 'Create a document')
    : ($isFrench ? 'Modifier le document' : 'Edit document');
$categoryName = stripslashes((string) $category['cat_name']);

$content = DOCUMENTS_renderNavigation();
$content .= '<main class="documents-document-form">'
    . '<header class="documents-page-header"><h1>'
    . htmlspecialchars($title . ' — ' . $categoryName, ENT_QUOTES, 'UTF-8')
    . '</h1>';

$help = isset($category['cat_help']) ? trim((string) $category['cat_help']) : '';
if ($help !== '') {
    $content .= '<div class="documents-page-intro">' . $help . '</div>';
}
$content .= '</header>';

$msg = isset($_REQUEST['msg']) && !is_array($_REQUEST['msg'])
    ? trim((string) $_REQUEST['msg']) : '';
if ($msg !== '') {
    $content .= '<div class="documents-message">'
        . htmlspecialchars(stripslashes($msg), ENT_QUOTES, 'UTF-8') . '</div>';
}

$content .= '<section class="documents-form-card">' . $form . '</section></main>';

COM_output(DOCUMENTS_createPublicPage($content, $title . ' - ' . $categoryName));
