<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | rewrite.php                                                               |
// |                                                                           |
// | Creates and refreshes the public .htaccess rewrite rules.                 |
// +---------------------------------------------------------------------------+

/**
 * @package Documents
 */

if (!function_exists('DOCUMENTS_writeHtaccess')) {
    /**
     * Create or refresh the Documents .htaccess file.
     *
     * Normal runtime calls use $force=false and only repair a missing file.
     * Installation/update calls use $force=true so the current plugin rules
     * replace rules shipped by an older Documents release.
     *
     * @param bool $force Rewrite an existing file when true
     * @return bool
     */
    function DOCUMENTS_writeHtaccess($force = false)
    {
        global $_CONF, $_DOCUMENTS_CONF;

        if (isset($_DOCUMENTS_CONF['path_html']) && $_DOCUMENTS_CONF['path_html'] !== '') {
            $publicDir = rtrim($_DOCUMENTS_CONF['path_html'], "/\\") . DIRECTORY_SEPARATOR;
        } else {
            $folder = isset($_DOCUMENTS_CONF['documents_folder'])
                ? trim($_DOCUMENTS_CONF['documents_folder'], "/\\")
                : 'documents';
            $publicDir = rtrim($_CONF['path_html'], "/\\") . DIRECTORY_SEPARATOR
                . $folder . DIRECTORY_SEPARATOR;
        }

        if (!is_dir($publicDir)) {
            if (function_exists('COM_errorLog')) {
                COM_errorLog('Documents rewrite: public directory not found at ' . $publicDir);
            }
            return false;
        }

        $target = $publicDir . '.htaccess';
        if (!$force && is_file($target)) {
            return true;
        }

        $rules = "RewriteEngine On\n\n"
            . "RewriteRule ^$ home.php [L]\n\n"
            . "RewriteCond %{REQUEST_FILENAME} !-f\n"
            . "RewriteCond %{REQUEST_FILENAME} !-d\n"
            . "RewriteRule ^([^/]+)/([^/]+)/?$ index.php?mode=view&cat=$1&doc=$2 [L,QSA]\n\n"
            . "RewriteCond %{REQUEST_FILENAME} !-f\n"
            . "RewriteCond %{REQUEST_FILENAME} !-d\n"
            . "RewriteRule ^([^/]+)/?$ category.php?cat=$1 [L,QSA]\n";

        if (@file_put_contents($target, $rules, LOCK_EX) === false) {
            if (function_exists('COM_errorLog')) {
                COM_errorLog('Documents rewrite: unable to write ' . $target);
            }
            return false;
        }

        return true;
    }
}
