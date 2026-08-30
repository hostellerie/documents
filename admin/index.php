<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
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
        $details .= '<h3>'
            . htmlspecialchars($LANG_DOCUMENTS_1['integrity_duplicate_category_slugs'], ENT_QUOTES, 'UTF-8')
            . '</h3><ul>';
        foreach ($report['duplicate_category_slugs'] as $item) {
            $details .= '<li>' . htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8')
                . ' (' . (int) $item['count'] . ')</li>';
        }
        $details .= '</ul>';
    }

    if (!empty($report['duplicate_document_slugs'])) {
        $details .= '<h3>'
            . htmlspecialchars($LANG_DOCUMENTS_1['integrity_duplicate_document_slugs'], ENT_QUOTES, 'UTF-8')
            . '</h3><ul>';
        foreach ($report['duplicate_document_slugs'] as $item) {
            $details .= '<li>' . htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8')
                . ' (' . (int) $item['count'] . ')</li>';
        }
        $details .= '</ul>';
    }

    if (!empty($report['missing_image_files'])) {
        $details .= '<h3>'
            . htmlspecialchars($LANG_DOCUMENTS_1['integrity_missing_images'], ENT_QUOTES, 'UTF-8')
            . '</h3><ul>';
        foreach ($report['missing_image_files'] as $filename) {
            $details .= '<li>' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $details .= '</ul>';
    }

    if (!empty($report['unreferenced_image_files'])) {
        $details .= '<h3>'
            . htmlspecialchars($LANG_DOCUMENTS_1['integrity_unreferenced_images'], ENT_QUOTES, 'UTF-8')
            . '</h3><ul>';
        foreach ($report['unreferenced_image_files'] as $filename) {
            $details .= '<li>' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $details .= '</ul>';
    }

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('integrity' => 'admin_integrity.thtml'));
    $template->set_var('audit_title', $LANG_DOCUMENTS_1['integrity_audit_title']);
    $template->set_var('audit_notice', $LANG_DOCUMENTS_1['integrity_audit_notice']);
    $template->set_var('check_label', $LANG_DOCUMENTS_1['integrity_check']);
    $template->set_var('result_label', $LANG_DOCUMENTS_1['integrity_result']);
    $template->set_var('duplicate_category_label', $LANG_DOCUMENTS_1['integrity_duplicate_category_slugs']);
    $template->set_var('duplicate_category_count', count($report['duplicate_category_slugs']));
    $template->set_var('duplicate_document_label', $LANG_DOCUMENTS_1['integrity_duplicate_document_slugs']);
    $template->set_var('duplicate_document_count', count($report['duplicate_document_slugs']));
    $template->set_var('documents_without_values_label', $LANG_DOCUMENTS_1['integrity_documents_without_values']);
    $template->set_var('documents_without_values_count', (int) $report['orphan_documents_without_values']);
    $template->set_var('values_without_document_label', $LANG_DOCUMENTS_1['integrity_values_without_document']);
    $template->set_var('values_without_document_count', (int) $report['orphan_values_without_document']);
    $template->set_var('values_without_field_label', $LANG_DOCUMENTS_1['integrity_values_without_field']);
    $template->set_var('values_without_field_count', (int) $report['orphan_values_without_field']);
    $template->set_var('fields_without_category_label', $LANG_DOCUMENTS_1['integrity_fields_without_category']);
    $template->set_var('fields_without_category_count', (int) $report['orphan_fields_without_category']);
    $template->set_var('missing_images_label', $LANG_DOCUMENTS_1['integrity_missing_images']);
    $template->set_var('missing_images_count', count($report['missing_image_files']));
    $template->set_var('unreferenced_images_label', $LANG_DOCUMENTS_1['integrity_unreferenced_images']);
    $template->set_var('unreferenced_images_count', count($report['unreferenced_image_files']));
    $template->set_var('details', $details);
    $template->set_var('admin_url', htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8'));
    $template->set_var('back_label', $LANG_DOCUMENTS_1['integrity_back_admin']);
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
    $template->set_var('integrity_label', $LANG_DOCUMENTS_1['integrity_audit_title']);

    $newCategoryUrl = rtrim((string) $documentsUrl, '/') . '/index.php?mode=edit_cat';
    $template->set_var('new_category_url', htmlspecialchars($newCategoryUrl, ENT_QUOTES, 'UTF-8'));
    $template->set_var('new_category_label', htmlspecialchars($LANG_DOCUMENTS_1['new_cat'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('categories_label', htmlspecialchars($LANG_DOCUMENTS_1['categories'], ENT_QUOTES, 'UTF-8'));

    $categoryActions = '';
    $categoryResult = DB_query(
        "SELECT c.cid, c.cat_name, c.cat_url, COUNT(f.fid) AS field_count "
        . "FROM {$_TABLES['documents_cat']} AS c "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.cat_id=c.cid "
        . "GROUP BY c.cid, c.cat_name, c.cat_url "
        . "ORDER BY c.cat_order ASC, c.cat_name ASC"
    );

    while ($category = DB_fetchArray($categoryResult)) {
        if (!is_array($category) || empty($category['cid'])) {
            continue;
        }

        $cid = (int) $category['cid'];
        $catName = htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8');
        $catSlug = (string) $category['cat_url'];
        $fieldCount = isset($category['field_count']) ? (int) $category['field_count'] : 0;

        $editCategoryUrl = rtrim((string) $documentsUrl, '/')
            . '/index.php?mode=edit_cat&cid=' . $cid;
        $fieldsUrl = rtrim((string) $documentsUrl, '/')
            . '/index.php?mode=list_fields&cat=' . $cid;

        $categoryActions .= '<div class="documents-admin-category">';
        $categoryActions .= '<h3>' . $catName . '</h3>';
        $categoryActions .= '<p><a href="'
            . htmlspecialchars($editCategoryUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($LANG_DOCUMENTS_1['edit_cat'], ENT_QUOTES, 'UTF-8') . '</a> | ';
        $categoryActions .= '<a href="'
            . htmlspecialchars($fieldsUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($LANG_DOCUMENTS_1['fields'], ENT_QUOTES, 'UTF-8') . '</a>';

        if ($fieldCount > 0 && $catSlug !== '') {
            $newDocumentUrl = rtrim((string) $documentsUrl, '/')
                . '/index.php?mode=new&cat=' . rawurlencode($catSlug);
            $categoryActions .= ' | <a href="'
                . htmlspecialchars($newDocumentUrl, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($LANG_DOCUMENTS_1['create_new_doc'], ENT_QUOTES, 'UTF-8') . '</a>';
        }
        $categoryActions .= '</p>';

        if ($fieldCount === 0) {
            $categoryActions .= '<p><em>'
                . htmlspecialchars($LANG_DOCUMENTS_1['new_field'], ENT_QUOTES, 'UTF-8')
                . '</em></p>';
        }

        $categoryActions .= '</div>';
    }

    if ($categoryActions === '') {
        $categoryActions = '<p>'
            . htmlspecialchars($LANG_DOCUMENTS_1['none'], ENT_QUOTES, 'UTF-8')
            . ' — <a href="' . htmlspecialchars($newCategoryUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($LANG_DOCUMENTS_1['new_cat'], ENT_QUOTES, 'UTF-8')
            . '</a></p>';
    }

    $template->set_var('category_actions', $categoryActions);
    $display .= $template->parse('output', 'home');
}

$display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

$display = COM_createHTMLDocument(
    $display,
    array('pagetitle' => $pluginName)
);

COM_output($display);
