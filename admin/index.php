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

$_SCRIPTS->setCSSFile(
    'documents_admin_css',
    '/admin/plugins/documents/documents.css',
    true
);

$display .= COM_startBlock(
    $pluginName,
    '',
    COM_getBlockTemplate('_admin_block', 'header')
);

if ($mode === 'integrity') {
    require_once $_CONF['path'] . 'plugins/documents/integrity.php';
    $report = DOCUMENTS_integrityReport();

    $details = '';

    if (!empty($report['duplicate_category_slugs'])) {
        $details .= '<h3>Duplicate category slugs</h3><ul>';
        foreach ($report['duplicate_category_slugs'] as $item) {
            $details .= '<li>' . htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8')
                . ' (' . (int) $item['count'] . ')</li>';
        }
        $details .= '</ul>';
    }

    if (!empty($report['duplicate_document_slugs'])) {
        $details .= '<h3>Duplicate document slugs</h3><ul>';
        foreach ($report['duplicate_document_slugs'] as $item) {
            $details .= '<li>' . htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8')
                . ' (' . (int) $item['count'] . ')</li>';
        }
        $details .= '</ul>';
    }

    if (!empty($report['missing_image_files'])) {
        $details .= '<h3>Missing image files</h3><ul>';
        foreach ($report['missing_image_files'] as $filename) {
            $details .= '<li>' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $details .= '</ul>';
    }

    if (!empty($report['unreferenced_image_files'])) {
        $details .= '<h3>Unreferenced image files</h3><ul>';
        foreach ($report['unreferenced_image_files'] as $filename) {
            $details .= '<li>' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $details .= '</ul>';
    }

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('integrity' => 'admin_integrity.thtml'));
    $template->set_var('audit_title', 'Data integrity audit');
    $template->set_var('audit_notice', 'This report is read-only. No data or files are modified.');
    $template->set_var('check_label', 'Check');
    $template->set_var('result_label', 'Result');
    $template->set_var('duplicate_category_label', 'Duplicate category slugs');
    $template->set_var('duplicate_category_count', count($report['duplicate_category_slugs']));
    $template->set_var('duplicate_document_label', 'Duplicate document slugs');
    $template->set_var('duplicate_document_count', count($report['duplicate_document_slugs']));
    $template->set_var('documents_without_values_label', 'Documents without values');
    $template->set_var('documents_without_values_count', (int) $report['orphan_documents_without_values']);
    $template->set_var('values_without_document_label', 'Values without document');
    $template->set_var('values_without_document_count', (int) $report['orphan_values_without_document']);
    $template->set_var('values_without_field_label', 'Values without field');
    $template->set_var('values_without_field_count', (int) $report['orphan_values_without_field']);
    $template->set_var('fields_without_category_label', 'Fields without category');
    $template->set_var('fields_without_category_count', (int) $report['orphan_fields_without_category']);
    $template->set_var('missing_images_label', 'Referenced image files missing on disk');
    $template->set_var('missing_images_count', count($report['missing_image_files']));
    $template->set_var('unreferenced_images_label', 'Image files not referenced by Documents');
    $template->set_var('unreferenced_images_count', count($report['unreferenced_image_files']));
    $template->set_var('details', $details);
    $template->set_var('admin_url', htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8'));
    $template->set_var('back_label', 'Back to Documents administration');
    $display .= $template->parse('output', 'integrity');
} else {
    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('home' => 'admin_home.thtml'));
    $template->set_var('documents_url', htmlspecialchars($documentsUrl, ENT_QUOTES, 'UTF-8'));
    $template->set_var('plugin_name', htmlspecialchars($pluginName, ENT_QUOTES, 'UTF-8'));
    $template->set_var(
        'integrity_url',
        htmlspecialchars($adminUrl . '?mode=integrity', ENT_QUOTES, 'UTF-8')
    );
    $template->set_var('integrity_label', 'Data integrity audit');
    $display .= $template->parse('output', 'home');
}

$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

$display = COM_createHTMLDocument(
    $display,
    array('pagetitle' => $pluginName)
);

COM_output($display);
