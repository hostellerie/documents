<?php

/* Documents 1.2.0 persistent category presentation assets. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'custom_assets.php') !== false) {
    die('This file can not be used on its own.');
}

/**
 * Return the persistent Documents templates directory.
 *
 * Custom files deliberately live outside the plugin tree so reinstalling or
 * upgrading Documents does not overwrite administrator customizations.
 *
 * @return string
 */
function DOCUMENTS_customTemplatesRoot()
{
    if (!function_exists('DOCUMENTS_dataDir')) {
        return '';
    }

    $base = DOCUMENTS_dataDir();
    if ($base === '') {
        return '';
    }

    return rtrim($base, "/\\") . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
}

/**
 * Return the persistent Documents styles directory.
 *
 * @return string
 */
function DOCUMENTS_customStylesRoot()
{
    if (!function_exists('DOCUMENTS_dataDir')) {
        return '';
    }

    $base = DOCUMENTS_dataDir();
    if ($base === '') {
        return '';
    }

    return rtrim($base, "/\\") . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR;
}

/**
 * Ensure persistent template/style directories exist.
 *
 * @return bool
 */
function DOCUMENTS_ensureCustomAssetDirectories()
{
    global $_CONF;

    if (!function_exists('DOCUMENTS_dataDir')) {
        return false;
    }

    $base = DOCUMENTS_dataDir();
    if ($base === '') {
        return false;
    }

    if (!is_dir($base)) {
        $storageFile = isset($_CONF['path'])
            ? $_CONF['path'] . 'plugins/documents/storage.php'
            : '';
        if ($storageFile !== '' && is_file($storageFile)) {
            require_once $storageFile;
        }
        if (function_exists('DOCUMENTS_ensureDataDirectory')) {
            if (!DOCUMENTS_ensureDataDirectory()) {
                return false;
            }
        } elseif (!@mkdir($base, 0755, true) && !is_dir($base)) {
            return false;
        }
    }

    $directories = array(
        DOCUMENTS_customTemplatesRoot(),
        DOCUMENTS_customStylesRoot()
    );

    foreach ($directories as $directory) {
        if ($directory === '') {
            return false;
        }
        if (!is_dir($directory)
            && !@mkdir($directory, 0755, true)
            && !is_dir($directory)) {
            if (function_exists('COM_errorLog')) {
                COM_errorLog('Documents custom assets: unable to create ' . $directory);
            }
            return false;
        }
    }

    return true;
}

/**
 * Validate a custom CSS filename stored on a category.
 *
 * Only a filename is accepted, never a path. This prevents path traversal and
 * gives the option one clear meaning in every supported Geeklog version.
 *
 * @param string $name
 * @return string Empty string when invalid.
 */
function DOCUMENTS_customStyleName($name)
{
    $name = trim((string) $name);
    if ($name === '' || basename($name) !== $name || strpos($name, '..') !== false) {
        return '';
    }
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.css$/', $name)) {
        return '';
    }

    return $name;
}

/**
 * Resolve a custom category CSS file in persistent storage.
 *
 * @param string $name
 * @return string Empty string when the file is not usable.
 */
function DOCUMENTS_customStylePath($name)
{
    $name = DOCUMENTS_customStyleName($name);
    if ($name === '') {
        return '';
    }

    $root = DOCUMENTS_customStylesRoot();
    if ($root === '') {
        return '';
    }

    $path = $root . $name;
    return is_file($path) && is_readable($path) ? $path : '';
}

/**
 * Validate that a named custom document template is usable.
 *
 * Historical Documents templates require document.thtml and doccomments.thtml.
 * scripts.thtml remains optional.
 *
 * @param string $name
 * @return bool
 */
function DOCUMENTS_customTemplateIsReady($name)
{
    if (!function_exists('DOCUMENTS_templateName')
        || !function_exists('DOCUMENTS_customTemplateReadDir')) {
        return false;
    }

    $name = DOCUMENTS_templateName($name);
    if ($name === '') {
        return false;
    }

    $directory = DOCUMENTS_customTemplateReadDir($name);
    if ($directory === '') {
        return false;
    }

    return is_file($directory . 'document.thtml')
        && is_readable($directory . 'document.thtml')
        && is_file($directory . 'doccomments.thtml')
        && is_readable($directory . 'doccomments.thtml');
}

/**
 * Register a category CSS file through Documents' controlled public endpoint.
 *
 * setCSSFile() in Geeklog 2.1.1 expects a URI whose physical endpoint exists
 * below public_html; Geeklog 2.2.x accepts the same local URI. Serving the
 * actual CSS through style.php lets the source file remain outside public_html.
 *
 * @param string $cssName
 * @return bool
 */
function DOCUMENTS_loadCategoryStyle($cssName)
{
    global $_DOCUMENTS_CONF, $_SCRIPTS;

    $cssName = DOCUMENTS_customStyleName($cssName);
    if ($cssName === '' || DOCUMENTS_customStylePath($cssName) === '') {
        return false;
    }

    if (!isset($_SCRIPTS) || !is_object($_SCRIPTS)
        || !method_exists($_SCRIPTS, 'setCSSFile')) {
        return false;
    }

    $folder = isset($_DOCUMENTS_CONF['documents_folder'])
        ? trim((string) $_DOCUMENTS_CONF['documents_folder'], '/')
        : 'documents';
    if ($folder === '') {
        $folder = 'documents';
    }

    $uri = '/' . $folder . '/style.php?name=' . rawurlencode($cssName);

    return (bool) $_SCRIPTS->setCSSFile(
        'documents_category_' . substr(sha1($cssName), 0, 12),
        $uri
    );
}

/**
 * Load the CSS selected by the category present in the current request.
 *
 * @return bool
 */
function DOCUMENTS_loadRequestedCategoryStyle()
{
    global $_TABLES;

    if (!isset($_GET['cat']) || !isset($_TABLES['documents_cat'])) {
        return false;
    }

    $slug = trim((string) $_GET['cat']);
    if ($slug === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
        return false;
    }

    $safeSlug = DB_escapeString($slug);
    $css = DB_getItem(
        $_TABLES['documents_cat'],
        'css',
        "cat_url='{$safeSlug}'"
    );

    return DOCUMENTS_loadCategoryStyle($css);
}
