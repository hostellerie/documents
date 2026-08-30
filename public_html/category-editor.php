<?php

/* Internal dispatcher for the Documents 1.2.0 category editor. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

if (!SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'admin_styles.php';
require_once $pluginPath . 'admin_category_editor.php';

/*
 * Geeklog 2.1.1 and 2.2.x expose CSS registration through the scripts/resource
 * object, but their surrounding page-generation code differs.  Keep that
 * compatibility logic in one helper and register the stylesheet before
 * COM_createHTMLDocument() builds the page header.
 */
DOCUMENTS_loadAdminStyles();

$categoryId = 0;
if (isset($_GET['cid'])) {
    $categoryId = (int) $_GET['cid'];
} elseif (isset($_GET['cat'])) {
    $categoryId = (int) $_GET['cat'];
}

COM_output(DOCUMENTS_renderCategoryEditor($categoryId));
exit;
