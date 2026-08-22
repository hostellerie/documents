<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | include_edit.php                                                          |
// |                                                                           |
// | Documents administration/edit helpers.                                    |
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

function DOCUMENTS_editCat($cat = array())
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;
    global $LANG_ACCESS, $_USER, $_GROUPS;

    $defaults = array(
        'cid' => '', 'cat_name' => '', 'cat_url' => '', 'css' => '', 'map' => 0,
        'template' => '', 'cat_order' => '', 'list_index' => '', 'submitable' => '',
        'cat_help' => '', 'custom_header' => '', 'custom_footer' => '',
        'owner_id' => '', 'group_id' => '', 'perm_owner' => '', 'perm_group' => '',
        'perm_members' => '', 'perm_anon' => ''
    );
    $cat = array_merge($defaults, is_array($cat) ? $cat : array());

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('cat' => 'cat_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
    $template->set_var('xhtml', XHTML);
    $csrfToken = SEC_createToken();
    $template->set_var('gltoken_name', CSRF_TOKEN);
    $template->set_var('gltoken', $csrfToken);

    if ($cat['cid'] === '') {
        $template->set_var('cat_informations', $LANG_DOCUMENTS_1['new_cat']);
    } else {
        $template->set_var(
            'cat_informations',
            $LANG_DOCUMENTS_1['edit_cat'] . ' ' . $cat['cat_name']
        );
    }

    if (is_numeric($cat['cid'])) {
        $template->set_var(
            'cid',
            '<input type="hidden" name="cid" value="' . (int) $cat['cid'] . '" />'
        );
    } else {
        $template->set_var('cid', '');
    }

    $template->set_var('category_label', $LANG_DOCUMENTS_1['cat_name']);
    $template->set_var('category', htmlspecialchars($cat['cat_name'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('cat_url_label', $LANG_DOCUMENTS_1['cat_url']);
    $template->set_var('cat_url', htmlspecialchars($cat['cat_url'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('css_label', $LANG_DOCUMENTS_1['css']);
    $template->set_var('css', htmlspecialchars($cat['css'], ENT_QUOTES, 'UTF-8'));

    if (DOCUMENTS_hasMaps() && function_exists('MAPS_recurseMaps')) {
        $mapOptions = MAPS_recurseMaps((int) $cat['map']);
        $map = '<div><p><label>' . $LANG_DOCUMENTS_1['use_map'] . '</label> '
            . '<select id="map" name="map"><option value="0">'
            . $LANG_DOCUMENTS_1['no_map'] . '</option>' . $mapOptions . '</select>'
            . '<br' . XHTML . '>' . $LANG_DOCUMENTS_1['use_map_details'] . '</p></div>';
        $template->set_var('map', $map);
    } else {
        $template->set_var('map', '');
    }

    $template->set_var('template_label', $LANG_DOCUMENTS_1['template']);
    $template->set_var('template', htmlspecialchars($cat['template'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('catorder_label', $LANG_DOCUMENTS_1['cat_order']);

    if ($cat['cat_order'] === '') {
        $cat['cat_order'] = (int) DB_getItem(
            $_TABLES['documents_cat'],
            'MAX(cat_order)',
            '1=1'
        ) + 10;
    }
    $template->set_var('cat_order', (int) $cat['cat_order']);
    $template->set_var('existing_cat', $LANG_DOCUMENTS_1['existing_cat']);

    $categoriesOrder = '';
    $res = DB_query(
        "SELECT cat_order, cat_name, cat_url FROM {$_TABLES['documents_cat']} "
        . 'ORDER BY cat_order'
    );
    while ($row = DB_fetchArray($res)) {
        $categoriesOrder .= (int) $row['cat_order'] . '. '
            . htmlspecialchars($row['cat_name'], ENT_QUOTES, 'UTF-8') . ' | '
            . htmlspecialchars($row['cat_url'], ENT_QUOTES, 'UTF-8')
            . '<br' . XHTML . '>';
    }
    if ($categoriesOrder === '') {
        $categoriesOrder = $LANG_DOCUMENTS_1['none'];
    }
    $template->set_var('categories_order', '<blockquote>' . $categoriesOrder . '</blockquote>');

    $template->set_var('list_index', $LANG_DOCUMENTS_1['list_index']);
    $template->set_var(
        'list_index_ckecked',
        ($cat['list_index'] === '1' || $cat['list_index'] === '') ? ' checked="checked"' : ''
    );
    $template->set_var('submitable', $LANG_DOCUMENTS_1['submitable']);
    $template->set_var(
        'submitable_ckecked',
        ($cat['submitable'] === '1' || $cat['submitable'] === '') ? ' checked="checked"' : ''
    );

    $template->set_var('cat_help_label', $LANG_DOCUMENTS_1['cat_help']);
    $template->set_var('cat_help', $cat['cat_help']);
    $template->set_var('custom_header_label', $LANG_DOCUMENTS_1['custom_header']);
    $template->set_var('custom_header', $cat['custom_header']);
    $template->set_var('custom_footer_label', $LANG_DOCUMENTS_1['custom_footer']);
    $template->set_var('custom_footer', $cat['custom_footer']);

    $options = '<select name="op"><option value="save" selected="selected">'
        . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($cat['cid'] !== '') {
        $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option>';
    }
    $options .= '</select>';
    $template->set_var('admin_options', $options);
    $template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
    $template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);

    if ($cat['perm_owner'] === '') {
        SEC_setDefaultPermissions($cat, $_DOCUMENTS_CONF['default_permissions']);
    }
    if ($cat['owner_id'] === '') {
        $cat['owner_id'] = $_USER['uid'];
    }
    if ($cat['group_id'] === '') {
        $cat['group_id'] = isset($_GROUPS['Documents Admin']) ? $_GROUPS['Documents Admin'] : 1;
    }

    $ownerName = COM_getDisplayName((int) $cat['owner_id']);
    $template->set_var('lang_accessrights', $LANG_ACCESS['accessrights']);
    $template->set_var('lang_owner', $LANG_ACCESS['owner']);
    $template->set_var(
        'owner_username',
        DB_getItem($_TABLES['users'], 'username', 'uid = ' . (int) $cat['owner_id'])
    );
    $template->set_var('owner_name', $ownerName);
    $template->set_var('owner', $ownerName);
    $template->set_var('owner_id', (int) $cat['owner_id']);
    $template->set_var('lang_group', $LANG_ACCESS['group']);
    $template->set_var('group_dropdown', SEC_getGroupDropdown((int) $cat['group_id'], 3));
    $template->set_var(
        'permissions_editor',
        SEC_getPermissionsHTML(
            $cat['perm_owner'],
            $cat['perm_group'],
            $cat['perm_members'],
            $cat['perm_anon']
        )
    );
    $template->set_var('lang_permissions', $LANG_ACCESS['permissions']);
    $template->set_var('lang_perm_key', $LANG_ACCESS['permissionskey']);
    $template->set_var('permissions_msg', $LANG_ACCESS['permmsg']);
    $template->set_var('lang_permissions_msg', $LANG_ACCESS['permmsg']);

    return $template->parse('output', 'cat');
}

function DOCUMENTS_editField($field = array())
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;
    global $LANG_ACCESS, $_USER, $_GROUPS, $_SCRIPTS;

    $defaults = array(
        'fid' => '', 'f_name' => '', 'cat_id' => '', 'f_order' => '', 'f_type' => 'text',
        'sel_id' => 0, 'var_name' => '', 'f_help' => '', 'f_required' => '',
        'f_on_list' => '', 'owner_id' => '', 'group_id' => '', 'perm_owner' => '',
        'perm_group' => '', 'perm_members' => '', 'perm_anon' => ''
    );
    $field = array_merge($defaults, is_array($field) ? $field : array());

    $_SCRIPTS->setJavaScriptLibrary('jquery');

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('field' => 'field_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
    $template->set_var('xhtml', XHTML);
    $csrfToken = SEC_createToken();
    $template->set_var('gltoken_name', CSRF_TOKEN);
    $template->set_var('gltoken', $csrfToken);
    $template->set_var(
        'field_informations',
        $field['fid'] === ''
            ? $LANG_DOCUMENTS_1['new_field']
            : $LANG_DOCUMENTS_1['edit_field'] . ' ' . $field['f_name']
    );

    $template->set_var(
        'fid',
        is_numeric($field['fid'])
            ? '<input type="hidden" name="fid" value="' . (int) $field['fid'] . '" />'
            : ''
    );
    $template->set_var('field_label', $LANG_DOCUMENTS_1['field_name']);
    $template->set_var('field_name', htmlspecialchars($field['f_name'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('categories_label', $LANG_DOCUMENTS_1['category']);

    $categoriesSelect = '<select id="cat_id" name="cat_id"><option value="0"> -- </option>';
    $res = DB_query(
        "SELECT cat_name, cid FROM {$_TABLES['documents_cat']} ORDER BY cat_order"
    );
    while ($row = DB_fetchArray($res)) {
        $selected = ((int) $row['cid'] === (int) $field['cat_id']) ? ' selected="selected"' : '';
        $categoriesSelect .= '<option value="' . (int) $row['cid'] . '"' . $selected . '>'
            . htmlspecialchars($row['cat_name'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $categoriesSelect .= '</select>';
    $template->set_var('categories_select', $categoriesSelect);

    $js = "$(document).ready(function(){"
        . "jQuery('#cat_id').change(function(){"
        . "var cat_id=jQuery(this).val();"
        . "jQuery.ajax({type:'POST',url:'" . $_CONF['site_admin_url']
        . "/plugins/documents/ajax.php',data:{action:'change_field_cat',cat_id:cat_id,"
        . json_encode(CSRF_TOKEN) . ':' . json_encode($csrfToken) . "},"
        . "dataType:'json',cache:false,success:function(result){"
        . "jQuery('input[name=f_order]').val(result.a);"
        . "jQuery('#fields_list').html(result.b);}});return false;});});";
    $_SCRIPTS->setJavaScript($js, true);

    if ($field['f_order'] === '' && $field['cat_id'] !== '') {
        $field['f_order'] = (int) DB_getItem(
            $_TABLES['documents_fields'],
            'MAX(f_order)',
            'cat_id = ' . (int) $field['cat_id']
        ) + 10;
    }
    $template->set_var('fieldorder_label', $LANG_DOCUMENTS_1['field_order']);
    $template->set_var('field_order', (int) $field['f_order']);
    $template->set_var('existing_field', $LANG_DOCUMENTS_1['existing_field']);

    $fieldsOrder = '';
    if ($field['cat_id'] !== '') {
        $res = DB_query(
            "SELECT f_order, f_name FROM {$_TABLES['documents_fields']} WHERE cat_id = "
            . (int) $field['cat_id'] . ' ORDER BY f_order'
        );
        while ($row = DB_fetchArray($res)) {
            $fieldsOrder .= (int) $row['f_order'] . '. '
                . htmlspecialchars($row['f_name'], ENT_QUOTES, 'UTF-8')
                . '<br' . XHTML . '>';
        }
    }
    if ($fieldsOrder === '') {
        $fieldsOrder = $LANG_DOCUMENTS_1['none'];
    }
    $template->set_var(
        'fields_order',
        '<div style="padding:0 20px" id="fields_list">' . $fieldsOrder . '</div>'
    );

    $template->set_var('type_label', $LANG_DOCUMENTS_1['type']);
    $template->set_var('type_select', DOCUMENTS_fieldsTypeSelect($field['f_type']));

    if ($field['f_type'] === 'text') {
        if (!function_exists('DOCUMENTS_textFormatOptions')) {
            require_once $_CONF['path'] . 'plugins/documents/presentation.php';
        }
        $isFrench = isset($_CONF['language'])
            && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
        $formatLabels = $isFrench
            ? array(
                'raw' => 'Tel que saisi',
                'lower' => 'minuscules',
                'upper' => 'MAJUSCULES',
                'sentence' => 'Première lettre en majuscule',
                'title' => 'Initiale de chaque mot en majuscule'
            )
            : array(
                'raw' => 'As entered',
                'lower' => 'lowercase',
                'upper' => 'UPPERCASE',
                'sentence' => 'First letter uppercase',
                'title' => 'Each Word Capitalized'
            );
        $template->set_var(
            'sel_label',
            $isFrench ? 'Format d’affichage du texte' : 'Text display format'
        );
        $groupSelect = DOCUMENTS_textFormatOptions($field['sel_id'], $formatLabels);
    } else {
        $template->set_var('sel_label', $LANG_DOCUMENTS_1['sel_group']);
        $groupSelect = '<select name="sel_id"><option value="0"> -- '
            . $LANG_DOCUMENTS_1['none'] . ' -- </option>';
        $res = DB_query(
            "SELECT g_name, gid FROM {$_TABLES['documents_selects_group']} ORDER BY g_name"
        );
        while ($row = DB_fetchArray($res)) {
            $selected = ((int) $row['gid'] === (int) $field['sel_id']) ? ' selected="selected"' : '';
            $groupSelect .= '<option value="' . (int) $row['gid'] . '"' . $selected . '>'
                . htmlspecialchars($row['g_name'], ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $groupSelect .= '</select>';
    }
    $template->set_var('group_select', $groupSelect);

    $template->set_var('var_label', $LANG_DOCUMENTS_1['var_name']);
    $template->set_var('var_name', htmlspecialchars($field['var_name'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('help_label', $LANG_DOCUMENTS_1['field_help']);
    $template->set_var('f_help', $field['f_help']);
    $template->set_var('f_required', $LANG_DOCUMENTS_1['field_require']);
    $template->set_var(
        'f_required_ckecked',
        ($field['f_required'] === '1' || $field['f_required'] === '') ? ' checked="checked"' : ''
    );
    $template->set_var('f_on_list', $LANG_DOCUMENTS_1['field_on_list']);
    $template->set_var(
        'f_on_list_ckecked',
        ($field['f_on_list'] === '1' || $field['f_on_list'] === '') ? ' checked="checked"' : ''
    );

    $options = '<select name="op"><option value="save" selected="selected">'
        . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($field['fid'] !== '') {
        $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option>';
    }
    $options .= '</select>';
    $template->set_var('admin_options', $options);
    $template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
    $template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);

    if ($field['perm_owner'] === '') {
        SEC_setDefaultPermissions($field, $_DOCUMENTS_CONF['default_permissions']);
    }
    if ($field['owner_id'] === '') {
        $field['owner_id'] = $_USER['uid'];
    }
    if ($field['group_id'] === '') {
        $field['group_id'] = isset($_GROUPS['Documents Admin']) ? $_GROUPS['Documents Admin'] : 1;
    }

    $ownerName = COM_getDisplayName((int) $field['owner_id']);
    $template->set_var('lang_accessrights', $LANG_ACCESS['accessrights']);
    $template->set_var('lang_owner', $LANG_ACCESS['owner']);
    $template->set_var(
        'owner_username',
        DB_getItem($_TABLES['users'], 'username', 'uid = ' . (int) $field['owner_id'])
    );
    $template->set_var('owner_name', $ownerName);
    $template->set_var('owner', $ownerName);
    $template->set_var('owner_id', (int) $field['owner_id']);
    $template->set_var('lang_group', $LANG_ACCESS['group']);
    $template->set_var('group_dropdown', SEC_getGroupDropdown((int) $field['group_id'], 3));
    $template->set_var(
        'permissions_editor',
        SEC_getPermissionsHTML(
            $field['perm_owner'],
            $field['perm_group'],
            $field['perm_members'],
            $field['perm_anon']
        )
    );
    $template->set_var('lang_permissions', $LANG_ACCESS['permissions']);
    $template->set_var('lang_perm_key', $LANG_ACCESS['permissionskey']);
    $template->set_var('permissions_msg', $LANG_ACCESS['permmsg']);
    $template->set_var('lang_permissions_msg', $LANG_ACCESS['permmsg']);

    return $template->parse('output', 'field');
}

function DOCUMENTS_editDoc($doc = array())
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;
    global $LANG_ACCESS, $_USER, $_GROUPS, $_SCRIPTS, $_MAPS_CONF;

    $defaults = array(
        'cid' => '', 'doc_url' => '', 'cat_name' => '', 'active' => 1,
        'owner_id' => '', 'group_id' => '', 'perm_owner' => '', 'perm_group' => '',
        'perm_members' => '', 'perm_anon' => '', 'v_value' => array(), 'selects' => array()
    );
    $doc = array_merge($defaults, is_array($doc) ? $doc : array());
    if (!is_array($doc['v_value'])) {
        $doc['v_value'] = array();
    }

    if ($doc['cid'] === '') {
        return 'Category is empty!';
    }

    $_SCRIPTS->setCSSFile('document_css', '/admin/plugins/documents/documents.css');
    $_SCRIPTS->setJavaScriptLibrary('jquery');

    $hasMarkerField = false;
    if (DOCUMENTS_hasMaps()) {
        $hasMarkerField = (int) DB_count(
            $_TABLES['documents_fields'],
            array('cat_id', 'f_type'),
            array((int) $doc['cid'], 'marker')
        ) > 0;
    }

    if ($hasMarkerField && isset($_MAPS_CONF['google_api_key'])) {
        $key = rawurlencode($_MAPS_CONF['google_api_key']);
        $_SCRIPTS->setJavaScript(
            '<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key='
            . $key . '"></script>',
            false,
            false
        );
    }

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('doc' => 'doc_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
    $template->set_var('xhtml', XHTML);
    $template->set_var('gltoken_name', CSRF_TOKEN);
    $template->set_var('gltoken', SEC_createToken());
    $template->set_var(
        'doc_informations',
        ($doc['doc_url'] === '' ? $LANG_DOCUMENTS_1['create_new_doc'] : $LANG_DOCUMENTS_1['edit_doc'])
        . ' > ' . $doc['cat_name']
    );
    $template->set_var(
        'doc_url_hidden',
        '<input type="hidden" name="doc_url" value="'
        . htmlspecialchars($doc['doc_url'], ENT_QUOTES, 'UTF-8') . '" />'
    );
    $template->set_var(
        'cid',
        is_numeric($doc['cid'])
            ? '<input type="hidden" name="cid" value="' . (int) $doc['cid'] . '" />'
            : ''
    );

    $raws = '';
    $sql = "SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id = "
        . (int) $doc['cid'] . ' ORDER BY f_order';
    $resFields = DB_query($sql);

    $docName = isset($doc['v_value'][0]) ? $doc['v_value'][0] : '';
    if (!defined('DOC_NAME')) {
        define('DOC_NAME', $docName);
    }

    while ($docField = DB_fetchArray($resFields)) {
        $fid = (int) $docField['fid'];
        $doc['selects'][$fid] = array('name' => array(), 'value' => array(), 'g_help' => array());

        if ($docField['f_type'] === 'select' || $docField['f_type'] === 'radio') {
            $resSelects = DB_query(
                "SELECT s.*, g.* FROM {$_TABLES['documents_selects']} AS s "
                . "LEFT JOIN {$_TABLES['documents_selects_group']} AS g ON s.s_group = g.gid "
                . 'WHERE s.s_group = ' . (int) $docField['sel_id'] . ' ORDER BY s.s_order'
            );
            while ($row = DB_fetchArray($resSelects)) {
                $doc['selects'][$fid]['name'][] = $row['s_name'];
                $doc['selects'][$fid]['value'][] = $row['s_value'];
                $doc['selects'][$fid]['g_help'][] = $row['g_help'];
                $docField['sel_name'] = $row['g_name'];
            }
        }

        $raws .= DOCUMENTS_buildRawForm($docField, $doc, $template, $fid);
    }
    $template->set_var('raws', $raws);

    $selected0 = '';
    $selected1 = '';
    $selected2 = '';
    if ((int) $doc['active'] === 0) {
        $selected0 = ' selected="selected"';
    } elseif ((int) $doc['active'] === 2) {
        $selected2 = ' selected="selected"';
    } else {
        $selected1 = ' selected="selected"';
    }

    $active = '<p><label class="document_field_edit">' . $LANG_DOCUMENTS_1['active_label']
        . '</label><select name="active">';
    if (SEC_hasRights('documents.admin')) {
        $active .= '<option value="0"' . $selected0 . '>'
            . $LANG_DOCUMENTS_1['not_active'] . '</option>';
    }
    $active .= '<option value="1"' . $selected1 . '>' . $LANG_DOCUMENTS_1['active'] . '</option>';
    if (!COM_isAnonUser()) {
        $active .= '<option value="2"' . $selected2 . '>' . $LANG_DOCUMENTS_1['draft'] . '</option>';
    }
    $active .= '</select></p>';
    $template->set_var('active', $active);

    $options = '<select name="op"><option value="save" selected="selected">'
        . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($doc['doc_url'] !== '' && SEC_hasRights('documents.admin')) {
        $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option>';
    }
    $options .= '</select>';

    $accessPerms = '';
    if (SEC_hasRights('documents.admin')) {
        if ($doc['perm_owner'] === '') {
            SEC_setDefaultPermissions($doc, $_DOCUMENTS_CONF['default_permissions']);
        }
        if ($doc['owner_id'] === '') {
            $doc['owner_id'] = $_USER['uid'];
        }
        if ($doc['group_id'] === '') {
            $doc['group_id'] = isset($_GROUPS['Documents Admin']) ? $_GROUPS['Documents Admin'] : 1;
        }

        $ownerSelect = '<select name="owner_id">';
        $result = DB_query("SELECT uid FROM {$_TABLES['users']} WHERE uid > 1 ORDER BY username");
        while ($row = DB_fetchArray($result)) {
            $selected = ((int) $doc['owner_id'] === (int) $row['uid']) ? ' selected="selected"' : '';
            $ownerSelect .= '<option value="' . (int) $row['uid'] . '"' . $selected . '>'
                . htmlspecialchars(COM_getDisplayName($row['uid']), ENT_QUOTES, 'UTF-8')
                . ' | ' . (int) $row['uid'] . '</option>';
        }
        $ownerSelect .= '</select>';

        $accessTemplate = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
        $accessTemplate->set_file(array('access' => 'access_permissions.thtml'));
        $accessTemplate->set_var('lang_accessrights', $LANG_ACCESS['accessrights']);
        $accessTemplate->set_var('lang_owner', $LANG_ACCESS['owner']);
        $accessTemplate->set_var('owner_select', $ownerSelect);
        $accessTemplate->set_var('owner', COM_getDisplayName((int) $doc['owner_id']));
        $accessTemplate->set_var('lang_group', $LANG_ACCESS['group']);
        $accessTemplate->set_var('group_dropdown', SEC_getGroupDropdown((int) $doc['group_id'], 3));
        $accessTemplate->set_var(
            'permissions_editor',
            SEC_getPermissionsHTML(
                $doc['perm_owner'],
                $doc['perm_group'],
                $doc['perm_members'],
                $doc['perm_anon']
            )
        );
        $accessTemplate->set_var('lang_permissions', $LANG_ACCESS['permissions']);
        $accessTemplate->set_var('lang_perm_key', $LANG_ACCESS['permissionskey']);
        $accessTemplate->set_var('permissions_msg', $LANG_ACCESS['permmsg']);
        $accessTemplate->set_var('lang_permissions_msg', $LANG_ACCESS['permmsg']);
        $accessTemplate->parse('access_perms', 'access');
        $accessPerms = $accessTemplate->finish($accessTemplate->get_var('access_perms'));
    }

    $template->set_var('access_perms', $accessPerms);
    $template->set_var('admin_options', $options);
    $template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);
    $template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);

    $template->parse('output', 'doc');
    $retval = $template->finish($template->get_var('output'));
    if (trim((string) $retval) === '') {
        COM_errorLog('Documents: doc_form.thtml rendered an empty result for category ' . (int) $doc['cid']);
        return '<div class="pluginAlert">Documents: the document form template returned no output.</div>';
    }

    return $retval;
}

function DOCUMENTS_buildRawForm($field, $doc, &$template, $i)
{
    global $_CONF, $_DOCUMENTS_CONF, $LANG_MAPS_1, $_SCRIPTS, $_TABLES;

    $value = isset($doc['v_value'][$i]) ? $doc['v_value'][$i] : '';
    $required = !empty($field['f_required']) ? '<span class="documents_required"> *</span>' : '';
    $help = !empty($field['f_help'])
        ? ' <div class="documents_help">' . $field['f_help'] . '</div>'
        : '';
    $name = htmlspecialchars($field['var_name'], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars($field['f_name'], ENT_QUOTES, 'UTF-8');
    $html = '<div>' . LB;

    switch ($field['f_type']) {
        case 'checkbox':
            $checked = ((int) $value === 1) ? ' checked="checked"' : '';
            $html .= '<p><label class="document_field_edit">&nbsp;</label>'
                . '<input type="checkbox" value="1" name="' . $name . '"' . $checked . XHTML . '>'
                . $label . $help . '</p>' . LB;
            break;

        case 'select':
            $selects = isset($doc['selects'][$i]) ? $doc['selects'][$i] : array();
            $names = isset($selects['name']) && is_array($selects['name']) ? $selects['name'] : array();
            $values = isset($selects['value']) && is_array($selects['value']) ? $selects['value'] : array();
            $helps = isset($selects['g_help']) && is_array($selects['g_help']) ? $selects['g_help'] : array();
            $selectLabel = isset($field['sel_name']) ? $field['sel_name'] : $field['f_name'];
            $html .= '<p><label class="document_field_edit">'
                . htmlspecialchars($selectLabel, ENT_QUOTES, 'UTF-8') . '</label>';
            $html .= '<select name="' . $name . '">';
            if (!empty($helps[0])) {
                $html .= '<option value="0"> -- '
                    . htmlspecialchars($helps[0], ENT_QUOTES, 'UTF-8') . ' -- </option>';
            }
            foreach ($names as $index => $optionName) {
                $selected = ((string) $value === (string) $optionName) ? ' selected="selected"' : '';
                $caption = isset($values[$index]) ? $values[$index] : $optionName;
                $html .= '<option value="' . htmlspecialchars($optionName, ENT_QUOTES, 'UTF-8') . '"'
                    . $selected . '>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</option>';
            }
            $html .= '</select>' . $help . '</p>' . LB;
            break;

        case 'textarea':
            $html .= '<p><label class="document_field_edit">' . $label . $required . '</label>'
                . '<textarea style="width:70%;height:300px" class="document_field_edit_textarea" name="'
                . $name . '">' . htmlspecialchars(stripslashes($value), ENT_QUOTES, 'UTF-8')
                . '</textarea>' . $help . '</p>' . LB;
            break;

        case 'decimal':
        case 'date':
        case 'text':
            $html .= '<p><label class="document_field_edit">' . $label . $required . '</label>'
                . '<input class="document_field_edit_text" type="text" name="' . $name
                . '" value="' . htmlspecialchars(stripslashes($value), ENT_QUOTES, 'UTF-8')
                . '" size="100" maxlength="255" ' . XHTML . '>' . $help . '</p>' . LB;
            break;

        case 'album':
            if (DOCUMENTS_hasMediaGallery()) {
                $common = $_CONF['path'] . 'plugins/mediagallery/include/common.php';
                $classAlbum = $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
                if (is_file($common) && is_file($classAlbum)) {
                    require_once $common;
                    require_once $classAlbum;
                    if (class_exists('mgAlbum')) {
                        $rootAlbum = new mgAlbum(0);
                        $albumJumpbox = '<select name="' . $name . '"><option value="">----</option>';
                        $rootAlbum->buildJumpBox($albumJumpbox, 0, 1, -1);
                        $albumJumpbox .= '</select>';
                        $html .= '<p><label class="document_field_edit">' . $label . $required
                            . '</label>' . $albumJumpbox . '</p>';
                    }
                }
            }
            break;

        case 'image':
            $html .= '<p><label class="document_field_edit">' . $label . $required
                . '</label><div class="document_field_edit_right">';
            if ($value !== '' && is_file($_DOCUMENTS_CONF['path_images'] . $value)) {
                $html .= '<img src="' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src='
                    . rawurlencode($value) . '&amp;w=450" alt="'
                    . htmlspecialchars(defined('DOC_NAME') ? DOC_NAME : '', ENT_QUOTES, 'UTF-8')
                    . '" /><br' . XHTML . '><br' . XHTML . '>';
            }
            $html .= '<input type="file" dir="ltr" name="file' . (int) $field['fid'] . '"'
                . XHTML . '></div></p>' . LB;
            break;

        case 'marker':
            if (DOCUMENTS_hasMaps()) {
                $markerId = (int) $value;
                $marker = array('lat' => '', 'lng' => '', 'address' => '', 'name' => '');
                if ($markerId > 0 && isset($_TABLES['maps_markers'])) {
                    $res = DB_query(
                        "SELECT * FROM {$_TABLES['maps_markers']} WHERE mkid = " . $markerId
                    );
                    $row = DB_fetchArray($res);
                    if (is_array($row)) {
                        $marker = array_merge($marker, $row);
                    }
                }
                if ($marker['lat'] === '') {
                    $marker['lat'] = '37.4217913';
                    $marker['lng'] = '-122.0837139';
                    $marker['address'] = '';
                }

                $t = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
                $t->set_file(array('marker' => 'marker_form.thtml'));
                $t->set_var('go', isset($LANG_MAPS_1['go']) ? $LANG_MAPS_1['go'] : 'Go');
                $t->set_var('address', htmlspecialchars($marker['address'], ENT_QUOTES, 'UTF-8'));
                $t->set_var('lat_value', $marker['lat']);
                $t->set_var('lng_value', $marker['lng']);
                $t->set_var('var_name', $name);
                $t->set_var('mkid', $markerId);

                $js = "var geocoder=new google.maps.Geocoder(),map,infowindow,markers=[];"
                    . "function initializeGMap(){var p=new google.maps.LatLng("
                    . (float) $marker['lat'] . ',' . (float) $marker['lng'] . ");"
                    . "map=new google.maps.Map(document.getElementById('map_canvas'),"
                    . "{center:p,zoom:10,mapTypeId:google.maps.MapTypeId.ROADMAP});"
                    . "var m=new google.maps.Marker({map:map,position:p,draggable:true});markers.push(m);"
                    . "google.maps.event.addListener(m,'dragend',function(e){"
                    . "document.getElementById('lat').value=e.latLng.lat().toFixed(6);"
                    . "document.getElementById('lng').value=e.latLng.lng().toFixed(6);});}"
                    . "function codeAddress(){var a=document.getElementById('geoaddress').value;"
                    . "geocoder.geocode({address:a},function(r,s){if(s===google.maps.GeocoderStatus.OK){"
                    . "map.setCenter(r[0].geometry.location);document.getElementById('lat').value="
                    . "r[0].geometry.location.lat();document.getElementById('lng').value="
                    . "r[0].geometry.location.lng();}});}"
                    . "function copyText(){document.getElementById('address').value="
                    . "document.getElementById('geoaddress').value;}"
                    . "google.maps.event.addDomListener(window,'load',initializeGMap);";
                $_SCRIPTS->setJavaScript($js, true);

                $html .= '<p><label class="document_field_edit">' . $label . $required
                    . '</label><div class="document_field_edit_right">'
                    . $t->parse('marker_field', 'marker') . '</div></p>';
            }
            break;

        case 'category':
        case 'file':
        case 'radio':
        default:
            break;
    }

    $html .= '</div>' . LB;
    return $html;
}

function DOCUMENTS_editGroup($group = array())
{
    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1;

    $defaults = array('gid' => '', 'g_name' => '', 'g_help' => '');
    $group = array_merge($defaults, is_array($group) ? $group : array());

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('group' => 'group_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
    $template->set_var('xhtml', XHTML);
    $csrfToken = SEC_createToken();
    $template->set_var('gltoken_name', CSRF_TOKEN);
    $template->set_var('gltoken', $csrfToken);
    $template->set_var(
        'group_informations',
        $group['gid'] === ''
            ? $LANG_DOCUMENTS_1['new_group']
            : $LANG_DOCUMENTS_1['edit_group'] . ' ' . $group['g_name']
    );
    $template->set_var(
        'gid',
        is_numeric($group['gid'])
            ? '<input type="hidden" name="gid" value="' . (int) $group['gid'] . '" />'
            : ''
    );
    $template->set_var('group_label', $LANG_DOCUMENTS_1['group_name']);
    $template->set_var('group_name', htmlspecialchars($group['g_name'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('help_label', $LANG_DOCUMENTS_1['group_help']);
    $template->set_var('help', $group['g_help']);

    $options = '<select name="op"><option value="save" selected="selected">'
        . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($group['gid'] !== '') {
        $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option>';
    }
    $options .= '</select>';
    $template->set_var('admin_options', $options);
    $template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
    $template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);

    return $template->parse('output', 'group');
}

function DOCUMENTS_editSelect($select = array())
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1, $_SCRIPTS;

    $defaults = array(
        'sid' => '', 's_name' => '', 's_value' => '', 's_group' => '', 's_order' => ''
    );
    $select = array_merge($defaults, is_array($select) ? $select : array());

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('select' => 'select_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
    $template->set_var('xhtml', XHTML);
    $csrfToken = SEC_createToken();
    $template->set_var('gltoken_name', CSRF_TOKEN);
    $template->set_var('gltoken', $csrfToken);
    $template->set_var(
        'select_informations',
        $select['sid'] === ''
            ? $LANG_DOCUMENTS_1['new_option']
            : $LANG_DOCUMENTS_1['edit_select'] . ' ' . $select['s_name']
    );
    $template->set_var(
        'sid',
        is_numeric($select['sid'])
            ? '<input type="hidden" name="sid" value="' . (int) $select['sid'] . '" />'
            : ''
    );
    $template->set_var('select_label', $LANG_DOCUMENTS_1['select_name']);
    $template->set_var('select_name', htmlspecialchars($select['s_name'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('value_label', $LANG_DOCUMENTS_1['select_value']);
    $template->set_var('value', htmlspecialchars($select['s_value'], ENT_QUOTES, 'UTF-8'));
    $template->set_var('group_label', $LANG_DOCUMENTS_1['sel_group']);

    $groupSelect = '<select id="s_group" name="s_group"><option value="0"> -- </option>';
    $res = DB_query("SELECT g_name, gid FROM {$_TABLES['documents_selects_group']} ORDER BY g_name");
    while ($row = DB_fetchArray($res)) {
        $selected = ((int) $row['gid'] === (int) $select['s_group']) ? ' selected="selected"' : '';
        $groupSelect .= '<option value="' . (int) $row['gid'] . '"' . $selected . '>'
            . htmlspecialchars($row['g_name'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $groupSelect .= '</select>';
    $template->set_var('group_select', $groupSelect);

    $js = "$(document).ready(function(){jQuery('#s_group').change(function(){"
        . "var g=jQuery(this).val();jQuery.ajax({type:'POST',url:'"
        . $_CONF['site_admin_url'] . "/plugins/documents/ajax.php',"
        . "data:{action:'change_select_group',s_group:g,"
        . json_encode(CSRF_TOKEN) . ':' . json_encode($csrfToken) . "},dataType:'json',cache:false,"
        . "success:function(r){jQuery('input[name=s_order]').val(r.a);"
        . "jQuery('#groups_list').html(r.b);}});return false;});});";
    $_SCRIPTS->setJavaScript($js, true);

    if ($select['s_order'] === '' && $select['s_group'] !== '') {
        $select['s_order'] = (int) DB_getItem(
            $_TABLES['documents_selects'],
            'MAX(s_order)',
            's_group = ' . (int) $select['s_group']
        ) + 10;
    }
    $template->set_var('s_order_label', $LANG_DOCUMENTS_1['select_order']);
    $template->set_var('s_order', (int) $select['s_order']);
    $template->set_var('existing_select', $LANG_DOCUMENTS_1['existing_select']);

    $selectOrder = '';
    if ($select['s_group'] !== '') {
        $res = DB_query(
            "SELECT s_order, s_name FROM {$_TABLES['documents_selects']} WHERE s_group = "
            . (int) $select['s_group'] . ' ORDER BY s_order'
        );
        while ($row = DB_fetchArray($res)) {
            $selectOrder .= (int) $row['s_order'] . '. '
                . htmlspecialchars($row['s_name'], ENT_QUOTES, 'UTF-8')
                . '<br' . XHTML . '>';
        }
    }
    if ($selectOrder === '') {
        $selectOrder = $LANG_DOCUMENTS_1['none'];
    }
    $template->set_var(
        'select_order',
        '<div style="padding:0 20px" id="groups_list">' . $selectOrder . '</div>'
    );

    $options = '<select name="op"><option value="save" selected="selected">'
        . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($select['sid'] !== '') {
        $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option>';
    }
    $options .= '</select>';
    $template->set_var('admin_options', $options);
    $template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
    $template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);

    return $template->parse('output', 'select');
}

function DOCUMENTS_fieldsTypeSelect($type)
{
    $types = array(
        'text' => 'text',
        'textarea' => 'textarea',
        'decimal' => 'decimal',
        'date' => 'date',
        'image' => 'image',
        'checkbox' => 'checkbox',
        'select' => 'select',
        'category' => 'category'
    );

    if (DOCUMENTS_hasMaps()) {
        $types['marker'] = 'marker';
    }
    if (DOCUMENTS_hasMediaGallery()) {
        $types['album'] = 'mediagallery album';
    }

    $html = '<select name="f_type">';
    foreach ($types as $value => $label) {
        $selected = ($type === $value) ? ' selected="selected"' : '';
        $html .= '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
    }
    $html .= '</select>';

    return $html;
}

?>