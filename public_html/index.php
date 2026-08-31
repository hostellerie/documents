<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | index.php                                                                 |
// |                                                                           |
// | Small public compatibility router.                                        |
// +---------------------------------------------------------------------------+

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
$requestedMode = isset($_REQUEST['mode']) && !is_array($_REQUEST['mode'])
    ? trim((string) $_REQUEST['mode']) : '';

/* Tell runtime.php not to register its historical shutdown observer for the
 * modern secure save controller. document-save.php owns that lifecycle. */
if ($requestedMode === 'save' && empty($GLOBALS['DOCUMENTS_LEGACY_SAVE_DISPATCH'])) {
    $GLOBALS['DOCUMENTS_SECURE_SAVE_CONTROLLER'] = true;
}

require_once $pluginPath . 'rewrite.php';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';

DOCUMENTS_writeHtaccess(false);
DOCUMENTS_ensureImageDirectory();
DOCUMENTS_initializeRequestDefaults($_REQUEST);

$mode = (string) DOCUMENTS_requestValue($_REQUEST, 'mode', $requestedMode);

if ($mode === '') {
    require __DIR__ . '/home.php';
    exit;
}

if ($mode === 'view') {
    $category = trim((string) DOCUMENTS_requestValue($_REQUEST, 'cat', ''));
    $document = trim((string) DOCUMENTS_requestValue($_REQUEST, 'doc', ''));

    if ($category === '') {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    $requestPath = isset($_SERVER['REQUEST_URI'])
        ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)
        : '';
    if (is_string($requestPath) && basename($requestPath) === 'index.php') {
        $canonical = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/' . rawurlencode($category);
        if ($document !== '') {
            $canonical .= '/' . rawurlencode($document);
        }
        header('Location: ' . $canonical, true, 301);
        exit;
    }

    $_GET['cat'] = $category;
    $_REQUEST['cat'] = $category;

    if ($document !== '') {
        $_GET['doc'] = $document;
        $_REQUEST['doc'] = $document;
        require __DIR__ . '/document.php';
    } else {
        unset($_GET['doc'], $_REQUEST['doc']);
        require __DIR__ . '/category.php';
    }
    exit;
}

if ($mode === 'new' || $mode === 'edit') {
    if ($mode === 'new' && COM_isAnonUser()) {
        echo COM_refresh($_CONF['site_url'] . '/users.php?mode=login');
        exit;
    }

    /* Public contribution forms must use public presentation assets, not an
     * administration stylesheet. Keep the old renderer temporarily, but give
     * it the same page-level CSS/SEO preparation as the other public pages. */
    require_once $pluginPath . 'presentation.php';
    DOCUMENTS_preparePublicPresentation();

    /* Historical option rows sometimes have an empty display label. Preserve
     * the internal value and repair only missing labels so existing categories
     * immediately render useful <option> text. */
    if (isset($_TABLES['documents_selects'])) {
        DB_query(
            "UPDATE {$_TABLES['documents_selects']} SET s_value=s_name "
            . "WHERE (s_value='' OR s_value IS NULL) AND s_name<>''"
        );
    }

    require_once $pluginPath . 'include_html.php';
    exit;
}

if ($mode === 'save') {
    if (empty($GLOBALS['DOCUMENTS_LEGACY_SAVE_DISPATCH'])) {
        require __DIR__ . '/document-save.php';
    } else {
        require_once $pluginPath . 'include_html.php';
    }
    exit;
}

$adminModes = array(
    'edit_cat', 'save_cat',
    'edit_field', 'save_field', 'list_fields',
    'edit_group', 'save_group', 'list_groups',
    'edit_select', 'save_select', 'list_selects'
);

if (in_array($mode, $adminModes, true)) {
    if (!SEC_hasRights('documents.admin')) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!SEC_checkToken()) {
            if (function_exists('http_response_code')) {
                http_response_code(403);
            } else {
                header('HTTP/1.1 403 Forbidden');
            }
            exit;
        }

        require_once $pluginPath . 'admin_dispatch.php';
        list($ok, $returnUrl) = DOCUMENTS_adminDispatchMutation($mode, $_REQUEST);
        echo COM_refresh($returnUrl);
        exit;
    }

    $adminUrl = rtrim((string) $_CONF['site_admin_url'], '/')
        . '/plugins/documents/index.php';
    $query = $_GET;
    $query['mode'] = $mode;
    header('Location: ' . $adminUrl . '?' . http_build_query($query, '', '&'), true, 302);
    exit;
}

echo COM_refresh($_CONF['site_url'] . '/404.php');
exit;
