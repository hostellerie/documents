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

// Normalize new/edited category routes before the existing validation and
// duplicate check run in include_html.php. Existing stored routes are left
// untouched so an upgrade never changes historical URLs automatically.
if ((string) DOCUMENTS_requestValue($_REQUEST, 'mode', '') === 'save_cat') {
    $_REQUEST['cat_url'] = DOCUMENTS_normalizeRouteSlug(
        DOCUMENTS_requestValue($_REQUEST, 'cat_url', '')
    );
    $_POST['cat_url'] = $_REQUEST['cat_url'];
}

$documentsMode = (string) DOCUMENTS_requestValue($_REQUEST, 'mode', '');
$documentsDocUrl = (string) DOCUMENTS_requestValue($_REQUEST, 'doc_url', '');
$documentsOperation = (string) DOCUMENTS_requestValue($_REQUEST, 'op', '');

// Capture current image references before an existing document is saved.
// The shutdown callback runs even when include_html.php ends the request with
// a redirect. It removes only files whose DB reference changed successfully.
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

// A document deletion already removes its document/value rows in the legacy
// save handler. Capture local resources first, then remove them only if the
// document row is actually gone when the request finishes.
if ($documentsMode === 'save' && $documentsDocUrl !== '' && $documentsOperation === 'delete') {
    $documentsImagesBeforeDelete = DOCUMENTS_documentImageReferences($documentsDocUrl);
    register_shutdown_function(
        'DOCUMENTS_cleanupDeletedDocumentImages',
        $documentsDocUrl,
        $documentsImagesBeforeDelete
    );
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
