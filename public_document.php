<?php

/* Unified public document renderer. Compatible with Geeklog 2.1.1-2.2.2 and PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'public_document.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_publicChoiceValue($groupId, $storedValue)
{
    global $_TABLES;

    $groupId = (int) $groupId;
    $storedValue = (string) $storedValue;
    if ($groupId <= 0 || $storedValue === '') {
        return $storedValue;
    }

    $safe = DB_escapeString($storedValue);
    $label = DB_getItem(
        $_TABLES['documents_selects'],
        's_value',
        "s_group={$groupId} AND s_name='{$safe}'"
    );

    return $label === '' ? $storedValue : (string) $label;
}

function DOCUMENTS_publicMarkerHtml($markerId)
{
    $markerId = preg_replace('/[^0-9]/', '', (string) $markerId);
    if ($markerId === '' || !DOCUMENTS_hasMaps() || !function_exists('PLG_invokeService')) {
        return '';
    }

    $output = '';
    $message = array();
    $result = PLG_invokeService(
        'maps',
        'marker_render',
        array(
            'marker_id' => $markerId,
            'width' => '100%',
            'height' => '400px',
            'zoom' => 14
        ),
        $output,
        $message
    );

    return ($result === PLG_RET_OK) ? (string) $output : '';
}

function DOCUMENTS_publicFieldHtml($field, $value, $title)
{
    global $_DOCUMENTS_CONF;

    $type = isset($field['f_type']) ? (string) $field['f_type'] : '';
    $value = (string) $value;

    if ($type === 'checkbox') {
        return ((int) $value === 1)
            ? '<span class="documents-boolean documents-boolean--true" aria-label="true">&#10003;</span>'
            : '<span class="documents-boolean documents-boolean--false" aria-label="false">&#8212;</span>';
    }

    if ($value === '') {
        return '';
    }

    if ($type === 'marker') {
        return DOCUMENTS_publicMarkerHtml($value);
    }

    if ($type === 'album') {
        return function_exists('DOCUMENTS_mediaGalleryRenderAlbum')
            ? DOCUMENTS_mediaGalleryRenderAlbum($value)
            : '';
    }

    if ($type === 'category' || $type === 'file') {
        return '';
    }

    if ($type === 'select' || $type === 'radio') {
        $value = DOCUMENTS_publicChoiceValue(
            isset($field['sel_id']) ? (int) $field['sel_id'] : 0,
            $value
        );
    } elseif ($type === 'text' && function_exists('DOCUMENTS_formatTextDisplay')) {
        $value = DOCUMENTS_formatTextDisplay(
            $value,
            isset($field['sel_id']) ? (int) $field['sel_id'] : 0
        );
    }

    if ($type === 'image') {
        $filename = basename($value);
        if ($filename === '') {
            return '';
        }

        $src = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/image.php?src=' . rawurlencode($filename) . '&amp;w=900';

        return '<img class="documents-document-image" src="'
            . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    }

    $safe = htmlspecialchars(stripslashes($value), ENT_QUOTES, 'UTF-8');
    return $type === 'textarea' ? nl2br($safe) : $safe;
}

function DOCUMENTS_publicDocumentTitle($fields, $fallback)
{
    if (!is_array($fields)) {
        return (string) $fallback;
    }

    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }

        $variable = isset($field['var_name'])
            ? strtolower(trim((string) $field['var_name'])) : '';
        if ($variable === 'metadescription' || $variable === 'schema_type') {
            continue;
        }

        $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';
        if ($type !== 'text' && $type !== 'textarea') {
            continue;
        }

        $candidate = isset($field['v_value'])
            ? trim(stripslashes((string) $field['v_value'])) : '';
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return (string) $fallback;
}

function DOCUMENTS_publicDocumentData($categorySlug, $documentSlug)
{
    global $_TABLES;

    $safeCategory = DB_escapeString($categorySlug);
    $safeDocument = DB_escapeString($documentSlug);

    $category = DB_fetchArray(DB_query(
        "SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeCategory}' LIMIT 1"
    ));
    if (!is_array($category) || empty($category['cid'])) {
        return array();
    }

    $categoryAccess = SEC_hasAccess(
        (int) $category['owner_id'],
        (int) $category['group_id'],
        (int) $category['perm_owner'],
        (int) $category['perm_group'],
        (int) $category['perm_members'],
        (int) $category['perm_anon']
    );
    if ($categoryAccess < 2) {
        return array();
    }

    $categoryId = (int) $category['cid'];
    $document = DB_fetchArray(DB_query(
        "SELECT d.* FROM {$_TABLES['documents_docs']} AS d "
        . "WHERE d.doc_url='{$safeDocument}' AND EXISTS ("
        . "SELECT 1 FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE v.doc_url=d.doc_url AND f.cat_id={$categoryId}) LIMIT 1"
    ));
    if (!DOCUMENTS_canViewDocument($document, 2)) {
        return array();
    }

    $fieldsResult = DB_query(
        "SELECT f.fid, f.f_name, f.f_order, f.f_type, f.sel_id, f.var_name, "
        . "f.f_required, f.f_on_list, v.v_value "
        . "FROM {$_TABLES['documents_fields']} AS f "
        . "LEFT JOIN {$_TABLES['documents_values']} AS v "
        . "ON v.field_id=f.fid AND v.doc_url='{$safeDocument}' "
        . "WHERE f.cat_id={$categoryId} ORDER BY f.f_order ASC, f.fid ASC"
    );

    $fields = array();
    while ($field = DB_fetchArray($fieldsResult)) {
        if (is_array($field)) {
            $fields[] = $field;
        }
    }

    return array(
        'category' => $category,
        'document' => $document,
        'fields' => $fields
    );
}

function DOCUMENTS_renderPublicDocument($categorySlug, $documentSlug)
{
    global $_CONF, $_DOCUMENTS_CONF, $_SCRIPTS, $_TABLES, $LANG_DOCUMENTS_1;

    $data = DOCUMENTS_publicDocumentData($categorySlug, $documentSlug);
    if (empty($data)) {
        return false;
    }

    $category = $data['category'];
    $document = $data['document'];
    $fields = $data['fields'];

    $title = DOCUMENTS_publicDocumentTitle($fields, $documentSlug);

    $templateName = isset($category['template'])
        ? DOCUMENTS_templateName($category['template']) : '';
    $templateDir = $templateName !== ''
        ? DOCUMENTS_customTemplateReadDir($templateName) : '';
    $customTemplate = $templateDir !== '';

    if ($customTemplate) {
        $template = COM_newTemplate(rtrim($templateDir, '/\\'));
        $scriptsFile = rtrim($templateDir, '/\\') . DIRECTORY_SEPARATOR . 'scripts.thtml';
        if (is_file($scriptsFile) && is_readable($scriptsFile)
            && isset($_SCRIPTS) && is_object($_SCRIPTS)) {
            $_SCRIPTS->setJavaScript(file_get_contents($scriptsFile), false);
        }
    } else {
        $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    }

    $template->set_file(array('doc' => 'document.thtml'));
    $template->set_var('doc_name', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'));

    $details = '<dl class="documents-fields">';
    foreach ($fields as $field) {
        $value = isset($field['v_value']) ? $field['v_value'] : '';
        $rendered = DOCUMENTS_publicFieldHtml($field, $value, $title);
        $variable = isset($field['var_name']) ? (string) $field['var_name'] : '';
        $normalizedVariable = strtolower(trim($variable));

        if ($variable !== '') {
            $template->set_var($variable, $rendered);
        }

        if ($normalizedVariable === 'metadescription'
            || $normalizedVariable === 'schema_type') {
            continue;
        }

        if (trim(stripslashes((string) $value)) === $title
            && ($field['f_type'] === 'text' || $field['f_type'] === 'textarea')) {
            continue;
        }

        if ($rendered === '' && $field['f_type'] !== 'checkbox') {
            continue;
        }

        $details .= '<div class="documents-field documents-field--'
            . htmlspecialchars((string) $field['f_type'], ENT_QUOTES, 'UTF-8') . '">'
            . '<dt class="documents-field__label">'
            . htmlspecialchars(stripslashes((string) $field['f_name']), ENT_QUOTES, 'UTF-8')
            . '</dt><dd class="documents-field__value">' . $rendered . '</dd></div>';
    }
    $details .= '</dl>';
    $template->set_var('raws', $customTemplate ? '' : $details);

    $status = (int) $document['active'];
    $statusLabel = '';
    if ($status === DOCUMENTS_STATUS_INACTIVE) {
        $statusLabel = isset($LANG_DOCUMENTS_1['not_active']) ? $LANG_DOCUMENTS_1['not_active'] : 'Inactive';
    } elseif ($status === DOCUMENTS_STATUS_DRAFT) {
        $statusLabel = isset($LANG_DOCUMENTS_1['draft']) ? $LANG_DOCUMENTS_1['draft'] : 'Draft';
    } elseif ($status === DOCUMENTS_STATUS_SUBMISSION) {
        $statusLabel = isset($LANG_DOCUMENTS_1['submission']) ? $LANG_DOCUMENTS_1['submission'] : 'Submission';
    }
    $template->set_var(
        'active',
        $statusLabel === '' ? '' : '<span class="documents-status">'
            . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</span> '
    );

    $edit = '';
    if (DOCUMENTS_canEditDocument($document)) {
        $editUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
            . '/index.php?mode=edit&doc_url=' . rawurlencode($documentSlug)
            . '&cat=' . (int) $category['cid'];
        $edit = '<a class="documents-edit-link" href="'
            . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars(isset($LANG_DOCUMENTS_1['edit']) ? $LANG_DOCUMENTS_1['edit'] : 'Edit', ENT_QUOTES, 'UTF-8')
            . '</a>';
    }
    $template->set_var('editor', $edit);

    $authorUrl = $_CONF['site_url'] . '/users.php?mode=profile&uid=' . (int) $document['owner_id'];
    $template->set_var('user_name', COM_createLink(COM_getDisplayName((int) $document['owner_id']), $authorUrl));
    $template->set_var('doc_by', isset($LANG_DOCUMENTS_1['doc_by']) ? $LANG_DOCUMENTS_1['doc_by'] : 'By');
    $template->set_var('displayed', isset($LANG_DOCUMENTS_1['displayed']) ? $LANG_DOCUMENTS_1['displayed'] : 'Viewed');
    $template->set_var('times', isset($LANG_DOCUMENTS_1['times']) ? $LANG_DOCUMENTS_1['times'] : 'times');

    DB_query("UPDATE {$_TABLES['documents_docs']} SET hits=hits+1 WHERE doc_url='"
        . DB_escapeString($documentSlug) . "'");
    $hits = isset($document['hits']) ? ((int) $document['hits'] + 1) : 1;
    $template->set_var('hits', COM_numberFormat($hits));
    $template->set_var('document_url', DOCUMENTS_interopCanonicalUrl($categorySlug, $documentSlug));

    require_once $_CONF['path_system'] . 'lib-comment.php';
    $template->set_var(
        'commentbar',
        CMT_userComments(
            $documentSlug,
            $title,
            'documents',
            'ASC',
            'nested',
            0,
            1,
            false,
            false,
            0
        )
    );

    $body = '';
    if (function_exists('DOCUMENTS_renderNavigation')) {
        $body .= DOCUMENTS_renderNavigation();
    }
    if (!empty($category['custom_header'])) {
        $body .= '<div class="documents-category-header">'
            . PLG_replaceTags((string) $category['custom_header']) . '</div>';
    }
    $body .= $template->finish($template->parse('output', 'doc'));
    if (!empty($category['custom_footer'])) {
        $body .= '<div class="documents-category-footer">'
            . PLG_replaceTags((string) $category['custom_footer']) . '</div>';
    }

    return array(
        'title' => $title,
        'body' => $body,
        'category_name' => isset($category['cat_name']) ? stripslashes((string) $category['cat_name']) : '',
        'category_slug' => isset($category['cat_url']) ? (string) $category['cat_url'] : $categorySlug
    );
}
