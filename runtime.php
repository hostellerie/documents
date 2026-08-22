<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.9                                                    |
// +---------------------------------------------------------------------------+
// | runtime.php                                                               |
// |                                                                           |
// | Runtime environment checks and self-repair helpers.                       |
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
