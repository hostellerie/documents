<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | index.php                                                                 |
// |                                                                           |
// | Public plugin page.                                                       |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// +---------------------------------------------------------------------------+

/**
 * @package Documents
 */

require_once '../lib-common.php';

/*
 * TEMPORARY RUNTIME DIAGNOSTICS
 * Remove this block once the blank-page issue has been identified.
 */
error_reporting(E_ALL);
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
@ini_set('log_errors', '1');

$documentsBootstrapDebug = isset($_GET['documents_debug']) && $_GET['documents_debug'] == '1';

function DOCUMENTS_debugOutput($message)
{
    global $documentsBootstrapDebug;

    if ($documentsBootstrapDebug) {
        echo '<pre style="background:#fff;color:#000;padding:10px;border:1px solid #ccc">'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            . '</pre>';
        @ob_flush();
        @flush();
    }

    if (function_exists('COM_errorLog')) {
        COM_errorLog($message);
    }
}

DOCUMENTS_debugOutput(
    'DOCUMENTS DEBUG B00 - public index entered; URI='
    . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown')
);

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (function_exists('COM_errorLog')) {
        COM_errorLog(
            'DOCUMENTS DEBUG PHP - type=' . $errno
            . ' message=' . $errstr
            . ' file=' . $errfile
            . ' line=' . $errline
        );
    }
    return false;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        $message = 'DOCUMENTS DEBUG SHUTDOWN - type=' . $error['type']
            . ' message=' . $error['message']
            . ' file=' . $error['file']
            . ' line=' . $error['line'];
        if (function_exists('COM_errorLog')) {
            COM_errorLog($message);
        }
        if (isset($_GET['documents_debug']) && $_GET['documents_debug'] == '1') {
            echo '<pre style="background:#fff;color:#900;padding:10px;border:1px solid #900">'
                . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
                . '</pre>';
        }
    } elseif (function_exists('COM_errorLog')) {
        COM_errorLog('DOCUMENTS DEBUG SHUTDOWN - normal request end, no PHP fatal error');
    }
});

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    DOCUMENTS_debugOutput('DOCUMENTS DEBUG B01 - plugin inactive, redirecting');
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

DOCUMENTS_debugOutput('DOCUMENTS DEBUG B02 - plugin active; loading include_compat.php');
require_once $_CONF['path'] . 'plugins/documents/include_compat.php';

DOCUMENTS_debugOutput('DOCUMENTS DEBUG B03 - include_compat.php loaded; initializing request defaults');
DOCUMENTS_initializeRequestDefaults($_REQUEST);

DOCUMENTS_debugOutput(
    'DOCUMENTS DEBUG B04 - before include_html.php; mode='
    . (isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '')
    . ' cat=' . (isset($_REQUEST['cat']) ? $_REQUEST['cat'] : '')
);
require_once $_CONF['path'] . 'plugins/documents/include_html.php';

DOCUMENTS_debugOutput('DOCUMENTS DEBUG B99 - include_html.php returned normally');
