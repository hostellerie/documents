<?php

/* Secure mutation endpoint for Documents 1.2.0 default administration forms. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

if (!SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

if (!SEC_checkToken()) {
    if (function_exists('http_response_code')) {
        http_response_code(403);
    } else {
        header('HTTP/1.1 403 Forbidden');
    }
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
if (!function_exists('DOCUMENTS_normalizeRouteSlug')) {
    require_once $pluginPath . 'include_compat.php';
}
require_once $pluginPath . 'admin_mutations.php';
require_once $pluginPath . 'admin_messages.php';

function DOCUMENTS_adminEndpointCategoryFields($categoryId)
{
    global $_TABLES;

    $categoryId = (int) $categoryId;
    $ids = array();
    if ($categoryId <= 0) {
        return $ids;
    }

    $result = DB_query(
        "SELECT fid FROM {$_TABLES['documents_fields']} WHERE cat_id={$categoryId}"
    );
    while ($row = DB_fetchArray($result)) {
        if (is_array($row) && !empty($row['fid'])) {
            $ids[] = (int) $row['fid'];
        }
    }
    return $ids;
}

function DOCUMENTS_adminEndpointCleanupFieldValues($fieldIds)
{
    global $_TABLES;

    if (!is_array($fieldIds)) {
        return;
    }
    foreach ($fieldIds as $fieldId) {
        $fieldId = (int) $fieldId;
        if ($fieldId > 0) {
            DB_query("DELETE FROM {$_TABLES['documents_values']} WHERE field_id={$fieldId}");
        }
    }
}

function DOCUMENTS_adminEndpointSelectIsUsed($selectId)
{
    global $_TABLES;

    $selectId = (int) $selectId;
    if ($selectId <= 0) {
        return false;
    }

    $select = DB_fetchArray(DB_query(
        "SELECT s_group, s_name FROM {$_TABLES['documents_selects']} WHERE sid={$selectId} LIMIT 1"
    ));
    if (!is_array($select) || !isset($select['s_group'])) {
        return false;
    }

    $groupId = (int) $select['s_group'];
    $safeName = DB_escapeString((string) $select['s_name']);
    $sql = "SELECT v.vid FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE f.sel_id={$groupId} AND v.v_value='{$safeName}' LIMIT 1";

    return DB_numRows(DB_query($sql)) > 0;
}

$mode = isset($_REQUEST['mode']) ? (string) $_REQUEST['mode'] : '';
$operation = isset($_REQUEST['op']) ? (string) $_REQUEST['op'] : 'save';
$ok = false;
$message = 'Unsupported operation.';
$returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/index.php';

switch ($mode) {
    case 'save_cat':
        $categoryFields = array();
        if ($operation === 'delete' && !empty($_REQUEST['cid'])) {
            $categoryFields = DOCUMENTS_adminEndpointCategoryFields((int) $_REQUEST['cid']);
        }
        list($ok, $message) = DOCUMENTS_adminSaveCategory($_REQUEST);
        if ($ok && $operation === 'delete' && !empty($categoryFields)) {
            DOCUMENTS_adminEndpointCleanupFieldValues($categoryFields);
        }
        if (!$ok && !empty($_REQUEST['cid'])) {
            $returnUrl .= '?mode=edit_cat&cat=' . (int) $_REQUEST['cid'];
        }
        break;

    case 'save_group':
        list($ok, $message) = DOCUMENTS_adminSaveGroup($_REQUEST);
        $returnUrl .= '?mode=list_groups';
        if (!$ok && !empty($_REQUEST['gid'])) {
            $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
                . '/index.php?mode=edit_group&group=' . (int) $_REQUEST['gid'];
        }
        break;

    case 'save_select':
        if ($operation === 'delete'
            && !empty($_REQUEST['sid'])
            && DOCUMENTS_adminEndpointSelectIsUsed((int) $_REQUEST['sid'])) {
            $ok = false;
            $message = 'This selection value is still used by one or more documents.';
        } else {
            list($ok, $message) = DOCUMENTS_adminSaveSelect($_REQUEST);
        }
        $returnUrl .= '?mode=list_selects';
        if (!empty($_REQUEST['s_group'])) {
            $returnUrl .= '&group=' . (int) $_REQUEST['s_group'];
        }
        if (!$ok && !empty($_REQUEST['sid'])) {
            $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
                . '/index.php?mode=edit_select&select=' . (int) $_REQUEST['sid'];
        }
        break;
}

$message = DOCUMENTS_adminMessage($message);
$separator = (strpos($returnUrl, '?') === false) ? '?' : '&';
$returnUrl .= $separator . 'msg=' . rawurlencode((string) $message);

echo COM_refresh($returnUrl);
exit;
