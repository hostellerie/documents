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

$categoryId = isset($_REQUEST['cid']) ? (int) $_REQUEST['cid'] : 0;
$operation = isset($_REQUEST['op']) ? (string) $_REQUEST['op'] : 'save';

/* Deletion and specialized categories still use the mature legacy path.
 * Delegate before SEC_checkToken() so the one-time token is consumed only once. */
if ($operation === 'delete'
    || $categoryId <= 0
    || !DOCUMENTS_documentMutationIsStandardCategory($categoryId)) {
    require __DIR__ . '/index.php';
    exit;
}

if (!SEC_checkToken()) {
    if (function_exists('http_response_code')) {
        http_response_code(403);
    } else {
        header('HTTP/1.1 403 Forbidden');
    }
    exit;
}

$documentId = isset($_REQUEST['doc_url']) ? trim((string) $_REQUEST['doc_url']) : '';
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
if (!$isCreation) {
    if (empty($existing)
        || DOCUMENTS_documentMutationDocumentCategoryId($documentId) !== $categoryId
        || !DOCUMENTS_canEditDocument($existing)) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
}

/* Never trust ownership/permission fields from a normal contributor. */
if ($isCreation && !SEC_hasRights('documents.admin')) {
    $defaults = array();
    SEC_setDefaultPermissions($defaults, $_DOCUMENTS_CONF['default_permissions']);
    $defaultGroup = (int) DB_getItem(
        $_TABLES['groups'],
        'grp_id',
        "grp_name='Documents Admin'"
    );
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

$wasPublic = !$isCreation && DOCUMENTS_isPubliclyIndexable($documentId);
if ($wasPublic) {
    $oldUrl = DOCUMENTS_interopResolveStoredUrl($documentId);
    if ($oldUrl !== '') {
        DOCUMENTS_interopRememberUrl($documentId, $oldUrl);
    }
}

list($ok, $message, $savedId, $categorySlug, $details) = DOCUMENTS_saveStandardDocument($_REQUEST);

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
