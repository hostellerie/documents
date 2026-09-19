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
     * Public clean URLs are routed directly to dedicated read controllers.
     * Mutations and structural administration continue to use index.php.
     *
     * @param bool $force Rewrite an existing generated file when true
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
        $signature = '# Documents generated rewrite v1.2.0-r4';

        /* Preserve the historical clean URL contract:
         * /documents/category/document -> public document
         * /documents/category          -> public category
         * /documents/                  -> public home
         *
         * Read routes deliberately bypass index.php so legacy identifiers such
         * as cv_cordiste and 126_darraidou reach their controllers unchanged. */
        $rules = $signature . "\n"
            . "RewriteEngine On\n\n"
            . "RewriteCond %{QUERY_STRING} (^|&)mode=save(&|$)\n"
            . "RewriteRule ^index\\.php$ document-save.php [L,QSA]\n\n"
            . "RewriteRule ^$ home.php [L]\n\n"
            . "RewriteCond %{REQUEST_FILENAME} !-f\n"
            . "RewriteCond %{REQUEST_FILENAME} !-d\n"
            . "RewriteRule ^([^/]+)/([^/]+)/?$ document.php?cat=$1&doc=$2 [L,QSA]\n\n"
            . "RewriteCond %{REQUEST_FILENAME} !-f\n"
            . "RewriteCond %{REQUEST_FILENAME} !-d\n"
            . "RewriteRule ^([^/]+)/?$ category-route.php?cat=$1 [L,QSA]\n";

        if (!$force && is_file($target)) {
            $existing = @file_get_contents($target);
            if (is_string($existing)) {
                if (strpos($existing, $signature) !== false) {
                    return true;
                }

                /* Upgrade only Documents-owned rule sets. Administrator-owned
                 * .htaccess files remain untouched. */
                $knownDocumentsRules = strpos($existing, '# Documents generated rewrite v1.2.0-r3') !== false
                    || strpos($existing, '# Documents generated rewrite v1.2.0-r2') !== false
                    || strpos($existing, '# Documents generated rewrite v1.2.0-r1') !== false
                    || (strpos($existing, 'document.php?cat=$1&doc=$2') !== false
                        && strpos($existing, 'category.php?cat=$1') !== false);

                if (!$knownDocumentsRules) {
                    return true;
                }
            } else {
                return true;
            }
        }

        if (@file_put_contents($target, $rules, LOCK_EX) === false) {
            if (function_exists('COM_errorLog')) {
                COM_errorLog('Documents rewrite: unable to write ' . $target);
            }
            return false;
        }

        return true;
    }
}
