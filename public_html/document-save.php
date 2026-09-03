<?php

/* Secure save dispatcher for Documents 1.2.0. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'security.php';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'integrity.php';
require_once $pluginPath . 'interoperability.php';
require_once $pluginPath . 'indexability.php';
require_once $pluginPath . 'document_images.php';
require_once $pluginPath . 'document_mutations.php';
require_once $pluginPath . 'maps_adapter.php';
require_once $pluginPath . 'document_delete.php';

/* Geeklog's PLG_invokeService() only calls a service function that is already
 * loaded. On some request paths Maps' functions.inc has not been loaded yet,
 * even though the plugin is active. Load it explicitly before a marker save so
 * service_marker_save_maps() and plugin_wsEnabled_maps() are available. */
if (DOCUMENTS_hasMaps() && !function_exists('service_marker_save_maps')) {
    $mapsFunctions = $_CONF['path'] . 'plugins/maps/functions.inc';
    if (is_file($mapsFunctions)) {
        require_once $mapsFunctions;
    }
}

$categoryId = isset($_REQUEST['cid']) ? (int) $_REQUEST['cid'] : 0;
$operation = isset($_REQUEST['op']) ? (string) $_REQUEST['op'] : 'save';
$documentId = isset($_REQUEST['doc_url']) ? trim((string) $_REQUEST['doc_url']) : '';
$mapsCategory = false;

if ($operation !== 'delete') {
    if ($categoryId <= 0) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    $hasMarkerCategory = DOCUMENTS_mapsCategoryHasMarker($categoryId);
    $mapsCategory = DOCUMENTS_mapsCategorySupported($categoryId);
    if ($hasMarkerCategory && !$mapsCategory) {
        COM_errorLog('DOCUMENTS: marker category requires the Maps service contract.');
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    if (!$mapsCategory && !DOCUMENTS_documentMutationIsStandardCategory($categoryId)) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
}

if (!SEC_checkToken()) {
    if (function_exists('http_response_code')) {
        http_response_code(403);
    } else {
        header('HTTP/1.1 403 Forbidden');
    }
    exit;
}

if ($operation === 'delete') {
    if ($documentId === '' || !SEC_hasRights('documents.admin')) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    list($deleteOk, $deleteMessage) = DOCUMENTS_deleteDocumentSecure($documentId);
    if (!$deleteOk) {
        $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/index.php?mode=edit&doc_url=' . rawurlencode($documentId)
            . '&cat=' . (int) DOCUMENTS_documentMutationDocumentCategoryId($documentId)
            . '&msg=' . rawurlencode($deleteMessage);
        echo COM_refresh($returnUrl);
        exit;
    }

    echo COM_refresh(
        rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
        . '/index.php?msg=' . rawurlencode($deleteMessage)
    );
    exit;
}

$isCreation = $documentId === '';
if ($isCreation && COM_isAnonUser()) {
    echo COM_refresh($_CONF['site_url'] . '/users.php?mode=login');
    exit;
}

$category = DOCUMENTS_documentMutationCategory($categoryId);
if (empty($category) || DOCUMENTS_documentMutationCategoryAccess($category) < 2) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}
if ((int) $category['submitable'] !== 1 && !SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$existing = $isCreation ? array() : DOCUMENTS_documentMutationExisting($documentId);
if (!$isCreation
    && (empty($existing)
        || DOCUMENTS_documentMutationDocumentCategoryId($documentId) !== $categoryId
        || !DOCUMENTS_canEditDocument($existing))) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$wasPublic = !$isCreation && DOCUMENTS_isPubliclyIndexable($documentId);

if ($isCreation && !SEC_hasRights('documents.admin')) {
    $defaults = array();
    SEC_setDefaultPermissions($defaults, $_DOCUMENTS_CONF['default_permissions']);
    $defaultGroup = (int) DB_getItem($_TABLES['groups'], 'grp_id', "grp_name='Documents Admin'");
    DOCUMENTS_lockSecurityFields(
        $_REQUEST,
        isset($_USER['uid']) ? (int) $_USER['uid'] : 1,
        $defaultGroup > 0 ? $defaultGroup : 1,
        $defaults
    );
}

if ($mapsCategory) {
    list($ok, $message, $savedId, $categorySlug, $details) = DOCUMENTS_saveMapsDocument($_REQUEST);
} else {
    list($ok, $message, $savedId, $categorySlug, $details) = DOCUMENTS_saveStandardDocument($_REQUEST);
}

if (!$ok) {
    $missing = isset($details) && is_array($details) ? $details : array();
    if (!empty($missing)) {
        $message .= ' ' . implode(', ', $missing);
    }
    if ($isCreation) {
        $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/index.php?mode=new&cat=' . rawurlencode((string) $category['cat_url']);
    } else {
        $returnUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/index.php?mode=edit&doc_url=' . rawurlencode($documentId)
            . '&cat=' . $categoryId;
    }
    echo COM_refresh($returnUrl . '&msg=' . rawurlencode($message));
    exit;
}

$newStatus = isset($details['status']) ? (int) $details['status'] : DOCUMENTS_STATUS_INACTIVE;
$isPublic = DOCUMENTS_isPubliclyIndexable($savedId);
DOCUMENTS_notifyPublicTransition($savedId, $wasPublic, $isPublic);
if ($isCreation
    && $newStatus === DOCUMENTS_STATUS_SUBMISSION
    && !SEC_hasRights('documents.admin')
    && !SEC_hasRights('documents.publish')) {
    $subject = '[' . $_CONF['site_name'] . '] '
        . (isset($LANG_DOCUMENTS_1['doc_submission']) ? $LANG_DOCUMENTS_1['doc_submission'] : 'Document submission');
    $body = (isset($LANG_DOCUMENTS_1['doc_submission']) ? $LANG_DOCUMENTS_1['doc_submission'] : 'Document submission')
        . ' > ' . DOCUMENTS_interopCanonicalUrl($categorySlug, $savedId);
    COM_mail($_CONF['site_mail'], $subject, $body);
}

if ($newStatus === DOCUMENTS_STATUS_SUBMISSION) {
    $message = isset($LANG_DOCUMENTS_1['submission_recorded'])
        ? $LANG_DOCUMENTS_1['submission_recorded'] : 'Your document has been submitted.';
    echo COM_refresh(
        rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
        . '/index.php?msg=' . rawurlencode($message)
    );
    exit;
}

header('Location: ' . DOCUMENTS_interopCanonicalUrl($categorySlug, $savedId), true, 303);
exit;
