<?php

/* Standalone category editor used by Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'admin_category_editor.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_renderCategoryEditor($categoryId)
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;
    global $LANG_ACCESS, $_USER, $_GROUPS;

    $categoryId = (int) $categoryId;
    $category = array(
        'cid' => '',
        'cat_name' => '',
        'cat_url' => '',
        'css' => '',
        'map' => 0,
        'template' => '',
        'cat_order' => '',
        'list_index' => 1,
        'submitable' => 1,
        'cat_help' => '',
        'metadescription' => '',
        'custom_header' => '',
        'custom_footer' => '',
        'owner_id' => '',
        'group_id' => '',
        'perm_owner' => '',
        'perm_group' => '',
        'perm_members' => '',
        'perm_anon' => ''
    );

    if ($categoryId > 0) {
        $row = DB_fetchArray(DB_query(
            "SELECT * FROM {$_TABLES['documents_cat']} WHERE cid={$categoryId} LIMIT 1"
        ));
        if (!is_array($row) || empty($row['cid'])) {
            return COM_createHTMLDocument(
                '<p>' . htmlspecialchars(isset($LANG_DOCUMENTS_1['error']) ? $LANG_DOCUMENTS_1['error'] : 'Error', ENT_QUOTES, 'UTF-8') . '</p>',
                array('pagetitle' => isset($LANG_DOCUMENTS_1['error']) ? $LANG_DOCUMENTS_1['error'] : 'Error')
            );
        }
        $category = array_merge($category, $row);
    }

    if ($category['cat_order'] === '') {
        $category['cat_order'] = (int) DB_getItem(
            $_TABLES['documents_cat'],
            'MAX(cat_order)',
            '1=1'
        ) + 10;
    }

    if ($category['perm_owner'] === '') {
        SEC_setDefaultPermissions($category, $_DOCUMENTS_CONF['default_permissions']);
    }
    if ($category['owner_id'] === '') {
        $category['owner_id'] = isset($_USER['uid']) ? (int) $_USER['uid'] : 1;
    }
    if ($category['group_id'] === '') {
        $category['group_id'] = isset($_GROUPS['Documents Admin'])
            ? (int) $_GROUPS['Documents Admin'] : 1;
    }

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('cat' => 'cat_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
    $template->set_var('xhtml', XHTML);

    $token = SEC_createToken();
    $template->set_var('gltoken_name', CSRF_TOKEN);
    $template->set_var('gltoken', $token);

    $template->set_var(
        'cat_informations',
        $categoryId > 0
            ? $LANG_DOCUMENTS_1['edit_cat'] . ' ' . htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8')
            : $LANG_DOCUMENTS_1['new_cat']
    );
    $template->set_var(
        'cid',
        $categoryId > 0
            ? '<input type="hidden" name="cid" value="' . $categoryId . '"' . XHTML . '>'
            : ''
    );

    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
    $template->set_var('metadescription_label', $isFrench ? 'Méta description (SEO)' : 'Meta description (SEO)');
    $template->set_var(
        'metadescription_help',
        $isFrench
            ? 'Description dédiée aux moteurs de recherche. Recommandation : un texte concis, unique et utile.'
            : 'Dedicated search-engine description. Recommendation: keep it concise, unique and useful.'
    );

    $vars = array(
        'category_label' => $LANG_DOCUMENTS_1['cat_name'],
        'category' => htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8'),
        'cat_url_label' => $LANG_DOCUMENTS_1['cat_url'],
        'cat_url' => htmlspecialchars((string) $category['cat_url'], ENT_QUOTES, 'UTF-8'),
        'metadescription' => htmlspecialchars(stripslashes((string) $category['metadescription']), ENT_QUOTES, 'UTF-8'),
        'cat_help_label' => $LANG_DOCUMENTS_1['cat_help'],
        'cat_help' => htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8'),
        'template_label' => $LANG_DOCUMENTS_1['template'],
        'template' => htmlspecialchars((string) $category['template'], ENT_QUOTES, 'UTF-8'),
        'css_label' => $LANG_DOCUMENTS_1['css'],
        'css' => htmlspecialchars((string) $category['css'], ENT_QUOTES, 'UTF-8'),
        'custom_header_label' => $LANG_DOCUMENTS_1['custom_header'],
        'custom_header' => htmlspecialchars(stripslashes((string) $category['custom_header']), ENT_QUOTES, 'UTF-8'),
        'custom_footer_label' => $LANG_DOCUMENTS_1['custom_footer'],
        'custom_footer' => htmlspecialchars(stripslashes((string) $category['custom_footer']), ENT_QUOTES, 'UTF-8'),
        'catorder_label' => $LANG_DOCUMENTS_1['cat_order'],
        'cat_order' => (int) $category['cat_order'],
        'existing_cat' => $LANG_DOCUMENTS_1['existing_cat'],
        'list_index' => $LANG_DOCUMENTS_1['list_index'],
        'list_index_ckecked' => (int) $category['list_index'] === 1 ? ' checked="checked"' : '',
        'submitable' => $LANG_DOCUMENTS_1['submitable'],
        'submitable_ckecked' => (int) $category['submitable'] === 1 ? ' checked="checked"' : '',
        'validate_button' => $LANG_DOCUMENTS_1['validate_button'],
        'required_field' => $LANG_DOCUMENTS_1['required_field']
    );
    foreach ($vars as $key => $value) {
        $template->set_var($key, $value);
    }

    $categoriesOrder = '';
    $result = DB_query(
        "SELECT cat_order, cat_name, cat_url FROM {$_TABLES['documents_cat']} ORDER BY cat_order ASC, cid ASC"
    );
    while ($row = DB_fetchArray($result)) {
        $categoriesOrder .= (int) $row['cat_order'] . '. '
            . htmlspecialchars(stripslashes((string) $row['cat_name']), ENT_QUOTES, 'UTF-8')
            . ' | ' . htmlspecialchars((string) $row['cat_url'], ENT_QUOTES, 'UTF-8')
            . '<br' . XHTML . '>';
    }
    if ($categoriesOrder === '') {
        $categoriesOrder = htmlspecialchars($LANG_DOCUMENTS_1['none'], ENT_QUOTES, 'UTF-8');
    }
    $template->set_var('categories_order', '<blockquote>' . $categoriesOrder . '</blockquote>');

    if (DOCUMENTS_hasMaps() && function_exists('MAPS_recurseMaps')) {
        $map = '<div><p><label>' . htmlspecialchars($LANG_DOCUMENTS_1['use_map'], ENT_QUOTES, 'UTF-8') . '</label> '
            . '<select id="map" name="map"><option value="0">'
            . htmlspecialchars($LANG_DOCUMENTS_1['no_map'], ENT_QUOTES, 'UTF-8') . '</option>'
            . MAPS_recurseMaps((int) $category['map']) . '</select><br' . XHTML . '>'
            . htmlspecialchars($LANG_DOCUMENTS_1['use_map_details'], ENT_QUOTES, 'UTF-8') . '</p></div>';
        $template->set_var('map', $map);
    } else {
        $template->set_var('map', '');
    }

    $options = '<select name="op"><option value="save" selected="selected">'
        . htmlspecialchars($LANG_DOCUMENTS_1['save_button'], ENT_QUOTES, 'UTF-8') . '</option>';
    if ($categoryId > 0) {
        $options .= '<option value="delete">'
            . htmlspecialchars($LANG_DOCUMENTS_1['delete_button'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $options .= '</select>';
    $template->set_var('admin_options', $options);

    $ownerName = COM_getDisplayName((int) $category['owner_id']);
    $template->set_var('lang_owner', $LANG_ACCESS['owner']);
    $template->set_var('owner_name', htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'));
    $template->set_var('owner_id', (int) $category['owner_id']);
    $template->set_var('lang_group', $LANG_ACCESS['group']);
    $template->set_var('group_dropdown', SEC_getGroupDropdown((int) $category['group_id'], 3));
    $template->set_var(
        'permissions_editor',
        SEC_getPermissionsHTML(
            $category['perm_owner'],
            $category['perm_group'],
            $category['perm_members'],
            $category['perm_anon']
        )
    );
    $template->set_var('lang_perm_key', $LANG_ACCESS['permissionskey']);
    $template->set_var('lang_permissions_msg', $LANG_ACCESS['permmsg']);

    $content = $template->parse('output', 'cat');
    $pageTitle = $categoryId > 0 ? $LANG_DOCUMENTS_1['edit_cat'] : $LANG_DOCUMENTS_1['new_cat'];

    return COM_createHTMLDocument($content, array('pagetitle' => $pageTitle));
}
