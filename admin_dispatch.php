<?php

/* Dispatch legacy Documents admin save modes to the 1.2.0 mutation layer. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'admin_dispatch.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_adminPrepareCategoryRequest($request)
{
    global $_TABLES;

    if (!is_array($request)) {
        return array();
    }

    $cid = isset($request['cid']) ? (int) $request['cid'] : 0;
    if ($cid > 0 && !array_key_exists('metadescription', $request)) {
        $request['metadescription'] = DB_getItem(
            $_TABLES['documents_cat'],
            'metadescription',
            'cid=' . $cid
        );
    }

    return $request;
}

function DOCUMENTS_adminDispatchMutation($mode, $request)
{
    global $_CONF, $_DOCUMENTS_CONF;

    $mode = (string) $mode;
    $request = is_array($request) ? $request : array();
    $pluginPath = $_CONF['path'] . 'plugins/documents/';

    require_once $pluginPath . 'admin_mutations.php';
    require_once $pluginPath . 'admin_messages.php';

    $ok = false;
    $message = 'Unsupported operation.';
    $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/index.php';

    switch ($mode) {
        case 'save_cat':
            $request = DOCUMENTS_adminPrepareCategoryRequest($request);
            list($ok, $message) = DOCUMENTS_adminSaveCategory($request);
            if (!$ok && !empty($request['cid'])) {
                $returnUrl .= '?mode=edit_cat&cat=' . (int) $request['cid'];
            }
            break;

        case 'save_field':
            require_once $pluginPath . 'integrity.php';
            require_once $pluginPath . 'field_mutations.php';
            list($ok, $message, $categoryId) = DOCUMENTS_adminSaveField($request);
            $returnUrl .= '?mode=list_fields';
            if ($categoryId > 0) {
                $returnUrl .= '&cat=' . (int) $categoryId;
            }
            if (!$ok && !empty($request['fid'])) {
                $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
                    . '/index.php?mode=edit_field&field=' . (int) $request['fid'];
            }
            break;

        case 'save_group':
            list($ok, $message) = DOCUMENTS_adminSaveGroup($request);
            $returnUrl .= '?mode=list_groups';
            if (!$ok && !empty($request['gid'])) {
                $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
                    . '/index.php?mode=edit_group&group=' . (int) $request['gid'];
            }
            break;

        case 'save_select':
            list($ok, $message) = DOCUMENTS_adminSaveSelect($request);
            $returnUrl .= '?mode=list_selects';
            if (!empty($request['s_group'])) {
                $returnUrl .= '&group=' . (int) $request['s_group'];
            }
            if (!$ok && !empty($request['sid'])) {
                $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
                    . '/index.php?mode=edit_select&select=' . (int) $request['sid'];
            }
            break;
    }

    $message = DOCUMENTS_adminMessage($message);
    $separator = (strpos($returnUrl, '?') === false) ? '?' : '&';
    $returnUrl .= $separator . 'msg=' . rawurlencode($message);

    return array($ok, $returnUrl);
}
