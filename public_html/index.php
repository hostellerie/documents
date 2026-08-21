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

if (function_exists('COM_errorLog')) {
    COM_errorLog('DOCUMENTS DEBUG D00 - public index entered; URI=' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown'));
}

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
        if (function_exists('COM_errorLog')) {
            COM_errorLog(
                'DOCUMENTS DEBUG SHUTDOWN - type=' . $error['type']
                . ' message=' . $error['message']
                . ' file=' . $error['file']
                . ' line=' . $error['line']
            );
        }
    } else {
        if (function_exists('COM_errorLog')) {
            COM_errorLog('DOCUMENTS DEBUG SHUTDOWN - normal request end, no PHP fatal error');
        }
    }
});

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    if (function_exists('COM_errorLog')) {
        COM_errorLog('DOCUMENTS DEBUG D01 - plugin inactive, redirecting');
    }
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

if (function_exists('COM_errorLog')) {
    COM_errorLog('DOCUMENTS DEBUG D02 - plugin active; loading include_compat.php');
}
require_once $_CONF['path'] . 'plugins/documents/include_compat.php';

if (function_exists('COM_errorLog')) {
    COM_errorLog('DOCUMENTS DEBUG D03 - include_compat.php loaded; initializing request defaults');
}
DOCUMENTS_initializeRequestDefaults($_REQUEST);

if (function_exists('COM_errorLog')) {
    COM_errorLog(
        'DOCUMENTS DEBUG D04 - before include_html.php; mode='
        . (isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '')
        . ' cat=' . (isset($_REQUEST['cat']) ? $_REQUEST['cat'] : '')
    );
}
require_once $_CONF['path'] . 'plugins/documents/include_html.php';

if (function_exists('COM_errorLog')) {
    COM_errorLog('DOCUMENTS DEBUG D99 - include_html.php returned normally');
}
