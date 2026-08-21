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

// Category/field/select administration is private to Documents Admin. Keep
// this guard in front of the legacy controller so direct URLs cannot expose or
// mutate the plugin schema when a local handler misses a permission check.
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

if ($documentsMode === 'save_cat') {
    $_REQUEST['cat_url'] = DOCUMENTS_normalizeRouteSlug(
        DOCUMENTS_requestValue($_REQUEST, 'cat_url', '')
    );
    $_POST['cat_url'] = $_REQUEST['cat_url'];
}

// Enforce document permissions again at write time. Opening an edit form is
// not sufficient authorization for a later POST: the target document and its
// current permissions are reloaded before any value can be changed.
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

        $documentsSubmitable = DB_getItem(
            $_TABLES['documents_cat'],
            'submitable',
            'cid=' . $documentsCid
        );
        if ($documentsSubmitable === ''
            || ((int) $documentsSubmitable !== 1 && !SEC_hasRights('documents.admin'))) {
            echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit;
        }
    }
}

// Prepare a collision-safe URL before the legacy save controller starts a new
// document. The controller keeps the historical numeric-prefix format, but if
// DOC_URL is already defined it reuses this validated candidate.
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
