<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | index.php                                                                 |
// |                                                                           |
// | Public plugin page.                                                       |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// +---------------------------------------------------------------------------+

/**
 * @package Documents
 */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

require_once $_CONF['path'] . 'plugins/documents/rewrite.php';
DOCUMENTS_writeHtaccess(false);

require_once $_CONF['path'] . 'plugins/documents/runtime.php';
DOCUMENTS_ensureImageDirectory();

require_once $_CONF['path'] . 'plugins/documents/include_compat.php';
require_once $_CONF['path'] . 'plugins/documents/integrity.php';
DOCUMENTS_initializeRequestDefaults($_REQUEST);

$documentsMode = (string) DOCUMENTS_requestValue($_REQUEST, 'mode', '');
$documentsDocUrl = (string) DOCUMENTS_requestValue($_REQUEST, 'doc_url', '');
$documentsOperation = (string) DOCUMENTS_requestValue($_REQUEST, 'op', '');

$documentsWriteModes = array('save', 'save_cat', 'save_field', 'save_group', 'save_select');
if (in_array($documentsMode, $documentsWriteModes, true)) {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
}

$documentsAdminModes = array(
    'edit_cat', 'save_cat',
    'edit_field', 'save_field', 'list_fields',
    'edit_group', 'save_group', 'list_groups',
    'edit_select', 'save_select', 'list_selects'
);
if (in_array($documentsMode, $documentsAdminModes, true)
    && !SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$documentsAdminSaveModes = array('save_cat', 'save_field', 'save_group', 'save_select');
if (in_array($documentsMode, $documentsAdminSaveModes, true) && !SEC_checkToken()) {
    http_response_code(403);
    exit;
}

if ($documentsMode === 'view' || $documentsMode === 'new') {
    $documentsCategorySlug = (string) DOCUMENTS_requestValue($_REQUEST, 'cat', '');
    if ($documentsCategorySlug !== '') {
        $documentsCategorySlugSql = DB_escapeString($documentsCategorySlug);
        $documentsCategoryResult = DB_query(
            "SELECT cid, submitable, owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
            . "FROM {$_TABLES['documents_cat']} WHERE cat_url='{$documentsCategorySlugSql}' LIMIT 1"
        );
        $documentsCategory = DB_fetchArray($documentsCategoryResult);

        if (!is_array($documentsCategory) || empty($documentsCategory['cid'])) {
            echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit;
        }

        $documentsCategoryAccess = SEC_hasAccess(
            (int) $documentsCategory['owner_id'],
            (int) $documentsCategory['group_id'],
            (int) $documentsCategory['perm_owner'],
            (int) $documentsCategory['perm_group'],
            (int) $documentsCategory['perm_members'],
            (int) $documentsCategory['perm_anon']
        );
        if ($documentsCategoryAccess < 2) {
            echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit;
        }

        if ($documentsMode === 'new') {
            if (COM_isAnonUser()) {
                echo COM_refresh($_CONF['site_url'] . '/users.php?mode=login');
                exit;
            }
            if ((int) $documentsCategory['submitable'] !== 1
                && !SEC_hasRights('documents.admin')) {
                echo COM_refresh($_CONF['site_url'] . '/404.php');
                exit;
            }
        }
    }
}

if ($documentsMode === 'view') {
    $documentsViewDoc = (string) DOCUMENTS_requestValue($_REQUEST, 'doc', '');
    if ($documentsViewDoc !== '') {
        $documentsViewDocSql = DB_escapeString($documentsViewDoc);
        $documentsViewResult = DB_query(
            "SELECT active, owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
            . "FROM {$_TABLES['documents_docs']} WHERE doc_url='{$documentsViewDocSql}' LIMIT 1"
        );
        $documentsViewRow = DB_fetchArray($documentsViewResult);

        if (!DOCUMENTS_canViewDocument($documentsViewRow, 2)) {
            echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit;
        }
    }
}

if ($documentsMode === 'save_cat') {
    $_REQUEST['cat_url'] = DOCUMENTS_normalizeRouteSlug(
        DOCUMENTS_requestValue($_REQUEST, 'cat_url', '')
    );
    $_POST['cat_url'] = $_REQUEST['cat_url'];
}

if ($documentsMode === 'save') {
    if ($documentsDocUrl !== '') {
        $documentsDocUrlSql = DB_escapeString($documentsDocUrl);
        $documentsResult = DB_query(
            "SELECT owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
            . "FROM {$_TABLES['documents_docs']} WHERE doc_url='{$documentsDocUrlSql}' LIMIT 1"
        );
        $documentsRow = DB_fetchArray($documentsResult);

        if (!is_array($documentsRow) || !isset($documentsRow['owner_id'])) {
            echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit;
        }

        if ($documentsOperation === 'delete') {
            if (!SEC_hasRights('documents.admin')) {
                echo COM_refresh($_CONF['site_url'] . '/404.php');
                exit;
            }
        } else {
            $documentsAccess = SEC_hasAccess(
                (int) $documentsRow['owner_id'],
                (int) $documentsRow['group_id'],
                (int) $documentsRow['perm_owner'],
                (int) $documentsRow['perm_group'],
                (int) $documentsRow['perm_members'],
                (int) $documentsRow['perm_anon']
            );
            if ($documentsAccess < 3) {
                echo COM_refresh($_CONF['site_url'] . '/404.php');
                exit;
            }
        }
    } else {
        if (COM_isAnonUser()) {
            echo COM_refresh($_CONF['site_url'] . '/users.php?mode=login');
            exit;
        }

        $documentsCid = DOCUMENTS_requestInt($_REQUEST, 'cid', 0);
        if ($documentsCid <= 0) {
            echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit;
        }

        $documentsCategoryResult = DB_query(
            "SELECT submitable, owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
            . "FROM {$_TABLES['documents_cat']} WHERE cid={$documentsCid} LIMIT 1"
        );
        $documentsCategory = DB_fetchArray($documentsCategoryResult);
        if (!is_array($documentsCategory) || !isset($documentsCategory['submitable'])) {
            echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit;
        }

        $documentsCategoryAccess = SEC_hasAccess(
            (int) $documentsCategory['owner_id'],
            (int) $documentsCategory['group_id'],
            (int) $documentsCategory['perm_owner'],
            (int) $documentsCategory['perm_group'],
            (int) $documentsCategory['perm_members'],
            (int) $documentsCategory['perm_anon']
        );
        if ($documentsCategoryAccess < 2
            || ((int) $documentsCategory['submitable'] !== 1
                && !SEC_hasRights('documents.admin'))) {
            echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit;
        }
    }
}

if ($documentsMode === 'save' && $documentsDocUrl === '' && $documentsOperation !== 'delete') {
    $documentsCid = DOCUMENTS_requestInt($_REQUEST, 'cid', 0);
    if ($documentsCid > 0) {
        $firstField = DB_query(
            "SELECT var_name FROM {$_TABLES['documents_fields']} "
            . "WHERE cat_id={$documentsCid} ORDER BY f_order ASC, fid ASC LIMIT 1"
        );
        $firstFieldRow = DB_fetchArray($firstField);
        if (is_array($firstFieldRow) && !empty($firstFieldRow['var_name'])) {
            $titleValue = DOCUMENTS_requestValue($_REQUEST, $firstFieldRow['var_name'], '');
            if (is_array($titleValue)) {
                $titleValue = '';
            }
            if (!defined('DOC_URL')) {
                define('DOC_URL', DOCUMENTS_uniqueDocumentUrl((string) $titleValue));
            }
        }
    }
}

if ($documentsMode === 'save' && $documentsDocUrl !== '' && $documentsOperation !== 'delete') {
    $documentsImagesBeforeSave = DOCUMENTS_documentImageReferences($documentsDocUrl);
    if (!empty($documentsImagesBeforeSave)) {
        register_shutdown_function(
            'DOCUMENTS_cleanupReplacedImages',
            $documentsImagesBeforeSave,
            $documentsDocUrl
        );
    }
}

if ($documentsMode === 'save' && $documentsDocUrl !== '' && $documentsOperation === 'delete') {
    $documentsImagesBeforeDelete = DOCUMENTS_documentImageReferences($documentsDocUrl);
    register_shutdown_function(
        'DOCUMENTS_cleanupDeletedDocumentImages',
        $documentsDocUrl,
        $documentsImagesBeforeDelete
    );
}

if ($documentsMode === 'save_field' && $documentsOperation === 'delete') {
    $documentsFieldId = DOCUMENTS_requestInt($_REQUEST, 'fid', 0);
    if ($documentsFieldId > 0) {
        $documentsFieldImages = DOCUMENTS_fieldImageReferences($documentsFieldId);
        if (!empty($documentsFieldImages)) {
            register_shutdown_function(
                'DOCUMENTS_cleanupDeletedFieldImages',
                $documentsFieldId,
                $documentsFieldImages
            );
        }
    }
}

$requestPath = isset($_SERVER['REQUEST_URI'])
    ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)
    : '';
if (is_string($requestPath) && preg_match('#/index\.php$#', $requestPath)) {
    $mode = (string) DOCUMENTS_requestValue($_REQUEST, 'mode', '');
    $catUrl = (string) DOCUMENTS_requestValue($_REQUEST, 'cat', '');
    $docUrl = (string) DOCUMENTS_requestValue($_REQUEST, 'doc', '');

    if ($mode === 'view' && $catUrl !== '') {
        $canonical = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/' . rawurlencode($catUrl);
        if ($docUrl !== '') {
            $canonical .= '/' . rawurlencode($docUrl);
        }
        header('Location: ' . $canonical, true, 301);
        exit;
    }
}

require_once $_CONF['path'] . 'plugins/documents/include_html.php';