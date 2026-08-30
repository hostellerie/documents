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
require_once $pluginPath . 'admin_category_editor.php';

/*
 * This editor is served from the public Documents directory rather than from
 * admin/plugins/documents/. Load the admin stylesheet explicitly so the
 * category form does not fall back to the raw theme styling.
 *
 * The version query string also makes browser/proxy caches pick up UI changes
 * while testing or upgrading Documents 1.2.0.
 */
if (isset($_SCRIPTS) && is_object($_SCRIPTS) && method_exists($_SCRIPTS, 'setCSSFile')) {
    $documentsAdminCss = $_CONF['site_admin_url']
        . '/plugins/documents/documents.css?v=1.2.0';
    $_SCRIPTS->setCSSFile('documents_admin_css', $documentsAdminCss, true);
}

$categoryId = 0;
if (isset($_GET['cid'])) {
    $categoryId = (int) $_GET['cid'];
} elseif (isset($_GET['cat'])) {
    $categoryId = (int) $_GET['cat'];
}

COM_output(DOCUMENTS_renderCategoryEditor($categoryId));
exit;
