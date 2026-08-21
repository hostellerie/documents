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

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

$display = '';

if (!SEC_hasRights('documents.admin')) {
    $username = isset($_USER['username']) ? $_USER['username'] : 'unknown';

    $display .= COM_showMessageText($MESSAGE[29], $MESSAGE[30]);
    $display = COM_createHTMLDocument(
        $display,
        array('pagetitle' => $MESSAGE[30])
    );

    COM_accessLog(
        'User ' . $username
        . ' tried to illegally access the Documents plugin administration screen.'
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
$adminUrl = $_CONF['site_admin_url'] . '/plugins/documents/index.php';
$mode = isset($_GET['mode']) ? (string) $_GET['mode'] : '';

$display .= COM_startBlock(
    $pluginName,
    '',
    COM_getBlockTemplate('_admin_block', 'header')
);

if ($mode === 'integrity') {
    require_once $_CONF['path'] . 'plugins/documents/integrity.php';
    $report = DOCUMENTS_integrityReport();

    $display .= '<h2>Data integrity audit</h2>';
    $display .= '<p>This report is read-only. No data or files are modified.</p>';
    $display .= '<table class="admin-list" style="width:100%">';
    $display .= '<thead><tr><th>Check</th><th>Result</th></tr></thead><tbody>';
    $display .= '<tr><td>Values without document</td><td>'
        . (int) $report['orphan_values_without_document'] . '</td></tr>';
    $display .= '<tr><td>Values without field</td><td>'
        . (int) $report['orphan_values_without_field'] . '</td></tr>';
    $display .= '<tr><td>Fields without category</td><td>'
        . (int) $report['orphan_fields_without_category'] . '</td></tr>';
    $display .= '<tr><td>Referenced image files missing on disk</td><td>'
        . count($report['missing_image_files']) . '</td></tr>';
    $display .= '<tr><td>Image files not referenced by Documents</td><td>'
        . count($report['unreferenced_image_files']) . '</td></tr>';
    $display .= '</tbody></table>';

    if (!empty($report['missing_image_files'])) {
        $display .= '<h3>Missing image files</h3><ul>';
        foreach ($report['missing_image_files'] as $filename) {
            $display .= '<li>' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $display .= '</ul>';
    }

    if (!empty($report['unreferenced_image_files'])) {
        $display .= '<h3>Unreferenced image files</h3><ul>';
        foreach ($report['unreferenced_image_files'] as $filename) {
            $display .= '<li>' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $display .= '</ul>';
    }

    $display .= '<p><a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8')
        . '">&laquo; Back to Documents administration</a></p>';
} else {
    $display .= '<p><a href="'
        . htmlspecialchars($documentsUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($pluginName, ENT_QUOTES, 'UTF-8')
        . '</a></p>';
    $display .= '<p><a href="'
        . htmlspecialchars($adminUrl . '?mode=integrity', ENT_QUOTES, 'UTF-8')
        . '">Data integrity audit</a></p>';
}

$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

// Geeklog 2.1.1 and newer use COM_createHTMLDocument() for full-page output.
$display = COM_createHTMLDocument(
    $display,
    array('pagetitle' => $pluginName)
);

COM_output($display);
