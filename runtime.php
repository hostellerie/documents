<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | runtime.php                                                               |
// |                                                                           |
// | Runtime environment, lifecycle and self-repair helpers.                   |
// +---------------------------------------------------------------------------+

if (isset($_CONF['path'])) {
    $documentsSecurityFile = $_CONF['path'] . 'plugins/documents/security.php';
    if (is_file($documentsSecurityFile)) {
        require_once $documentsSecurityFile;
    }

    $documentsPresentationFile = $_CONF['path'] . 'plugins/documents/presentation.php';
    if (is_file($documentsPresentationFile)) {
        require_once $documentsPresentationFile;
    }

    $documentsMediaGalleryFile = $_CONF['path'] . 'plugins/documents/mediagallery_adapter.php';
    if (is_file($documentsMediaGalleryFile)) {
        require_once $documentsMediaGalleryFile;
    }

    $documentsInteropFile = $_CONF['path'] . 'plugins/documents/interoperability.php';
    if (is_file($documentsInteropFile)) {
        require_once $documentsInteropFile;
    }

    $documentsIndexabilityFile = $_CONF['path'] . 'plugins/documents/indexability.php';
    if (is_file($documentsIndexabilityFile)) {
        require_once $documentsIndexabilityFile;
    }
}

function DOCUMENTS_runtimeDocumentSnapshot($id)
{
    global $_TABLES;

    $id = trim((string) $id);
    if ($id === '') {
        return array();
    }

    $safeId = DB_escapeString($id);
    $row = DB_fetchArray(DB_query(
        "SELECT doc_url, active, created, modified FROM {$_TABLES['documents_docs']} "
        . "WHERE doc_url='{$safeId}' LIMIT 1"
    ));

    return is_array($row) ? $row : array();
}

function DOCUMENTS_runtimeLifecycleAfterSave($requestedId, $operation, $before, $wasPublic)
{
    $id = trim((string) $requestedId);
    if ($id === '' && defined('DOC_URL')) {
        $id = (string) DOC_URL;
    }
    if ($id === '') {
        return;
    }

    $wasPublic = (bool) $wasPublic;

    if ($operation === 'delete') {
        $after = DOCUMENTS_runtimeDocumentSnapshot($id);
        if (empty($after)) {
            DOCUMENTS_notifyPublicTransition($id, $wasPublic, false);
        }
        return;
    }

    $after = DOCUMENTS_runtimeDocumentSnapshot($id);
    if (empty($after)) {
        return;
    }

    $previousStatus = is_array($before) && isset($before['active'])
        ? (int) $before['active'] : DOCUMENTS_STATUS_INACTIVE;
    $newStatus = isset($after['active']) ? (int) $after['active'] : DOCUMENTS_STATUS_INACTIVE;
    $beforeModified = is_array($before) && isset($before['modified']) ? (string) $before['modified'] : '';
    $afterModified = isset($after['modified']) ? (string) $after['modified'] : '';
    $createdNow = empty($before);
    $changed = $createdNow || $previousStatus !== $newStatus || $beforeModified !== $afterModified;

    if (!$changed) {
        return;
    }

    $isPublic = DOCUMENTS_isPubliclyIndexable($id);
    DOCUMENTS_notifyPublicTransition($id, $wasPublic, $isPublic);
}

function DOCUMENTS_runtimePrepareLifecycle()
{
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST'
        || !isset($_REQUEST['mode']) || $_REQUEST['mode'] !== 'save') {
        return;
    }

    $operation = isset($_REQUEST['op']) ? (string) $_REQUEST['op'] : 'save';
    $id = isset($_REQUEST['doc_url']) ? trim((string) $_REQUEST['doc_url']) : '';
    $before = ($id !== '') ? DOCUMENTS_runtimeDocumentSnapshot($id) : array();
    $wasPublic = ($id !== '') ? DOCUMENTS_isPubliclyIndexable($id) : false;

    if ($wasPublic) {
        $url = DOCUMENTS_interopResolveStoredUrl($id);
        if ($url !== '') {
            DOCUMENTS_interopRememberUrl($id, $url);
        }
    }

    register_shutdown_function(
        'DOCUMENTS_runtimeLifecycleAfterSave',
        $id,
        $operation,
        $before,
        $wasPublic
    );
}

DOCUMENTS_runtimePrepareLifecycle();

$documentsScript = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
$documentsMode = isset($_REQUEST['mode']) ? trim((string) $_REQUEST['mode']) : '';
if ($documentsMode === ''
    && $documentsScript !== ''
    && strpos($documentsScript, '/documents/index.php') !== false
    && strpos($documentsScript, '/admin/') === false
    && function_exists('DOCUMENTS_homeStatsBlock')) {
    $documentsStats = DOCUMENTS_homeStatsBlock();
    if ($documentsStats !== '') {
        $existingFooter = isset($_DOCUMENTS_CONF['documents_main_footer'])
            ? (string) $_DOCUMENTS_CONF['documents_main_footer'] : '';
        $_DOCUMENTS_CONF['documents_main_footer'] = $existingFooter . $documentsStats;
    }
}

function DOCUMENTS_ensureImageDirectory()
{
    global $_DOCUMENTS_CONF;

    if (!isset($_DOCUMENTS_CONF['path_images'])) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents runtime: path_images is not configured.');
        }
        return false;
    }

    $path = rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\") . DIRECTORY_SEPARATOR;
    if ($path === DIRECTORY_SEPARATOR) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents runtime: path_images is empty.');
        }
        return false;
    }

    if (!is_dir($path)) {
        if (!@mkdir($path, 0755, true) && !is_dir($path)) {
            if (function_exists('COM_errorLog')) {
                COM_errorLog('Documents runtime: unable to create image directory ' . $path);
            }
            return false;
        }
    }

    if (!is_writable($path)) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents runtime: image directory is not writable ' . $path);
        }
        return false;
    }

    return true;
}

function DOCUMENTS_previewDirectory()
{
    global $_DOCUMENTS_CONF;

    if (empty($_DOCUMENTS_CONF['path_images'])) {
        return '';
    }

    return rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\")
        . DIRECTORY_SEPARATOR . '_previews' . DIRECTORY_SEPARATOR;
}

function DOCUMENTS_ensurePreviewDirectory()
{
    $path = DOCUMENTS_previewDirectory();
    if ($path === '') {
        return false;
    }

    if (!is_dir($path)) {
        if (!@mkdir($path, 0755, true) && !is_dir($path)) {
            if (function_exists('COM_errorLog')) {
                COM_errorLog('Documents runtime: unable to create preview directory ' . $path);
            }
            return false;
        }
    }

    if (!is_writable($path)) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents runtime: preview directory is not writable ' . $path);
        }
        return false;
    }

    return true;
}

function DOCUMENTS_removeImagePreviews($filename)
{
    $filename = basename((string) $filename);
    if ($filename === '') {
        return 0;
    }

    $directory = DOCUMENTS_previewDirectory();
    if ($directory === '' || !is_dir($directory)) {
        return 0;
    }

    $prefix = sha1($filename) . '-';
    $items = @scandir($directory);
    if (!is_array($items)) {
        return 0;
    }

    $removed = 0;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || strpos($item, $prefix) !== 0) {
            continue;
        }

        $path = $directory . basename($item);
        if (is_file($path) && @unlink($path)) {
            $removed++;
        }
    }

    return $removed;
}
