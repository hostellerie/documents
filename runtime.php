<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | runtime.php                                                               |
// |                                                                           |
// | Runtime environment checks and self-repair helpers.                       |
// +---------------------------------------------------------------------------+

/**
 * Ensure the configured Documents image upload directory exists and is usable.
 *
 * @return bool
 */
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

/**
 * Return the persistent preview cache directory.
 *
 * The cache lives below the Documents image directory so it remains specific
 * to the current Geeklog site and is kept away from the original image files.
 *
 * @return string
 */
function DOCUMENTS_previewDirectory()
{
    global $_DOCUMENTS_CONF;

    if (empty($_DOCUMENTS_CONF['path_images'])) {
        return '';
    }

    return rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\")
        . DIRECTORY_SEPARATOR . '_previews' . DIRECTORY_SEPARATOR;
}

/**
 * Ensure the persistent preview cache directory exists and is writable.
 *
 * @return bool
 */
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

/**
 * Remove image files that belonged to a document after its DB row was deleted.
 *
 * The function is intended for a shutdown callback. It first verifies that the
 * document no longer exists, so a failed/aborted delete never removes files.
 *
 * @param string $docUrl Document URL
 * @param array $images field id => filename captured before deletion
 * @return int Number of files removed
 */
function DOCUMENTS_cleanupDeletedDocumentImages($docUrl, $images)
{
    global $_TABLES, $_DOCUMENTS_CONF;

    if (!is_array($images) || empty($images) || empty($_DOCUMENTS_CONF['path_images'])) {
        return 0;
    }

    $docUrl = trim((string) $docUrl);
    if ($docUrl === '') {
        return 0;
    }

    $docUrlSql = DB_escapeString($docUrl);
    if (DB_getItem($_TABLES['documents_docs'], 'did', "doc_url='{$docUrlSql}'") !== '') {
        return 0;
    }

    $base = rtrim((string) $_DOCUMENTS_CONF['path_images'], "/\\") . DIRECTORY_SEPARATOR;
    $removed = 0;

    foreach ($images as $filename) {
        $filename = basename((string) $filename);
        if ($filename === '') {
            continue;
        }

        $path = $base . $filename;
        if (is_file($path) && @unlink($path)) {
            $removed++;
        }
    }

    return $removed;
}
