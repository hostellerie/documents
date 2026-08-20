<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | index.php                                                                 |
// |                                                                           |
// | Plugin administration entry point.                                        |
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

$documentsDebug = isset($_GET['documents_debug']) ? $_GET['documents_debug'] : '';

if ($documentsDebug !== '') {
    error_reporting(E_ALL);
    @ini_set('display_errors', '1');
    @ini_set('display_startup_errors', '1');

    if ($documentsDebug === 'pre') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Documents debug PRE: admin/index.php is executing.\n";
        exit;
    }

    register_shutdown_function(function () {
        $error = error_get_last();
        if (is_array($error)) {
            $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
            if (in_array($error['type'], $fatalTypes, true)) {
                echo '<pre style="padding:1em;background:#fff;color:#900;border:1px solid #900;">';
                echo "Documents admin fatal error\n";
                echo 'Type: ' . (int) $error['type'] . "\n";
                echo 'Message: ' . htmlspecialchars($error['message'], ENT_QUOTES, 'UTF-8') . "\n";
                echo 'File: ' . htmlspecialchars($error['file'], ENT_QUOTES, 'UTF-8') . "\n";
                echo 'Line: ' . (int) $error['line'] . "\n";
                echo '</pre>';
            }
        }
    });
}

require_once '../../../lib-common.php';

if ($documentsDebug === 'lib') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Documents debug LIB: lib-common.php loaded successfully.\n";
    exit;
}

require_once '../../auth.inc.php';

if ($documentsDebug === 'auth') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Documents debug AUTH: lib-common.php and auth.inc.php loaded successfully.\n";
    exit;
}

$display = '';

if (!SEC_hasRights('documents.admin')) {
    $username = isset($_USER['username']) ? $_USER['username'] : 'unknown';

    $display .= COM_siteHeader('menu', $MESSAGE[30]);
    $display .= COM_showMessageText($MESSAGE[29], $MESSAGE[30]);
    $display .= COM_siteFooter();

    COM_accessLog(
        'User ' . $username .
        ' tried to illegally access the Documents plugin administration screen.'
    );

    COM_output($display);
    exit;
}

$pluginName = isset($LANG_DOCUMENTS_1['plugin_name'])
    ? $LANG_DOCUMENTS_1['plugin_name']
    : 'Documents';
$documentsUrl = isset($_DOCUMENTS_CONF['site_url'])
    ? $_DOCUMENTS_CONF['site_url']
    : $_CONF['site_url'] . '/documents';

$display .= COM_siteHeader('menu', $pluginName);
$display .= COM_startBlock($pluginName);
$display .= '<p><a href="' . htmlspecialchars($documentsUrl, ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($pluginName, ENT_QUOTES, 'UTF-8') . '</a></p>';
$display .= COM_endBlock();
$display .= COM_siteFooter();

COM_output($display);
