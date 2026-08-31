<?php

/* Public document form renderer. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'public_form.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_renderPublicDocumentForm($doc = array())
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
    if ((int) $doc['cid'] <= 0) {
        return '';
    }

    if (isset($_SCRIPTS) && is_object($_SCRIPTS)) {
        $_SCRIPTS->setJavaScriptLibrary('jquery');
    }

    $hasMarkerField = false;
    if (DOCUMENTS_hasMaps()) {
        $hasMarkerField = (int) DB_count(
            $_TABLES['documents_fields'],
            array('cat_id', 'f_type'),
            array((int) $doc['cid'], 'marker')
        ) > 0;
    }

    if ($hasMarkerField && isset($_MAPS_CONF['google_api_key'])
        && isset($_SCRIPTS) && is_object($_SCRIPTS)) {
        $key = rawurlencode((string) $_MAPS_CONF['google_api_key']);
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
        . ' > ' . htmlspecialchars(stripslashes((string) $doc['cat_name']), ENT_QUOTES, 'UTF-8')
    );
    $template->set_var(
        'doc_url_hidden',
        '<input type="hidden" name="doc_url" value="'
        . htmlspecialchars((string) $doc['doc_url'], ENT_QUOTES, 'UTF-8') . '"' . XHTML . '>'
    );
    $template->set_var(
        'cid',
        '<input type="hidden" name="cid" value="' . (int) $doc['cid'] . '"' . XHTML . '>'
    );

    $raws = '';
    $fields = DB_query(
        "SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id="
        . (int) $doc['cid'] . ' ORDER BY f_order ASC, fid ASC'
    );
    while ($field = DB_fetchArray($fields)) {
        if (!is_array($field) || empty($field['fid'])) {
            continue;
        }
        $fid = (int) $field['fid'];
        $doc['selects'][$fid] = array('name' => array(), 'value' => array(), 'g_help' => array());

        if ($field['f_type'] === 'select' || $field['f_type'] === 'radio') {
            $selects = DB_query(
                "SELECT s.*, g.g_name, g.g_help FROM {$_TABLES['documents_selects']} AS s "
                . "LEFT JOIN {$_TABLES['documents_selects_group']} AS g ON g.gid=s.s_group "
                . 'WHERE s.s_group=' . (int) $field['sel_id'] . ' ORDER BY s.s_order ASC, s.sid ASC'
            );
            while ($row = DB_fetchArray($selects)) {
                $doc['selects'][$fid]['name'][] = isset($row['s_name']) ? $row['s_name'] : '';
                $displayValue = isset($row['s_value']) ? trim((string) $row['s_value']) : '';
                $doc['selects'][$fid]['value'][] = $displayValue !== ''
                    ? $displayValue : (isset($row['s_name']) ? $row['s_name'] : '');
                $doc['selects'][$fid]['g_help'][] = isset($row['g_help']) ? $row['g_help'] : '';
                if (!empty($row['g_name'])) {
                    $field['sel_name'] = $row['g_name'];
                }
            }
        }

        $raws .= DOCUMENTS_renderPublicFormField($field, $doc, $fid);
    }
    $template->set_var('raws', $raws);

    $selected0 = ((int) $doc['active'] === 0) ? ' selected="selected"' : '';
    $selected1 = ((int) $doc['active'] !== 0 && (int) $doc['active'] !== 2)
        ? ' selected="selected"' : '';
    $selected2 = ((int) $doc['active'] === 2) ? ' selected="selected"' : '';

    $active = '<p><label class="document_field_edit">'
        . htmlspecialchars($LANG_DOCUMENTS_1['active_label'], ENT_QUOTES, 'UTF-8')
        . '</label><select name="active">';
    if (SEC_hasRights('documents.admin')) {
        $active .= '<option value="0"' . $selected0 . '>'
            . htmlspecialchars($LANG_DOCUMENTS_1['not_active'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $active .= '<option value="1"' . $selected1 . '>'
        . htmlspecialchars($LANG_DOCUMENTS_1['active'], ENT_QUOTES, 'UTF-8') . '</option>';
    if (!COM_isAnonUser()) {
        $active .= '<option value="2"' . $selected2 . '>'
            . htmlspecialchars($LANG_DOCUMENTS_1['draft'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $active .= '</select></p>';
    $template->set_var('active', $active);

    $options = '<select name="op"><option value="save" selected="selected">'
        . htmlspecialchars($LANG_DOCUMENTS_1['save_button'], ENT_QUOTES, 'UTF-8') . '</option>';
    if ($doc['doc_url'] !== '' && SEC_hasRights('documents.admin')) {
        $options .= '<option value="delete">'
            . htmlspecialchars($LANG_DOCUMENTS_1['delete_button'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $options .= '</select>';
    $template->set_var('admin_options', $options);

    $accessPerms = '';
    if (SEC_hasRights('documents.admin')) {
        if ($doc['perm_owner'] === '') {
            SEC_setDefaultPermissions($doc, $_DOCUMENTS_CONF['default_permissions']);
        }
        if ($doc['owner_id'] === '') {
            $doc['owner_id'] = isset($_USER['uid']) ? (int) $_USER['uid'] : 1;
        }
        if ($doc['group_id'] === '') {
            $doc['group_id'] = isset($_GROUPS['Documents Admin']) ? (int) $_GROUPS['Documents Admin'] : 1;
        }

        $ownerSelect = '<select name="owner_id">';
        $users = DB_query("SELECT uid FROM {$_TABLES['users']} WHERE uid>1 ORDER BY username");
        while ($user = DB_fetchArray($users)) {
            $selected = ((int) $doc['owner_id'] === (int) $user['uid'])
                ? ' selected="selected"' : '';
            $ownerSelect .= '<option value="' . (int) $user['uid'] . '"' . $selected . '>'
                . htmlspecialchars(COM_getDisplayName((int) $user['uid']), ENT_QUOTES, 'UTF-8')
                . ' | ' . (int) $user['uid'] . '</option>';
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
    $template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);
    $template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);

    $template->parse('output', 'doc');
    return $template->finish($template->get_var('output'));
}

function DOCUMENTS_renderPublicFormField($field, $doc, $fid)
{
    global $_CONF, $_DOCUMENTS_CONF, $_SCRIPTS, $_TABLES, $LANG_MAPS_1;

    $value = isset($doc['v_value'][$fid]) ? $doc['v_value'][$fid] : '';
    $required = !empty($field['f_required']) ? '<span class="documents_required"> *</span>' : '';
    $help = !empty($field['f_help'])
        ? '<div class="documents_help">' . (string) $field['f_help'] . '</div>' : '';
    $name = htmlspecialchars((string) $field['var_name'], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars((string) $field['f_name'], ENT_QUOTES, 'UTF-8');
    $html = '<div class="documents-form-field documents-form-field--'
        . htmlspecialchars((string) $field['f_type'], ENT_QUOTES, 'UTF-8') . '">';

    switch ((string) $field['f_type']) {
        case 'checkbox':
            $checked = ((int) $value === 1) ? ' checked="checked"' : '';
            $html .= '<p><label><input type="checkbox" value="1" name="' . $name . '"'
                . $checked . XHTML . '> ' . $label . $required . '</label>' . $help . '</p>';
            break;

        case 'select':
            $selects = isset($doc['selects'][$fid]) ? $doc['selects'][$fid] : array();
            $names = isset($selects['name']) && is_array($selects['name']) ? $selects['name'] : array();
            $values = isset($selects['value']) && is_array($selects['value']) ? $selects['value'] : array();
            $helps = isset($selects['g_help']) && is_array($selects['g_help']) ? $selects['g_help'] : array();
            $selectLabel = isset($field['sel_name']) && trim((string) $field['sel_name']) !== ''
                ? $field['sel_name'] : $field['f_name'];
            $html .= '<p><label class="document_field_edit">'
                . htmlspecialchars((string) $selectLabel, ENT_QUOTES, 'UTF-8') . $required
                . '</label><select name="' . $name . '">';
            if (!empty($helps[0])) {
                $html .= '<option value="0"> -- '
                    . htmlspecialchars((string) $helps[0], ENT_QUOTES, 'UTF-8') . ' -- </option>';
            }
            foreach ($names as $index => $optionName) {
                $selected = ((string) $value === (string) $optionName)
                    ? ' selected="selected"' : '';
                $caption = isset($values[$index]) && trim((string) $values[$index]) !== ''
                    ? $values[$index] : $optionName;
                $html .= '<option value="' . htmlspecialchars((string) $optionName, ENT_QUOTES, 'UTF-8') . '"'
                    . $selected . '>' . htmlspecialchars((string) $caption, ENT_QUOTES, 'UTF-8') . '</option>';
            }
            $html .= '</select>' . $help . '</p>';
            break;

        case 'radio':
            $selects = isset($doc['selects'][$fid]) ? $doc['selects'][$fid] : array();
            $names = isset($selects['name']) && is_array($selects['name']) ? $selects['name'] : array();
            $values = isset($selects['value']) && is_array($selects['value']) ? $selects['value'] : array();
            $html .= '<fieldset class="documents-radio-group"><legend>' . $label . $required . '</legend>';
            foreach ($names as $index => $optionName) {
                $checked = ((string) $value === (string) $optionName) ? ' checked="checked"' : '';
                $caption = isset($values[$index]) && trim((string) $values[$index]) !== ''
                    ? $values[$index] : $optionName;
                $html .= '<label><input type="radio" name="' . $name . '" value="'
                    . htmlspecialchars((string) $optionName, ENT_QUOTES, 'UTF-8') . '"' . $checked . XHTML . '> '
                    . htmlspecialchars((string) $caption, ENT_QUOTES, 'UTF-8') . '</label> ';
            }
            $html .= $help . '</fieldset>';
            break;

        case 'textarea':
            $html .= '<p><label class="document_field_edit">' . $label . $required . '</label>'
                . '<textarea class="document_field_edit_textarea" name="' . $name . '">'
                . htmlspecialchars(stripslashes((string) $value), ENT_QUOTES, 'UTF-8')
                . '</textarea>' . $help . '</p>';
            break;

        case 'decimal':
        case 'date':
        case 'text':
            $html .= '<p><label class="document_field_edit">' . $label . $required . '</label>'
                . '<input class="document_field_edit_text" type="text" name="' . $name . '" value="'
                . htmlspecialchars(stripslashes((string) $value), ENT_QUOTES, 'UTF-8')
                . '" maxlength="255"' . XHTML . '>' . $help . '</p>';
            break;

        case 'album':
            if (function_exists('DOCUMENTS_mediaGalleryAlbumSelect')) {
                $album = DOCUMENTS_mediaGalleryAlbumSelect($field['var_name'], $value);
                if ($album !== '') {
                    $html .= '<p><label class="document_field_edit">' . $label . $required
                        . '</label>' . $album . $help . '</p>';
                }
            }
            break;

        case 'image':
            $html .= '<p><label class="document_field_edit">' . $label . $required
                . '</label><span class="document_field_edit_right">';
            if ($value !== '' && is_file($_DOCUMENTS_CONF['path_images'] . $value)) {
                $html .= '<img class="documents-document-image" src="'
                    . htmlspecialchars($_DOCUMENTS_CONF['site_url'] . '/image.php?src='
                    . rawurlencode((string) $value) . '&w=450', ENT_QUOTES, 'UTF-8') . '" alt=""' . XHTML . '>';
            }
            $html .= '<input type="file" name="file' . (int) $field['fid'] . '"' . XHTML . '>'
                . '</span>' . $help . '</p>';
            break;

        case 'marker':
            if (DOCUMENTS_hasMaps()) {
                $markerId = trim((string) $value);
                $marker = array('lat' => '37.4217913', 'lng' => '-122.0837139', 'address' => '');
                if ($markerId !== '' && isset($_TABLES['maps_markers'])) {
                    $markerSql = DB_escapeString($markerId);
                    $res = DB_query(
                        "SELECT * FROM {$_TABLES['maps_markers']} WHERE mkid='{$markerSql}' LIMIT 1"
                    );
                    $row = DB_fetchArray($res);
                    if (is_array($row)) {
                        $marker = array_merge($marker, $row);
                    }
                }
                $markerTemplate = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
                $markerTemplate->set_file(array('marker' => 'marker_form.thtml'));
                $markerTemplate->set_var('go', isset($LANG_MAPS_1['go']) ? $LANG_MAPS_1['go'] : 'Go');
                $markerTemplate->set_var('address', htmlspecialchars((string) $marker['address'], ENT_QUOTES, 'UTF-8'));
                $markerTemplate->set_var('lat_value', (string) $marker['lat']);
                $markerTemplate->set_var('lng_value', (string) $marker['lng']);
                $markerTemplate->set_var('var_name', $name);
                $markerTemplate->set_var('mkid', htmlspecialchars($markerId, ENT_QUOTES, 'UTF-8'));

                if (isset($_SCRIPTS) && is_object($_SCRIPTS)) {
                    $js = "var map=null,documentMarker=null;"
                        . "function documentsMapsAvailable(){return typeof google!=='undefined'&&google.maps;}"
                        . "function initializeGMap(){if(!documentsMapsAvailable()){return false;}"
                        . "var c=document.getElementById('map_canvas');if(!c){return false;}"
                        . "var p=new google.maps.LatLng(" . (float) $marker['lat'] . ',' . (float) $marker['lng'] . ");"
                        . "map=new google.maps.Map(c,{center:p,zoom:10,mapTypeId:google.maps.MapTypeId.ROADMAP});"
                        . "documentMarker=new google.maps.Marker({map:map,position:p,draggable:true});"
                        . "google.maps.event.addListener(documentMarker,'dragend',function(e){"
                        . "document.getElementById('lat').value=e.latLng.lat().toFixed(6);"
                        . "document.getElementById('lng').value=e.latLng.lng().toFixed(6);});return true;}"
                        . "function codeAddress(){if(!documentsMapsAvailable()){return false;}"
                        . "if(!map&&!initializeGMap()){return false;}var a=document.getElementById('geoaddress').value;"
                        . "if(!a){return false;}var g=new google.maps.Geocoder();"
                        . "g.geocode({address:a},function(r,s){if(s===google.maps.GeocoderStatus.OK&&r[0]){"
                        . "var p=r[0].geometry.location;map.setCenter(p);documentMarker.setPosition(p);"
                        . "document.getElementById('lat').value=p.lat();document.getElementById('lng').value=p.lng();}});"
                        . "return false;}if(window.addEventListener){window.addEventListener('load',initializeGMap);}";
                    $_SCRIPTS->setJavaScript($js, true);
                }

                $html .= '<div class="documents-marker-field"><strong>' . $label . $required . '</strong>'
                    . $markerTemplate->parse('marker_field', 'marker') . $help . '</div>';
            }
            break;

        default:
            break;
    }

    return $html . '</div>';
}
