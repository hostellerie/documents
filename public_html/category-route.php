<?php

/* Direct clean-URL category controller. Geeklog 2.1.1-2.2.2 / PHP 5.6+. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';

$_REQUEST['mode'] = 'view';
$_REQUEST['cat'] = isset($_GET['cat']) ? (string) $_GET['cat'] : '';
unset($_REQUEST['doc']);

require __DIR__ . '/category-list.php';
