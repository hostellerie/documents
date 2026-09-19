<?php

/* Secure field mutation endpoint for Documents 1.2.0 default admin form. */

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
if (!function_exists('DOCUMENTS_requestPermissions')) {
    require_once $pluginPath . 'include_compat.php';
}
require_once $pluginPath . 'integrity.php';
require_once $pluginPath . 'admin_mutations.php';
require_once $pluginPath . 'field_mutations.php';
require_once $pluginPath . 'admin_messages.php';

list($ok, $message, $categoryId) = DOCUMENTS_adminSaveField($_REQUEST);
$message = DOCUMENTS_adminMessage($message);

$returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/index.php?mode=list_fields';
if ($categoryId > 0) {
    $returnUrl .= '&cat=' . (int) $categoryId;
}

if (!$ok && !empty($_REQUEST['fid'])) {
    $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
        . '/index.php?mode=edit_field&field=' . (int) $_REQUEST['fid'];
}

$returnUrl .= '&msg=' . rawurlencode((string) $message);
echo COM_refresh($returnUrl);
exit;
