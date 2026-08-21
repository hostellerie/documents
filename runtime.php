<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.8                                                    |
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
 * Remove all cached previews generated from one original image.
 *
 * Preview filenames start with sha1(original filename), so cleanup does not
 * need to know which dimensions or source modification times were cached.
 *
 * @param string $filename Original image filename
 * @return int Number of preview files removed
 */
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
