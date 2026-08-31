<?php

/* Progressive secure save dispatcher for Documents 1.2.0. */

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

function DOCUMENTS_documentSaveTrace($message)
{
    if (function_exists('COM_errorLog')) {
        COM_errorLog('DOCUMENTS SAVE TRACE: ' . (string) $message);
    }
}

$categoryId = isset($_REQUEST['cid']) ? (int) $_REQUEST['cid'] : 0;
$operation = isset($_REQUEST['op']) ? (string) $_REQUEST['op'] : 'save';
$documentId = isset($_REQUEST['doc_url']) ? trim((string) $_REQUEST['doc_url']) : '';

DOCUMENTS_documentSaveTrace('document controller begin cid=' . $categoryId . ' op=' . $operation);

/* Decide ownership before consuming Geeklog's one-time token. Non-marker
 * integrations without a service contract may still use the compatibility
 * controller. Any category containing a marker is forbidden from doing so. */
if ($operation !== 'delete') {
    DOCUMENTS_documentSaveTrace('document classify category begin');
    $standardCategory = $categoryId > 0 && DOCUMENTS_documentMutationIsStandardCategory($categoryId);
    $hasMarkerCategory = $categoryId > 0 && DOCUMENTS_mapsCategoryHasMarker($categoryId);
    $mapsCategory = $categoryId > 0 && DOCUMENTS_mapsCategorySupported($categoryId);
    DOCUMENTS_documentSaveTrace(
        'document classify category end standard=' . ($standardCategory ? '1' : '0')
        . ' marker=' . ($hasMarkerCategory ? '1' : '0')
        . ' maps=' . ($mapsCategory ? '1' : '0')
    );

    if ($hasMarkerCategory && !$mapsCategory) {
        COM_errorLog('DOCUMENTS: marker category refused legacy save fallback; Maps service ownership is mandatory.');
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    if ($categoryId <= 0 || (!$standardCategory && !$mapsCategory)) {
        DOCUMENTS_documentSaveTrace('document legacy save dispatch begin');
        $GLOBALS['DOCUMENTS_LEGACY_SAVE_DISPATCH'] = true;
        require __DIR__ . '/index.php';
        exit;
    }
} else {
    $standardCategory = false;
    $hasMarkerCategory = false;
    $mapsCategory = false;
}

DOCUMENTS_documentSaveTrace('document token check begin');
if (!SEC_checkToken()) {
    if (function_exists('http_response_code')) {
        http_response_code(403);
    } else {
        header('HTTP/1.1 403 Forbidden');
    }
    exit;
}
DOCUMENTS_documentSaveTrace('document token check end');

if ($operation === 'delete') {
    if ($documentId === '' || !SEC_hasRights('documents.admin')) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    DOCUMENTS_documentSaveTrace('document delete mutation begin');
    list($deleteOk, $deleteMessage) = DOCUMENTS_deleteDocumentSecure($documentId);
    DOCUMENTS_documentSaveTrace('document delete mutation end ok=' . ($deleteOk ? '1' : '0'));
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

DOCUMENTS_documentSaveTrace('document category load begin');
$category = DOCUMENTS_documentMutationCategory($categoryId);
DOCUMENTS_documentSaveTrace('document category load end');
if (empty($category) || DOCUMENTS_documentMutationCategoryAccess($category) < 2) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}
if ((int) $category['submitable'] !== 1 && !SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

DOCUMENTS_documentSaveTrace('document existing load begin');
$existing = $isCreation ? array() : DOCUMENTS_documentMutationExisting($documentId);
DOCUMENTS_documentSaveTrace('document existing load end');
if (!$isCreation) {
    if (empty($existing)
        || DOCUMENTS_documentMutationDocumentCategoryId($documentId) !== $categoryId
        || !DOCUMENTS_canEditDocument($existing)) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
}

if ($isCreation && !SEC_hasRights('documents.admin')) {
    $defaults = array();
    SEC_setDefaultPermissions($defaults, $_DOCUMENTS_CONF['default_permissions']);
    $defaultGroup = (int) DB_getItem($_TABLES['groups'], 'grp_id', "grp_name='Documents Admin'");
    if ($defaultGroup <= 0) {
        $defaultGroup = 1;
    }
    DOCUMENTS_lockSecurityFields(
        $_REQUEST,
        isset($_USER['uid']) ? (int) $_USER['uid'] : 1,
        $defaultGroup,
        $defaults
    );
}

if ($mapsCategory) {
    DOCUMENTS_documentSaveTrace('document maps mutation begin');
    list($ok, $message, $savedId, $categorySlug, $details) = DOCUMENTS_saveMapsDocument($_REQUEST);
    DOCUMENTS_documentSaveTrace('document maps mutation end ok=' . ($ok ? '1' : '0'));
} else {
    DOCUMENTS_documentSaveTrace('document standard mutation begin');
    list($ok, $message, $savedId, $categorySlug, $details) = DOCUMENTS_saveStandardDocument($_REQUEST);
    DOCUMENTS_documentSaveTrace('document standard mutation end ok=' . ($ok ? '1' : '0'));
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
    $returnUrl .= '&msg=' . rawurlencode($message);
    echo COM_refresh($returnUrl);
    exit;
}

/* Saving a document must finish independently from third-party listeners.
 * PLG_itemSaved/PLG_itemDeleted remain available through the interoperability
 * service, but they are intentionally not invoked synchronously here. */
$newStatus = isset($details['status']) ? (int) $details['status'] : DOCUMENTS_STATUS_INACTIVE;

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

DOCUMENTS_documentSaveTrace('document controller redirect');
header('Location: ' . DOCUMENTS_interopCanonicalUrl($categorySlug, $savedId), true, 303);
exit;
