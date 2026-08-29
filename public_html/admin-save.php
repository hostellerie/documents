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

$mode = isset($_REQUEST['mode']) ? (string) $_REQUEST['mode'] : '';
$ok = false;
$message = 'Unsupported operation.';
$returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/index.php';

switch ($mode) {
    case 'save_cat':
        list($ok, $message) = DOCUMENTS_adminSaveCategory($_REQUEST);
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
        list($ok, $message) = DOCUMENTS_adminSaveSelect($_REQUEST);
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

$separator = (strpos($returnUrl, '?') === false) ? '?' : '&';
$returnUrl .= $separator . 'msg=' . rawurlencode((string) $message);

echo COM_refresh($returnUrl);
exit;
