<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.1                                                    |
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

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

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

$display .= COM_siteHeader('menu', $LANG_DOCUMENTS_1['plugin_name']);
$display .= COM_startBlock($LANG_DOCUMENTS_1['plugin_name']);
$display .= '<p><a href="' . $_DOCUMENTS_CONF['site_url'] . '">'
    . $LANG_DOCUMENTS_1['plugin_name'] . '</a></p>';
$display .= COM_endBlock();
$display .= COM_siteFooter();

COM_output($display);
