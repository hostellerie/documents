<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | runtime.php                                                               |
// |                                                                           |
// | Shared runtime dependencies and filesystem helpers.                       |
// +---------------------------------------------------------------------------+

/* Runtime must stay side-effect free: it loads helpers only. Save lifecycle
 * events are emitted by the dedicated document-save.php controller. */
if (isset($_CONF['path'])) {
    $documentsRuntimeFiles = array(
        'security.php',
        'integrity.php',
        'navigation.php',
        'page_layout.php',
        'mediagallery_adapter.php',
        'interoperability.php',
        'indexability.php'
    );

    foreach ($documentsRuntimeFiles as $documentsRuntimeFile) {
        $documentsRuntimePath = $_CONF['path'] . 'plugins/documents/' . $documentsRuntimeFile;
        if (is_file($documentsRuntimePath)) {
            require_once $documentsRuntimePath;
        }
    }
}

function DOCUMENTS_ensureWritableDirectory($path, $label)
{
    $path = rtrim((string) $path, "/\\") . DIRECTORY_SEPARATOR;
    if ($path === DIRECTORY_SEPARATOR) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents runtime: ' . $label . ' path is empty.');
        }
        return false;
    }

    if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents runtime: unable to create ' . $label . ' directory ' . $path);
        }
        return false;
    }

    if (!is_writable($path)) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents runtime: ' . $label . ' directory is not writable ' . $path);
        }
        return false;
    }

    return true;
}

function DOCUMENTS_ensureImageDirectory()
{
    global $_DOCUMENTS_CONF;

    if (empty($_DOCUMENTS_CONF['path_images'])) {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('Documents runtime: path_images is not configured.');
        }
        return false;
    }

    return DOCUMENTS_ensureWritableDirectory($_DOCUMENTS_CONF['path_images'], 'image');
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

    return $path !== '' && DOCUMENTS_ensureWritableDirectory($path, 'preview');
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
