<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.0                                                    |
// +---------------------------------------------------------------------------+
// | include_edit.php                                                          |
// |                                                                           |
// | Plugin administration page                                                |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2014 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// +---------------------------------------------------------------------------+
// | Created with the Geeklog Plugin Toolkit.                                  |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+

/**
* @package Documents
*/

function DOCUMENTS_editCat($cat=array())
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1, $LANG_ACCESS, $_USER, $_GROUPS, $_PLUGINS;
	
	//Display form

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('cat' => 'cat_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
	$template->set_var('xhtml', XHTML);
	
	($cat['cid'] == '') ? $template->set_var('cat_informations', $LANG_DOCUMENTS_1['new_cat']) :
	$template->set_var('cat_informations', $LANG_DOCUMENTS_1['edit_cat'] . ' ' . $cat['category']);
	
	if (is_numeric($cat['cid'])) {
        $template->set_var('cid', '<input type="hidden" name="cid" value="' . $cat['cid'] .'" />');
    } else {
        $template->set_var('cid', '');
    }
	
	//category
    $template->set_var('category_label', $LANG_DOCUMENTS_1['cat_name']);
	$template->set_var('category', $cat['cat_name']);
	
	//category_url
    $template->set_var('cat_url_label', $LANG_DOCUMENTS_1['cat_url']);
	$template->set_var('cat_url', $cat['cat_url']);
	
	//css
    $template->set_var('css_label', $LANG_DOCUMENTS_1['css']);
	$template->set_var('css', $cat['css']);
	
	//map

	if(in_array('maps', $_PLUGINS)) {
	    $map_options = MAPS_recurseMaps($cat['map']);
		$map = '<div><p><label>' . $LANG_DOCUMENTS_1['use_map'] . '</label> <select id="map" name="map"><option value="0">' . $LANG_DOCUMENTS_1['no_map'] . '</option>' . $map_options . '</select><br' . XHTML . '>' . $LANG_DOCUMENTS_1['use_map_details'] . '</p></div>';
			
	    $template->set_var('map', $map);
	} else {
	    $template->set_var('map', '');
	}
	
	//template
    $template->set_var('template_label', $LANG_DOCUMENTS_1['template']);
	$template->set_var('template', $cat['template']);

    //catorder
    $template->set_var('catorder_label', $LANG_DOCUMENTS_1['cat_order']);
	
	if ($cat['cat_order'] == '') $cat['cat_order'] = DB_getItem($_TABLES['documents_cat'],'MAX(cat_order)',"1=1") + 10;
	$template->set_var('cat_order', $cat['cat_order']);
	
	$template->set_var('existing_cat', $LANG_DOCUMENTS_1['existing_cat']);
	
	$res = DB_query("SELECT cat_order, cat_name , cat_url 
	                 FROM {$_TABLES['documents_cat']}
					 WHERE 1 = 1 
					 ORDER by cat_order
					 ");

	while ($B = DB_fetchArray($res)) {
	    $categories_order .=  $B['cat_order'] .  '. ' . $B['cat_name'] .  ' | ' . $B['cat_url'] . '<br' . XHTML . '>';
	}
	
    if ($categories_order == '') $categories_order = $LANG_DOCUMENTS_1['none'];
	
	$template->set_var('categories_order', '<blockquote>' . $categories_order . '</blockquote>');
	
	// list_index
	$template->set_var('list_index', $LANG_DOCUMENTS_1['list_index']);
	if ($cat['list_index'] == '1' || $cat['list_index'] == '') {
        $template->set_var('list_index_ckecked', ' checked="checked"');
	}
    else {
        $template->set_var('list_index_ckecked', '');
	}
    
	// submitable
	$template->set_var('submitable', $LANG_DOCUMENTS_1['submitable']);
	if ($cat['submitable'] == '1' || $cat['submitable'] == '') {
        $template->set_var('submitable_ckecked', ' checked="checked"');
	}
    else {
        $template->set_var('submitable_ckecked', '');
	}
	
	// cat_help
	$template->set_var('cat_help_label', $LANG_DOCUMENTS_1['cat_help']);
	$template->set_var('cat_help', $cat['cat_help']);

	// custom_header
    $template->set_var('custom_header_label', $LANG_DOCUMENTS_1['custom_header']);
	$template->set_var('custom_header', $cat['custom_header']);
	
	//custom_footer
    $template->set_var('custom_footer_label', $LANG_DOCUMENTS_1['custom_footer']);
	$template->set_var('custom_footer', $cat['custom_footer']);
	
	//Admin options
	$options = '<select name="op"><option value="save" selected="selected">' . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($cat['cid'] != '') $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option></select>';
	$template->set_var('admin_options', $options);
	
	//submit
	$template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
	$template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);
	
	// Permissions
	if ($cat['perm_owner'] == '') {
	  SEC_setDefaultPermissions($cat, $_DOCUMENTS_CONF['default_permissions']);
	}
	$template->set_var('lang_accessrights', $LANG_ACCESS['accessrights']);
    $template->set_var('lang_owner', $LANG_ACCESS['owner']);

	if ($cat['owner_id'] == '') $cat['owner_id'] = $_USER['uid'];
    $ownername = COM_getDisplayName($cat['owner_id']);
	
    $template->set_var('owner_username', DB_getItem($_TABLES['users'],
                          'username',"uid = {$cat['owner_id']}"));
    $template->set_var('owner_name', $ownername);
    $template->set_var('owner', $ownername);
    $template->set_var('owner_id', $cat['owner_id']);
	
	if ($cat['group_id']  == '') {
        $cat['group_id'] = $_GROUPS['Documents Admin'];
    }
    $template->set_var('lang_group', $LANG_ACCESS['group']);

    $access = 3;
    $template->set_var('group_dropdown', SEC_getGroupDropdown($cat['group_id'], $access));
    $template->set_var('permissions_editor', SEC_getPermissionsHTML($cat['perm_owner'],$cat['perm_group'],$cat['perm_members'],$cat['perm_anon']));
    $template->set_var('lang_permissions', $LANG_ACCESS['permissions']);
    $template->set_var('lang_perm_key', $LANG_ACCESS['permissionskey']);
    $template->set_var('permissions_msg', $LANG_ACCESS['permmsg']);
    $template->set_var('lang_permissions_msg', $LANG_ACCESS['permmsg']);
	
    $retval .= $template->parse('output', 'cat');

    return $retval;
}

function DOCUMENTS_editField($field='')
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1, $LANG_ACCESS, $_USER, $_GROUPS,$_SCRIPTS;
	
	$_SCRIPTS->setJavaScriptLibrary('jquery');
	
	//Display form

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('field' => 'field_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
	$template->set_var('xhtml', XHTML);
	
	($field['fid'] == '') ? $template->set_var('field_informations', $LANG_DOCUMENTS_1['new_field']) :
	$template->set_var('field_informations', $LANG_DOCUMENTS_1['edit_field'] . ' ' . $field['f_name']);
	
	if (is_numeric($field['fid'])) {
        $template->set_var('fid', '<input type="hidden" name="fid" value="' . $field['fid'] .'" />');
    } else {
        $template->set_var('fid', '');
    }
	
	//field
    $template->set_var('field_label', $LANG_DOCUMENTS_1['field_name']);
	$template->set_var('field_name', $field['f_name']);
	
	//cat_id
	$template->set_var('categories_label', $LANG_DOCUMENTS_1['category']);
	$res = DB_query("SELECT cat_name, cid 
	                 FROM {$_TABLES['documents_cat']}
					 WHERE 1 = 1 
					 ORDER by cat_order
					 ");
					 
	
	// Category selector
	
	$categories_select = '<select id="cat_id" name="cat_id"><option value="0" on> -- </option>';
	
	while ($A = DB_fetchArray($res)) {
	    ($A['cid']==$field['cat_id']) ? $selected = 'selected="selected"' : $selected = '';
		$categories_select .= '<option value="' . $A['cid'] . '" ' . $selected . '>' . $A['cat_name'] . '</option>';
	}
	$categories_select .= '</select>';
	$template->set_var('categories_select', $categories_select);
	
	//ajax request to set last order from cat 
	$js = "$(document).ready(function(){
	jQuery('#cat_id').change(function()
		{
		   //alert('Value change to ' + jQuery(this).attr('value'));
		   var cat_id = jQuery(this).attr('value');
		   var string = '&action=change_field_cat&cat_id=' + cat_id;
		   
		   jQuery.ajax({
			type: 'POST',
			url: '" . $_CONF['site_admin_url'] . "/plugins/documents/ajax.php',
			data: string,
			dataType: 'json',
			cache: false,
			async:false,
			success: function(result){
				jQuery('input[name=f_order]').val(result.a);
				jQuery('#fields_list').html(result.b);
				return;
			}   
		});

		return false;
		});
		});";
		
    $_SCRIPTS->setJavaScript($js, true);
	
    //field order
    $template->set_var('fieldorder_label', $LANG_DOCUMENTS_1['field_order']);
	
	if ($field['f_order'] == '' && $field['cat_id'] != '') $field['f_order'] = DB_getItem($_TABLES['documents_fields'],'MAX(f_order)',"cat_id={$field['cat_id']}") + 10;
	$template->set_var('field_order', $field['f_order']);
	
	$template->set_var('existing_field', $LANG_DOCUMENTS_1['existing_field']);
	
	if ($field['cat_id']!='') {
		$res = DB_query("SELECT f_order, f_name 
						 FROM {$_TABLES['documents_fields']}
						 WHERE cat_id = {$field['cat_id']} 
						 ORDER by f_order
						 ");

		while ($B = DB_fetchArray($res)) {
			$fields_order .=  $B['f_order'] .  '. ' . $B['f_name'] . '<br' . XHTML . '>';
		}
	}
	
    if ($fields_order == '') $fields_order = $LANG_DOCUMENTS_1['none'];
	
	$template->set_var('fields_order', '<div style="padding:0px 20px" id="fields_list">' . $fields_order . '</div>');
	
	//f_type
	$template->set_var('type_label', $LANG_DOCUMENTS_1['type']);
	$type_select = DOCUMENTS_fieldsTypeSelect($field['f_type']);
	$template->set_var('type_select', $type_select);
	
	//TODO hide select group if type is not group 
    //sel_id
	$template->set_var('sel_label', $LANG_DOCUMENTS_1['sel_group']);
	$res = DB_query("SELECT g_name, gid 
	                 FROM {$_TABLES['documents_selects_group']}
					 WHERE 1 = 1 
					 ORDER by g_name
					 ");
					 
	$group_select = '<select name="sel_id"><option value="0"> -- ' . $LANG_DOCUMENTS_1['none'] . ' -- </option>';
	
	while ($A = DB_fetchArray($res)) {
	    ($A['gid']==$field['sel_id']) ? $selected = 'selected="selected" ' : $selected = '';
		$group_select .= '<option value="' . $A['gid'] . '" ' . $selected . '>' . $A['g_name'] . '</option>';
	}
	$group_select .= '</select>';
	$template->set_var('group_select', $group_select);
    
	//var_name
    $template->set_var('var_label', $LANG_DOCUMENTS_1['var_name']);
	$template->set_var('var_name', $field['var_name']);
	
	//f_help
	$template->set_var('help_label', $LANG_DOCUMENTS_1['field_help']);
	$template->set_var('f_help', $field['f_help']);
	
	// f_required
	$template->set_var('f_required', $LANG_DOCUMENTS_1['field_require']);
	if ($field['f_required'] == '1' || $field['f_required'] == '') {
        $template->set_var('f_required_ckecked', ' checked="checked"');
	}
    else {
        $template->set_var('f_required_ckecked', '');
	}

	// f_on_list
	$template->set_var('f_on_list', $LANG_DOCUMENTS_1['field_on_list']);
	if ($field['f_on_list'] == '1' || $field['f_on_list'] == '') {
        $template->set_var('f_on_list_ckecked', ' checked="checked"');
	}
    else {
        $template->set_var('f_on_list_ckecked', '');
	}
	//Admin options
	$options = '<select name="op"><option value="save" selected="selected">' . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($field['fid'] != '') $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option></select>';
	$template->set_var('admin_options', $options);
	
	//submit
	$template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
	$template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);
	
	// Permissions
	if ($field['perm_owner'] == '') {
	  SEC_setDefaultPermissions($field, $_DOCUMENTS_CONF['default_permissions']);
	}
	$template->set_var('lang_accessrights', $LANG_ACCESS['accessrights']);
    $template->set_var('lang_owner', $LANG_ACCESS['owner']);

	if ($field['owner_id'] == '') $field['owner_id'] = $_USER['uid'];
    $ownername = COM_getDisplayName($field['owner_id']);
	
    $template->set_var('owner_username', DB_getItem($_TABLES['users'],
                          'username',"uid = {$field['owner_id']}"));
    $template->set_var('owner_name', $ownername);
    $template->set_var('owner', $ownername);
    $template->set_var('owner_id', $field['owner_id']);
	
	if ($field['group_id']  == '') {
        $field['group_id'] = $_GROUPS['Documents Admin'];
    }
    $template->set_var('lang_group', $LANG_ACCESS['group']);

    $access = 3;
    $template->set_var('group_dropdown', SEC_getGroupDropdown($field['group_id'], $access));
    $template->set_var('permissions_editor', SEC_getPermissionsHTML($field['perm_owner'],$field['perm_group'],$field['perm_members'],$field['perm_anon']));
    $template->set_var('lang_permissions', $LANG_ACCESS['permissions']);
    $template->set_var('lang_perm_key', $LANG_ACCESS['permissionskey']);
    $template->set_var('permissions_msg', $LANG_ACCESS['permmsg']);
    $template->set_var('lang_permissions_msg', $LANG_ACCESS['permmsg']);
	
    $retval .= $template->parse('output', 'field');

    return $retval;
}

function DOCUMENTS_editDoc($doc = '')
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1, $LANG_ACCESS, $_USER, $_GROUPS, $_SCRIPTS, $_PLUGINS, $_MAPS_CONF;
	
	$_SCRIPTS->setCSSFile('document_css', '/admin/plugins/documents/documents.css');
    $_SCRIPTS->setJavaScriptLibrary('jquery');
    
    if (in_array('maps', $_PLUGINS)) {
        $_SCRIPTS->setJavaScript('<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=' . $_MAPS_CONF['google_api_key'] . '&amp;libraries=adsense"></script>', false, false);
    }
	
	//Display form
	
	$retval = '';

	// Get template from cat
	
	if ($doc['cid'] == '') return 'Category is empty!'; 

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');

	$template->set_file(array('doc' => 'doc_form.thtml',
	                          'access' => 'access_permissions.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
	$template->set_var('xhtml', XHTML);
	$template->set_var('gltoken_name', CSRF_TOKEN);
    $template->set_var('gltoken', SEC_createToken());
	
	if ($doc['doc_url'] == '') {
	    $template->set_var('doc_informations', $LANG_DOCUMENTS_1['create_new_doc'] . ' > ' . $doc['cat_name']);
		$template->set_var('doc_url_hidden', '<input type="hidden" name="doc_url" value="' . $doc['doc_url'] .'" />');
	} else {
	    $template->set_var('doc_informations', $LANG_DOCUMENTS_1['edit_doc'] . ' > ' . $doc['cat_name']);
		 $template->set_var('doc_url_hidden', '<input type="hidden" name="doc_url" value="' . $doc['doc_url'] .'" />');
	}
	
	if (is_numeric($doc['cid'])) {
        $template->set_var('cid', '<input type="hidden" name="cid" value="' . $doc['cid'] .'" />');
    } else {
        $template->set_var('cid', '');
    }

	// Get all fields for this form by order
	
	$sql = "SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id = '{$doc['cid']}' ORDER BY f_order";
	$res_fields = DB_query($sql);
	
	// For each field build the template
	define("DOC_NAME", $doc['v_value'][0]);
	
	while ($doc_field = DB_fetchArray($res_fields)) {
	
		// Build selects arrays
		$doc['selects'] = array();
		$fid = $doc_field ['fid'];
		
		if ($doc_field['f_type']=='select' || $doc_field['f_type']=='radio') {
			
			// get all items from selects table for this group
			
			$sql = "SELECT s.*, g.* FROM {$_TABLES['documents_selects']} AS s
				LEFT JOIN {$_TABLES['documents_selects_group']} AS g
				   ON s.s_group = g.gid 
				WHERE s_group = '{$doc_field['sel_id']}' ORDER BY s.s_order";
			
			$res_selects = DB_query($sql);
			$selects = array();
			
			while ($B = DB_fetchArray($res_selects)) {
				//build selects arrays
				$selects['name'][] = $B['s_name'];
				$selects['value'][] = $B['s_value'];
				$selects['g_help'][] = $B['g_help'];
				$doc_field['sel_name'] = $B['g_name'];
			}
			
			$doc['selects'][$fid] = $selects;
			unset($selects);
		}
	
		$raws .= DOCUMENTS_buildRawForm ($doc_field, $doc, $template, $fid);

	}

	$template->set_var('raws', $raws);
	
	//Active

	if ( $doc['active'] == 0 ) {
		$selected0 = ' selected="selected"';
	} else if ( $doc['active'] == 2 ){
		$selected2 = ' selected="selected"';
	} else {
		$selected1 = ' selected="selected"';
	}		
	$active = '<p><label class="document_field_edit">' . $LANG_DOCUMENTS_1['active_label']. '</label>';
	$active .= '<select name="active">';
	if (SEC_hasRights('documents.admin')) {
		$active .= '<option value="0"'. $selected0 . '>' . $LANG_DOCUMENTS_1['not_active'] . '</option>';
	}
	$active .= '<option value="1"'. $selected1 . '>' . $LANG_DOCUMENTS_1['active'] . '</option>';
	if (!COM_isAnonUser()) $active .= '<option value="2"'. $selected2 . '>' . $LANG_DOCUMENTS_1['draft'] . '</option>';
	$active .= '</select></p>';
	$template->set_var('active', $active);

	
	// Admin options
	
	$options = '<select name="op"><option value="save" selected="selected">' . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($doc['cid'] != '' && SEC_hasRights('documents.admin')) $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option></select>';
	
	if (SEC_hasRights('documents.admin')) {
	    //access & permissions
		if ($doc['perm_owner'] == '') {
		    SEC_setDefaultPermissions($doc, $_DOCUMENTS_CONF['default_permissions']);
		}
		$template->set_var('lang_accessrights', $LANG_ACCESS['accessrights']);
		$template->set_var('lang_owner', $LANG_ACCESS['owner']);

		if ($doc['owner_id'] == '') $doc['owner_id'] = $_USER['uid'];
		
		//Select owner
		$result = DB_query("SELECT * FROM {$_TABLES['users']}");
		$nRows  = DB_numRows($result);

		$owner_select = '<select name="owner_id">';
		for ($i=0; $i<$nRows;$i++) {
			$row = DB_fetchArray($result);
			if ( $row['uid'] == 1 ) {
				continue;
			}
			$owner_select .= '<option value="' . $row['uid'] . '"' . ($doc['owner_id'] == $row['uid'] ? 'selected="selected"' : '') . '>' . COM_getDisplayName($row['uid']) . ' | ' . $row['uid'] . '</option>';
		}
		$owner_select .= '</select>';

		$template->set_var('owner_select', $owner_select);
		$template->set_var('owner', $ownername);
		
		if ($doc['group_id']  == '') {
			$doc['group_id'] = $_GROUPS['Documents Admin'];
		}
		$template->set_var('lang_group', $LANG_ACCESS['group']);

		$access = 3;
		$template->set_var('group_dropdown', SEC_getGroupDropdown($doc['group_id'], $access));
		$template->set_var('permissions_editor', SEC_getPermissionsHTML($doc['perm_owner'],$doc['perm_group'],$doc['perm_members'],$doc['perm_anon']));
		$template->set_var('lang_permissions', $LANG_ACCESS['permissions']);
		$template->set_var('lang_perm_key', $LANG_ACCESS['permissionskey']);
		$template->set_var('permissions_msg', $LANG_ACCESS['permmsg']);
		$template->set_var('lang_permissions_msg', $LANG_ACCESS['permmsg']);
		
		$access_perms = $template->parse('access_perms', 'access');
	} else {
	    //Non admin
		$access_perms = ''; 
	}
	
	$template->set_var('access_perms', $access_perms);
	$template->set_var('admin_options', $options);
	
	// Submit
	
	$template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);
	$template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
	$template->set_var('required_doc', $LANG_DOCUMENTS_1['required_doc']);

	
    $retval .= $template->parse('output', 'doc');
	

    return $retval;
}

function DOCUMENTS_buildRawForm ($field, $doc, &$template, $i) {
    
	global $_CONF, $_DOCUMENTS_CONF, $_PLUGINS, $LANG_MAPS_1, $_SCRIPTS, $_TABLES, $LANG_DOCUMENTS_1, $MG_albums;
	
	$html = '<div>' . LB;

	( $field['f_required'] == 1 ) ? $f_required = '<font color="red"> *</font>' : $f_required = '';
	( $field['f_help'] != '' ) ? $help = ' <div class="documents_help">'. $field['f_help'] . '</div>' : $help = '';
	
	switch ($field['f_type']) {
		
		case 'checkbox' :
			$html .= '<p><label class="document_field_edit">&nbsp;</label>';
			$checked = '';
			if($doc['v_value'][$i] == 1) {
				$checked = 'checked="checked" ';
			}
			$html .= ' <input type="checkbox" value="1" name="' . $field['var_name'] . '" ' .  $checked . XHTML . '>' . $field['f_name'] . $help .'</p>' . LB;
			break;
			
		case 'radio' :
			//$selected = ' checked="checked" ';
			break;
			
		case 'select' :
			$html .= '<p><label class="document_field_edit">' . $field['sel_name'] . '</label>';
			$html .= '<select name="' . $field['var_name'] . '">' . LB;
			$count = count($doc['selects'][$i]['name']);
			
			for ($it=0; $it<$count; $it++) {
				$selected = '';
				if ($it == 0) {
				  $options .='<option value="0"  selected="selected" >  - - ' . $doc['selects'][$i]['g_help'][0] . ' - - </option>' . LB . $options; 
				}
				if ($doc['v_value'][$i]  == $doc['selects'][$i]['name'][$it]) $selected = ' selected="selected" ';
				$options .='<option value="' . $doc['selects'][$i]['name'][$it] . '"' . $selected . '>' . $doc['selects'][$i]['value'][$it] . '</option>' . LB;

			}

			$html .= $options . '</select>' . $help;
			break;
			
		case 'category' :
			//TODO display all items form category in a select box 
			break;
			
		case 'file' :
			// TODO display a list of all available files from downloads plugin
			break;
		
		case 'textarea':
			$html .= '<p><label class="document_field_edit">' . $field['f_name'] . $f_required . '</label>';
			$html .= '<textarea style="width:70%; height:300px;" class="document_field_edit_textarea" name="' . $field['var_name'] . '">' . stripslashes($doc['v_value'][$i]) . '</textarea>' . $help . '</p>' . LB;
			break;
		
		case 'decimal':
		case 'date':
		case 'text':
			$html .= '<p><label class="document_field_edit">' . $field['f_name'] . $f_required . '</label>';
			$html .= '<input class="document_field_edit_text" type="text" name="' . $field['var_name'] . '" value="' . stripslashes($doc['v_value'][$i]) . '" size="100" maxlength="255" ' . XHTML . '>' . $help .'</p>' . LB;
			break;
			
		//TODO make a list of available album for this user
		case 'album': // mediagallery album
		    if (in_array('mediagallery', $_PLUGINS)) {
			    //require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';
				//MG_initAlbums();
                //Mediagallery 1.7+
                require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
                require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';

                $root_album = new mgAlbum(0); // root album
                //$album      = new mgAlbum($album_id); // current album
                
				global $album_jumpbox;
				// construct the album jumpbox...
				$album_jumpbox = '<select name="' . $field['var_name'] . '">';
				$album_jumpbox .= '<option value="">----</option>';
				//$MG_albums[0]->buildJumpBox(stripslashes($doc['v_value'][$i]),3);
                $root_album->buildJumpBox($album_jumpbox, 0, 1, -1);
				$album_jumpbox .= '</select>' . LB;
				$html .= '<p><label class="document_field_edit">' . $field['f_name'] . $f_required . '</label>' . $album_jumpbox . '</p>';
				//$html .= '<input class="document_field_edit_text" type="text" name="' . $field['var_name'] . '" value="' . stripslashes($doc['v_value'][$i]) . '" size="60" maxlength="255" ' . XHTML . '>' . $help .'</p>' . LB;
			} else {
			    $html .= '';
			}
			
			break;
			
		case 'image':
			//image is set display image + replace button
			$html .= '<p><label class="document_field_edit">' . $field['f_name'] . $f_required . '</label><div class="document_field_edit_right">';
			
			if ($doc['v_value'][$i]!= '' && is_file($_DOCUMENTS_CONF['path_images'] . $doc['v_value'][$i])) {
				$html .= '<img src="' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . $_DOCUMENTS_CONF['images_url'] .
					$doc['v_value'][$i] . '&amp;w=450" align="top" alt="' . DOC_NAME . '" /><br' . XHTML . '><br' . XHTML . '>';
			}
			
			$html .= '<input type="file" dir="ltr" name="file' . $field['fid'] . '"' . XHTML . '></div></p>' . LB;
			break;
			
		case 'marker':

			if( in_array('maps', $_PLUGINS) ) {
				
				//Get marker info with id $doc['v_value'][$i]
				$sql = "SELECT *
							FROM {$_TABLES['maps_markers']}
						WHERE mkid = '{$doc['v_value'][$i]}'";
								
				$res = DB_query($sql);
				$marker = DB_fetchArray($res);
				
				if ($marker['lat'] == '') {
				    //set default for geocoder
					$marker['lat'] = '37.4217913';
					$marker['lng'] = '-122.08371390000002';
					$marker['address'] = '1600 Amphitheatre Pky, Mountain View, CA';
				}
	
				$t = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
				$t->set_file(array('marker' => 'marker_form.thtml'));
				$t->set_var('go', $LANG_MAPS_1['go']);
				$t->set_var('address',  $marker['address']);
				$t->set_var('lat_value', $marker['lat']);
				$t->set_var('lng_value',  $marker['lng']);
				$t->set_var('var_name', $field['var_name']);
				$t->set_var('mkid', $doc['v_value'][$i]);
				
				//javascript
				$_SCRIPTS->setJavaScriptLibrary('jquery');
				
				$js = LB . '
				<script type="text/javascript">	
					
					var geocoder = new google.maps.Geocoder();
					var map;
					var infowindow;
					var markers = [];

					function initializeGMap() {
						
						var mapOptions = {
						  center: new google.maps.LatLng(' . $marker['lat'] . ', ' . $marker['lng'] . '),
						  zoom: 10,
						  mapTypeId: google.maps.MapTypeId.ROADMAP
						};
						
						map = new google.maps.Map(document.getElementById("map_canvas"),
							mapOptions);
							
						var marker = new google.maps.Marker({
						  map: map,
						  position: new google.maps.LatLng('. $marker['lat']. ', '. $marker['lng'] .'),
						  title: "' .  $marker['name'] . '",
						  draggable:true,
						  animation: google.maps.Animation.DROP,
						});
						
						//Show infowindow
						showInfoWindowHtml(marker,' . $marker['lat'] . ',' . $marker['lng'] . ');
						
                        //Add marker to an array						
						markers.push(marker);

						google.maps.event.addDomListener(marker, "dragend", function(evt) {
							infowindow.close();
							document.getElementById(\'lat\').value = evt.latLng.lat().toFixed(6);
							document.getElementById(\'lng\').value = evt.latLng.lng().toFixed(6);
							var lat = evt.latLng.lat().toFixed(6);
							var lng = evt.latLng.lng().toFixed(6);
							showInfoWindowHtml(marker, lat, lng);
						});
						
					}
					
					google.maps.event.addDomListener(window, \'load\', initializeGMap);
					
					// Sets the map on all markers in the array.
					function setAllMap(map) {
					  for (var i = 0; i < markers.length; i++) {
						markers[i].setMap(map);
					  }
					}

					function showInfoWindowHtml (marker,lat,lng) {

					  infowindow = new google.maps.InfoWindow({
				        content: \'<div style="line-height:1.35;overflow:hidden;white-space:nowrap;"><p style="width:150px;">Lat : \' + lat + \'<br' . XHTML . '>Lng : \' + lng + \'</p></div>\'
			          });
					  infowindow.open(map,marker);

					}

					function codeAddress() {
					  var address = document.getElementById(\'geoaddress\').value;
					  geocoder.geocode( { \'address\': address}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {

    					  //Remove marker from the map
						  setAllMap(null);
						  markers = [];

						  
						  map.setCenter(results[0].geometry.location);
						  var marker = new google.maps.Marker({
							  map: map,
							  position: results[0].geometry.location,
							  draggable:true,
						      animation: google.maps.Animation.DROP
						  });
						  
						  //Add marker to an array						
						  markers.push(marker);
						
						  document.getElementById(\'lat\').value = results[0].geometry.location.lat(); 
						  document.getElementById(\'lng\').value = results[0].geometry.location.lng(); 
						  
						  //Show infowindow
						  showInfoWindowHtml(marker,results[0].geometry.location.lat(),results[0].geometry.location.lng());
						
						  google.maps.event.addDomListener(marker, "dragend", function(evt) {
							infowindow.close();
							document.getElementById(\'lat\').value = evt.latLng.lat().toFixed(6);
							document.getElementById(\'lng\').value = evt.latLng.lng().toFixed(6);
							var lat = evt.latLng.lat().toFixed(6);
							var lng = evt.latLng.lng().toFixed(6);
							showInfoWindowHtml(marker, lat, lng);
						  });
						
						} else {
						  alert(\'Geocode was not successful for the following reason: \' + status);
						}
					  });
					}

					function copyText()
					{
						var t1 = document.getElementById(\'geoaddress\').value;
						document.getElementById(\'address\').value = t1;
					}
					</script>' . LB. LB;
					
				$_SCRIPTS->setJavaScript($js, false);
				
				$html .= '<p><label class="document_field_edit">' . $field['f_name'] . $f_required . '</label>
				  ' . $field['f_help'] . '
				  <div class="document_field_edit_right">';
				$html .= $t->parse('marker_field', 'marker') . '</div></p>';
			}
			
			break;		
			
		default:
			break;
		
	}

	$html .= '</div>' . LB;   
	
	return $html;
	
}

function DOCUMENTS_editGroup($group=array())
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;
	
	//Display form

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('group' => 'group_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
	$template->set_var('xhtml', XHTML);
	
	($group['gid'] == '') ? $template->set_var('group_informations', $LANG_DOCUMENTS_1['new_group']) :
	$template->set_var('group_informations', $LANG_DOCUMENTS_1['edit_group'] . ' ' . $group['g_name']);
	
	if (is_numeric($group['gid'])) {
        $template->set_var('gid', '<input type="hidden" name="gid" value="' . $group['gid'] .'" />');
    } else {
        $template->set_var('gid', '');
    }
	
	//groupe name
    $template->set_var('group_label', $LANG_DOCUMENTS_1['group_name']);
	$template->set_var('group_name', $group['g_name']);
	
	//groupe help
    $template->set_var('help_label', $LANG_DOCUMENTS_1['group_help']);
	$template->set_var('help', $group['g_help']);
	
	
	//Admin options
	$options = '<select name="op"><option value="save" selected="selected">' . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($group['gid'] != '') $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option></select>';
	$template->set_var('admin_options', $options);
	
	//submit
	$template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
	$template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);
	
    $retval .= $template->parse('output', 'group');

    return $retval;
}

function DOCUMENTS_editSelect($select=array())
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1, $_SCRIPTS;
	
	//Display form

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('select' => 'select_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
	$template->set_var('xhtml', XHTML);
	
	($select['sid'] == '') ? $template->set_var('select_informations', $LANG_DOCUMENTS_1['new_option']) :
	$template->set_var('select_informations', $LANG_DOCUMENTS_1['edit_select'] . ' ' . $select['s_name']);
	
	if (is_numeric($select['sid'])) {
        $template->set_var('sid', '<input type="hidden" name="sid" value="' . $select['sid'] .'" />');
    } else {
        $template->set_var('sid', '');
    }
	
	//select name
    $template->set_var('select_label', $LANG_DOCUMENTS_1['select_name']);
	$template->set_var('select_name', $select['s_name']);
	
	//select value
    $template->set_var('value_label', $LANG_DOCUMENTS_1['select_value']);
	$template->set_var('value', $select['s_value']);
	
	// Group selector
    $template->set_var('group_label', $LANG_DOCUMENTS_1['sel_group']);
	$res = DB_query("SELECT g_name, gid 
	                 FROM {$_TABLES['documents_selects_group']}
					 WHERE 1 = 1 
					 ");
					 
	$group_select = '<select id="s_group" name="s_group"><option value="0" on> -- </option>';
	
	while ($A = DB_fetchArray($res)) {
	    ($A['gid']==$select['s_group']) ? $selected = 'selected="selected"' : $selected = '';
		$group_select .= '<option value="' . $A['gid'] . '" ' . $selected . '>' . $A['g_name'] . '</option>';
	}
	$group_select .= '</select>';
	$template->set_var('group_select', $group_select);
	
	//ajax request to set last order from cat 
	$js = "$(document).ready(function(){
	jQuery('#s_group').change(function()
		{
		   var s_group = jQuery(this).attr('value');
		   var string = '&action=change_select_group&s_group=' + s_group;
		   
		   jQuery.ajax({
			type: 'POST',
			url: '" . $_CONF['site_admin_url'] . "/plugins/documents/ajax.php',
			data: string,
			dataType: 'json',
			cache: false,
			async:false,
			success: function(result){
				jQuery('input[name=s_order]').val(result.a);
				jQuery('#groups_list').html(result.b);
				return;
			}   
		});

		return false;
		});
		});";
		
    $_SCRIPTS->setJavaScript($js, true);
	
	// Order
    $template->set_var('s_order_label', $LANG_DOCUMENTS_1['select_order']);
	
	if ($select['s_order'] == '' && $select['s_group'] != '' ) $select['s_order'] = DB_getItem($_TABLES['documents_selects'],'MAX(s_order)',"s_group = {$select['s_group']}") + 10;
	$template->set_var('s_order', $select['s_order']);
	
	$template->set_var('existing_select', $LANG_DOCUMENTS_1['existing_select']);
	
	if ($select['s_group'] != '' ) {
	    $res = DB_query("SELECT s_order, s_name 
	                 FROM {$_TABLES['documents_selects']}
					 WHERE s_group = {$select['s_group']} 
					 ORDER by s_order
					 ");

		while ($B = DB_fetchArray($res)) {
			$s_order .=  $B['s_order'] .  '. ' . $B['s_name'] . '<br' . XHTML . '>';
		}
	}
	
    if ($s_order == '') $s_order = $LANG_DOCUMENTS_1['none'];
	
	$template->set_var('select_order', '<div style="padding:0px 20px" id="groups_list">' . $s_order . '</div>');
	
	//Admin options
	$options = '<select name="op"><option value="save" selected="selected">' . $LANG_DOCUMENTS_1['save_button'] . '</option>';
    if ($cat['cid'] != '') $options .= '<option value="delete">' . $LANG_DOCUMENTS_1['delete_button'] . '</option></select>';
	$template->set_var('admin_options', $options);
	
	//submit
	$template->set_var('validate_button', $LANG_DOCUMENTS_1['validate_button']);
	$template->set_var('required_field', $LANG_DOCUMENTS_1['required_field']);
	
    $retval .= $template->parse('output', 'select');

    return $retval;
}

 function DOCUMENTS_fieldsTypeSelect($type) {
	
	($type=='text') ? $text_selected = 'selected="selected"' : $text_selected = '';
	($type=='album') ? $album_selected = 'selected="selected"' : $album_selected = '';
	($type=='decimal') ? $decimal_selected = 'selected="selected"' : $decimal_selected = '';
	($type=='date') ? $date_selected = 'selected="selected"' : $date_selected = '';
	($type=='checkbox') ? $checkbox_selected = 'selected="selected"' : $checkbox_selected = '';
	($type=='radio') ? $radio_selected = 'selected="selected"' : $radio_selected = '';
	//TODO file from downloads plugin
	($type=='file') ? $file_selected = 'selected="selected"' : $file_selected = '';
	($type=='marker') ? $marker_selected = 'selected="selected"' : $marker_selected = '';
	($type=='textarea') ? $textarea_selected = 'selected="selected"' : $textarea_selected = '';
	($type=='select') ? $select_selected = 'selected="selected"' : $select_selected = '';
	($type=='image') ? $image_selected = 'selected="selected"' : $image_selected = '';
	($type=='category') ? $category_selected = 'selected="selected"' : $category_selected = '';

	
	$html = '<select name="f_type">
	              <option value="text" ' . $text_selected .'>text</option>
				  <option value="textarea" ' . $textarea_selected .'>textarea</option>
				  <option value="decimal" ' . $decimal_selected .'>decimal</option>
				  <option value="date" ' . $date_selected .'>date</option>
				  <option value="image" ' . $image_selected .'>image</option>
	              <option value="checkbox" ' . $checkbox_selected .'>checkbox</option>
	        <!--  <option value="radio" ' . $radio_selected .'>radio</option> -->
	              <option value="select" ' . $select_selected .'>select</option>
	              <option value="category" ' . $category_selected .'>category</option>
			<!--  <option value="file" ' . $file_selected .'>file</option> -->>
			      <option value="marker" ' . $marker_selected .'>marker</option>
			      <option value="album" ' . $album_selected .'>mediagallery album</option>
		      </select>';
	
	return $html;
}

// Todo edit selects groups + selects items

?>
