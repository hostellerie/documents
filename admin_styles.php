<?php

/* Documents 1.2.0 admin stylesheet compatibility helper. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'admin_styles.php') !== false) {
    die('This file can not be used on its own.');
}

/**
 * Register the Documents admin stylesheet with Geeklog.
 *
 * Geeklog 2.1.1 already exposes the scripts resource object and setCSSFile(),
 * while Geeklog 2.2.x keeps the same capability through its newer resource
 * handling.  Use capability detection rather than a version-number branch.
 *
 * setCSSFile() expects a path relative to public_html.  Passing an absolute
 * site URL can be rejected by Geeklog 2.1.1 because it checks the corresponding
 * local file before registering it.
 *
 * @return bool True when the stylesheet was registered.
 */
function DOCUMENTS_loadAdminStyles()
{
    global $_SCRIPTS;

    if (!isset($_SCRIPTS)
        || !is_object($_SCRIPTS)
        || !method_exists($_SCRIPTS, 'setCSSFile')) {
        return false;
    }

    $cssFile = '/admin/plugins/documents/documents.css?v=1.2.0';

    return (bool) $_SCRIPTS->setCSSFile(
        'documents_admin_css',
        $cssFile
    );
}
