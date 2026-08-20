<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.0                                                    |
// +---------------------------------------------------------------------------+
// | include_html.php                                                          |
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

/**
 * Returns user menu display
 *
 * Generates the user menu from the template and returns the result as a string of HTML
 *
 * @return string HTML of user menu
 */
function DOCUMENTS_user_menu() 
{
    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $_TABLES;

    $retval = '';

    // Generate the menu from the template
    
	$menu = new Template($_CONF['path'] . 'plugins/documents/templates/menus');
    $menu->set_file(array('menu' => 'user_menu.thtml'));
    $menu->set_var('site_url', $_DOCUMENTS_CONF['site_url']);
	$menu->set_var('documents', $LANG_DOCUMENTS_1['documents']);
	
	if (SEC_hasRights('documents.admin')) {
        $admin_menu = '> ' . '<a href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=list_fields">' . $LANG_DOCUMENTS_1['fields'] . '</a>';
		if ($_REQUEST['mode'] ==  ('list_fields' || 'list_groups')) {
		     $admin_menu .= ' > ' . '<a href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=list_groups">' . $LANG_DOCUMENTS_1['selects'] . '</a>';
		}
		$menu->set_var('fields', $admin_menu);
    } else {
	    $menu->set_var('fields', '');
	}
	
	$doc_breadcrums = '';
	
	if (defined("CAT_NAME")) {
	    $doc_breadcrums .= ' > <a href="' . $_DOCUMENTS_CONF['site_url'] .'/'. CAT_URL . '">' . CAT_NAME . '</a>';
	}
	
	if (defined("DOC_NAME")) {
	    $doc_breadcrums .= ' > <a href="' . $_DOCUMENTS_CONF['site_url'] .'/'. CAT_URL .'/'. DOC_URL . '">' . DOC_NAME . '</a>';
		$menu->set_var('fields', '');
	}
	
	$menu->set_var('document', $doc_breadcrums);

	
	
    $retval .= $menu->parse('output', 'menu');

    return $retval;
}

function DOCUMENTS_missingFieldCat ()
{
    global $LANG_DOCUMENTS_1, $_TABLES;
	
	$fields_array = '';
	if ($_REQUEST['cat_name'] == '') $fields_array[] .= $LANG_DOCUMENTS_1['cat_name'];
	if ($_REQUEST['cat_url'] == '') $fields_array[] .= $LANG_DOCUMENTS_1['cat_url'];
	
	// todo check this not always works
	// cat_url must be unique
	
	if ( (!empty($_REQUEST['cid'])) && (is_numeric($_REQUEST['cid'])) ) {
    	
		//update
		
		$cid = DB_getItem($_TABLES['documents_cat'],'cid',"cat_url='{$_REQUEST['cat_url']}' AND cid<>{$_REQUEST['cid']}");
	} else {
	    
		//creation
		
		$cid = DB_getItem($_TABLES['documents_cat'],'cid',"cat_url='{$_REQUEST['cat_url']}'");
	}
    if ($cid != '' )  $fields_array[] .= $LANG_DOCUMENTS_1['cat_url_exists'];
    
	return $fields_array;

}

function DOCUMENTS_missingField ($field)
{
    global $LANG_DOCUMENTS_1;
	
	$fields_array = '';
	if ($field['f_name'] == '') $fields_array[] .= $LANG_DOCUMENTS_1['field_name'];
	if ($field['var_name'] == '') $fields_array[] .= $LANG_DOCUMENTS_1['var_name'];
    
	return $fields_array;

}

function DOCUMENTS_message ($message, $title='')
{
    global $LANG_DOCUMENTS_1;
	
    $retval = '';
	if (!empty($message)) {
        if ($title != '') {
            $retval = COM_startBlock($title, '', 'blockheader-message.thtml');
        } else {
            $retval = COM_startBlock($LANG_DOCUMENTS_1['message'], '', 'blockheader-message.thtml');
        }
        $retval .= stripslashes($message);
        $retval .= COM_endBlock('blockfooter-message.thtml');
    }
	return $retval;
}

/**
* Re-orders all categories in increments of 10
*
*/
function DOCUMENTS_reorderCategories()
{
    global $_TABLES;

    $sql = "SELECT * FROM {$_TABLES['documents_cat']} ORDER BY cat_order ASC;";
    $result = DB_query($sql);
    $nrows = DB_numRows($result);


    $catOrd = 10;
    $stepNumber = 10;

    for ($i = 0; $i < $nrows; $i++) {
        $A = DB_fetchArray($result);

        if ($A['catorder'] != $catOrd) {  // only update incorrect ones
            $q = "UPDATE " . $_TABLES['documents_cat'] . " SET cat_order = '" .
                  $catOrd . "' WHERE cid = '" . $A['cid'] ."'";
            DB_query($q);
        }
        $catOrd += $stepNumber;
    }
}

function DOCUMENTS_reorderSelects()
{
    global $_TABLES;

    $sql = "SELECT * FROM {$_TABLES['documents_selects']} WHERE s_group={$_REQUEST['s_group']} ORDER BY s_order ASC;";
    $result = DB_query($sql);
    $nrows = DB_numRows($result);


    $sOrd = 10;
    $stepNumber = 10;

    for ($i = 0; $i < $nrows; $i++) {
        $A = DB_fetchArray($result);

        if ($A['s_order'] != $sOrd) {  // only update incorrect ones
            $q = "UPDATE " . $_TABLES['documents_selects'] . " SET s_order = '" .
                  $sOrd . "' WHERE sid = '" . $A['sid'] ."'";
            DB_query($q);
        }
        $sOrd += $stepNumber;
    }
}

function DOCUMENTS_reorderFields($cat)
{
    global $_TABLES;

    $sql = "SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id=$cat ORDER BY f_order ASC;";
    $result = DB_query($sql);
    $nrows = DB_numRows($result);


    $fOrd = 10;
    $stepNumber = 10;

    for ($i = 0; $i < $nrows; $i++) {
        $A = DB_fetchArray($result);

        if ($A['f_order'] != $fOrd) {  // only update incorrect ones
            $q = "UPDATE " . $_TABLES['documents_fields'] . " SET f_order = '" .
                  $fOrd . "' WHERE fid = '" . $A['fid'] ."'";
            DB_query($q);
        }
        $fOrd += $stepNumber;
    }
}

function DOCUMENTS_displayDocument($cat_url, $doc_url) {

    global $_TABLES, $_CONF, $_SCRIPTS, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $_USER, $_PLUGINS, $_MAPS_CONF;
	
	$_SCRIPTS->setCSSFile('document_css', '/admin/plugins/documents/documents.css');
    $_SCRIPTS->setJavaScriptLibrary('jquery');
    
    if (in_array('maps', $_PLUGINS)) {
        $_SCRIPTS->setJavaScript('<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=' . $_MAPS_CONF['google_api_key'] . '&amp;libraries=adsense"></script>', false, false);
    }
	
	// Category
	
	$sql = "SELECT * FROM {$_TABLES['documents_cat']} 
			WHERE cat_url = '{$cat_url}'";
	$res = DB_query($sql);
	$cat = DB_fetchArray($res);
	
	if (!defined("CAT_NAME")) {
		define("CAT_NAME",$cat['cat_name']);
	}
	
	//Check if cat exists
	
	if($cat['cat_url'] != '' && $cat['cat_url']== $cat_url) {
		$doc['cid'] = $cat['cid'];
		$doc['cat_name'] = $cat['cat_name'];
		$doc['cat_url'] = $cat['cat_url'];
		$doc['cat_order'] = $cat['cat_order'];
		$doc['css'] = $cat['css'];
		$doc['template'] = $cat['template'];
		$doc['list_index'] = $cat['list_index'];
		$doc['submitable'] = $cat['submitable'];
		$doc['cat_help'] = $cat['cat_help'];
		$doc['custom_header'] = $cat['custom_header'];
		$doc['custom_footer'] = $cat['custom_footer'];
		/*$doc['owner_id'] = $cat['owner_id'];
		$doc['group_id'] = $cat['group_id'];
		$doc['perm_owner'] = $cat['perm_owner'];
		$doc['perm_group'] = $cat['perm_group'];
		$doc['perm_members'] = $cat['perm_members'];
		$doc['perm_anon'] = $cat['perm_anon'];*/
		
		define("CAT_NAME", $cat['cat_name']);
		define("CAT_URL", $cat['cat_url']);
		
	} else {   
	    echo COM_refresh($_CONF['site_url'] . '/404.php');
		exit;
	}
	
	//
	
	// Get Document values
	
	$sql = "SELECT v.field_id, f.f_type, f.sel_id, v.v_value, s.s_value, d.hits, d.doc_url, d.active, d.owner_id , d.group_id, d.perm_owner, d.perm_group, d.perm_members, d.perm_anon
             	FROM {$_TABLES['documents_values']} AS v
			LEFT JOIN {$_TABLES['documents_fields']} AS f
			  ON f.fid = v.field_id 
			LEFT JOIN {$_TABLES['documents_selects']} AS s
			  ON s.s_name = v.v_value
			LEFT JOIN {$_TABLES['documents_docs']} AS d
			  ON d.doc_url = v.doc_url
			WHERE v.doc_url = '{$doc_url}' ORDER BY f.f_order";
					
	$res = DB_query($sql);
	
	while ($A = DB_fetchArray($res)) {

		$doc['field_id'][$A['field_id']] = $A['field_id'];
		$doc['v_value'][$A['field_id']] = $A['v_value'];
		$doc['s_name'][$A['field_id']] = $A['s_value'];
		$doc['doc_url'] = $A['doc_url'];
		$doc['active'] = $A['active'];
		$doc['owner_id'] = $A['owner_id'];
		$doc['group_id'] = $A['group_id'];
		$doc['perm_owner'] = $A['perm_owner'];
		$doc['perm_group'] = $A['perm_group'];
		$doc['perm_members'] = $A['perm_members'];
		$doc['perm_anon'] = $A['perm_anon'];
		$doc['hits'] = $A['hits'];
		
		if (!defined("DOC_NAME")) {
		    define("DOC_NAME", stripslashes($A['v_value']));
	        define("DOC_URL", $A['doc_url']);
	        define("DOCUMENT_TITLE", stripslashes($A['v_value']));
	    }
	}
	
	//Check active rights (0=non-active, 1=active, 2=draft, 3=submission)
	
	if ( $doc['active'] == 0 ) {
	    if (SEC_hasRights('documents.admin') != 1) {
    		echo COM_refresh($_CONF['site_url'] . '/404.php');
		    exit();
		}
	}
	
	if ( $doc['active'] == 3 ) {
	    if (SEC_hasRights('documents.admin') != 1) {
    		return DOCUMENTS_message ($LANG_DOCUMENTS_1['document_submit']);
		}
	}
	
	if ( !SEC_hasRights('documents.admin') && $doc['active'] == 2 && $doc['owner_id'] != $_USER['uid']	) {
	    return DOCUMENTS_message ($LANG_DOCUMENTS_1['document_draft']);
	}

	// check secury access
	$access = SEC_hasAccess($doc['owner_id'], $doc['group_id'],
						$doc['perm_owner'], $doc['perm_group'],
						$doc['perm_members'], $doc['perm_anon']);
	
	if ( $access < 2) {
	    $group_name = DB_getItem($_TABLES['groups'],'grp_name',"grp_id={$doc['group_id']}");
	    if ($doc['perm_members'] == 2) {
		    require_once $_CONF['path'] . '/system/lib-security.php';
			return SEC_loginRequiredForm();
	    } else {
	        return DOCUMENTS_message ($LANG_DOCUMENTS_1['reserved_to'] . ' ' . $group_name, $LANG_DOCUMENTS_1['limited_access']);
		}
	}		

	// Get all fields by order
	
	$sql = "SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id = '{$doc['cid']}' ORDER BY f_order";
	
	$res_fields = DB_query($sql);
	
	// Select template
	
	if ($doc['template'] == '') {
    	$template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
	} else {
	    $template = COM_newTemplate($_CONF['path_data'] . 'data_documents/templates/' . $doc['template']);
		//js and css
		$jsfile = $_CONF['path_data'] . 'data_documents/templates/' . $doc['template'] .  '/scripts.thtml';
		if (file_exists($jsfile)) $_SCRIPTS->setJavaScript(file_get_contents ($jsfile), false);
		
		
	}
	$template->set_file(array('doc' => 'document.thtml',
	                           'comments' => 'doccomments.thtml'));
	$template->set_var('doc_name', DOC_NAME);

	if ( $doc['active'] == 0 ) {
	    $template->set_var('active', '<span style="color:red">' . $LANG_DOCUMENTS_1['not_active'] . '</span> ');
	} else if  ( $doc['active'] == 2 ) {
	    $template->set_var('active', '<span style="color:red">' . $LANG_DOCUMENTS_1['draft'] . '</span> ');
	} else if  ( $doc['active'] == 3 ) {
	    $template->set_var('active', '<span style="color:red">' . $LANG_DOCUMENTS_1['submission'] . '</span> ');
	} else {
	    $template->set_var('active', '');
	}
	
	//edit
	if ( $access == 3) {
    	$edit_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit&doc_url=' . $doc['doc_url'] . '&cat=' . $doc['cid'];
        $template->set_var('editor', COM_createLink('<img src="' . $_CONF['site_url'] . '/layout/' . $_CONF['theme']. '/images/edit.png" align="absmiddle">', $edit_url));
	} else {
	    $template->set_var('editor', '');
	}
	
	// For each field build the template

	while ($field = DB_fetchArray($res_fields)) {
		
		// Build selects arrays
		$doc['selects'] = array();
		
		if ($field['f_type']=='select') {
			// get all items from selects table for this group
			$sql = "SELECT s.*, g.* FROM {$_TABLES['documents_selects']} AS s
				LEFT JOIN {$_TABLES['documents_selects_group']} AS g
				   ON s.s_group = g.gid 
				WHERE s_group = '{$field['sel_id']}' ORDER BY s.s_order";
			$res_selects = DB_query($sql);
			$selects = array();
			
			while ($B = DB_fetchArray($res_selects)) {
				//build selects arrays
				$selects['name'][] = $B['s_value'];
				$selects['value'][] = $B['s_name']; //display
				$field['sel_name'] = $B['g_name'];
			}
			
			$doc['selects'][$field['fid']] = $selects;
			unset($selects);
		} else if ($field['f_type']=='radio') {
			// get all items from selects table for this group
			$sql = "SELECT s.*, g.* FROM {$_TABLES['documents_selects']} AS s
				LEFT JOIN {$_TABLES['documents_selects_group']} AS g
				   ON s.s_group = g.gid 
				WHERE s_group = '{$field['sel_id']}' ORDER BY s.s_order";
			$res_selects = DB_query($sql);
			$selects = array();
			
			while ($B = DB_fetchArray($res_selects)) {
				//build selects arrays
				$selects['name'][] = $B['s_name'];
				$selects['value'][] = $B['s_value']; //display
				$field['sel_name'] = $B['g_name'];
			}
			
			$doc['selects'][$field['fid']] = $selects;
			unset($selects);
		}
		
		// check if field id correspond
		$raws .= DOCUMENTS_buildRawDocument ($field, $doc, $template, $field['fid']);
	}
	if ($doc['template']=='') {
	    $template->set_var('raws', '<table cellpadding="10" class="documents">' . $raws . '</table>');
	} else {
	    $template->set_var('raws', '');
	} 
	
	//Source and stats
	$uid_url = $_CONF['site_url'] .
                                 '/users.php?mode=profile&uid=' . $doc['owner_id'];        
	$user_link = COM_createLink(COM_getDisplayName($doc['owner_id']), $uid_url);
	$template->set_var('user_name', $user_link);
	$template->set_var('doc_by', $LANG_DOCUMENTS_1['doc_by']);
	$template->set_var('displayed', $LANG_DOCUMENTS_1['displayed']);
	$template->set_var('times', $LANG_DOCUMENTS_1['times']);
	$template->set_var('hits', $doc['hits']);
	
	$template->set_var('document_url', $_DOCUMENTS_CONF['site_url'] . '/' . $cat_url . '/' . $doc_url);
	
	
	//Comments
	require_once $_CONF['path_system'] . 'lib-comment.php';
	$template->set_var('commentbar',
                            CMT_userComments(DOC_URL, DOCUMENT_TITLE, 'documents',
                                        'ASC', 'nested', 0, $comment_page, false,
                                        $delete_option, 0));
	
	//$retval .= CMT_commentBar( DOC_URL, DOCUMENT_TITLE, 'documents','ASC', 'nested', 0 );
	$retval .= $template->finish($template->parse('output', 'doc'));
	
	// Meta fb
	$script = '<meta property="og:title" content="' . DOCUMENT_TITLE . '" />
	<meta property="og:description" content="' . DOCUMENT_TITLE . '" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="' . $_DOCUMENTS_CONF['site_url'] . '/' . $cat_url . '/' . $doc_url . '" />
	<meta property="og:image" content="' . MAIN_DOC_IMG . '" />
	<meta property="og:site_name" content="' . $_CONF['site_name'] .'" />
	<meta property="fb:app_id" content="' . $_CONF['facebook_consumer_key'] .'" />';
	
	$_SCRIPTS->setJavaScript($script, false, false);
	
	// Add hits to doc
	DOCUMENTS_hit (DOC_URL);

    return $retval;

}

function DOCUMENTS_buildRawDocument ($field, $doc, &$template, $i) {
    
	global $_CONF, $_DOCUMENTS_CONF, $_MG_CONF, $_PLUGINS, $_TABLES, $_SCRIPTS;
	    
	// Todo handle display_empty field
	if ($doc['v_value'][$i] == '' && $field['f_type'] <> 'checkbox') return;
	
	switch ($field['f_type']) {
		
		
		case 'checkbox' :
			$html .= '<td valign="top">&nbsp;</td>' . LB ;

			$checked = '<img src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/disabled.png" align="top" alt="" /> ';
			if($doc['v_value'][$i] == 1) {
				$checked = '<img src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/enabled.png" align="top" alt="" /> ';
			}
			$content = $checked . '<label class="document_field_right">' . $field['f_name'] . '</label>';
			$html .= '<td class="document_value">' . $content . '</td>' . LB ;
			break;
			
		case 'radio' :
			
			$html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB ;

			$count = count($doc['selects']);
			$checked = '<img src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/enabled.png" align="top" alt="" /> ';
			$select = '';
			for ($it=0; $it<=$count; $it++) {   
				//COM_errorLog ($it . ' | ' . $doc['v_value'][1] . ' | ' . $doc['selects']['name'][$it] . ' | ' . $doc['selects']['value'][$it]);
				if ($doc['v_value'][1] == $doc['selects']['name'][$it]) {
					$select .= $checked . $doc['selects']['value'][$it] . '&nbsp;&nbsp;&nbsp;';
				} else {
					if ($it == 0) {
						$select .= $doc['selects']['value'][$it] . '&nbsp;&nbsp;&nbsp;';
					} else {
						$select .= '&nbsp;&nbsp;&nbsp;' . $doc['selects']['value'][$it] . '&nbsp;&nbsp;&nbsp;';
					}
				}
			}
            $content = $select;
			$html .= '<td class="document_value">' . $content .'</td>' . LB ;
			break;
			
		case 'album' :
		    
			if (in_array('mediagallery', $_PLUGINS)) {
			    $album_name =  DB_getItem($_TABLES['mg_albums'],'album_title',"album_id='{$doc['v_value'][$i]}'");
				if ($album_name != '') {
					$content = '<p><strong><a href="' . $_MG_CONF['site_url'] . '/album.php?aid=' . $doc['v_value'][$i] . '">' . $album_name . '</a></strong></p>';
					$content .= DOCUMENTS_albumGallery($doc['v_value'][$i]);
					$html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB ;
					$html .= '<td class="document_value">' . $content . '</td>' . LB ;
				} else {
				    break;
				}
			} else {
			    $content = '';
				$html .= '';
			}
			break;
			
		case 'category' :
			//TODO display an item from the category
			break;
			
		case 'file' :
			//TODO display a link from the downloads plugin
			break;
		
		case 'marker' :
			if (in_array('maps', $_PLUGINS)) {
				//TODO display a map from the maps plugin
				$html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB ;
				$html .= '<td width="100%" class="document_value">
					<div id="map_canvas" style="width: 100%; height: 400px">
					</div></td>';
				
				//Get marker info 
				$sql = "SELECT *
							FROM {$_TABLES['maps_markers']}
						WHERE mkid = '{$doc['v_value'][$i]}'";
								
				$res = DB_query($sql);
				$marker = DB_fetchArray($res);
				
				$js = LB . '
				<script type="text/javascript">	
					
					var map;

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
						  animation: google.maps.Animation.DROP,
						});
						
					}
					
					google.maps.event.addDomListener(window, \'load\', initializeGMap);
						
					</script>' . LB. LB;
					
				$_SCRIPTS->setJavaScript($js, false);
				$content = '<div id="map_canvas" style="width: 100%; height: 400px">
					</div>';
			} else {
			    $content = '';
				$html .= '';
			}
				
			break;
			
		case 'image':
			$image = '';
			if(is_file($_DOCUMENTS_CONF['path_images'] . $doc['v_value'][$i])) {
			    if ($doc['template'] == '') {
					$img_url = $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . $_DOCUMENTS_CONF['images_url'] .
					   $doc['v_value'][$i] . '&amp;w=450';
					$image = '<img class="document_img_big" src="' . $img_url . '" align="top" alt="' . DOC_NAME . '" />';
				} else {
				    $img_url = $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . $_DOCUMENTS_CONF['images_url'] .
						$doc['v_value'][$i] . '&amp;w=700';
					$image = '<img class="document_img_big" width="100%" src="' . $img_url . '" align="top" alt="' . DOC_NAME . '" />';
				}
				if (!defined('MAIN_DOC_IMG')) {
				    define("MAIN_DOC_IMG", $img_url);
				}
			}
			$html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB ;
			$content = $image;
			$html .= '<td class="document_value">' . $content . '</td>' . LB ;
			break;
		
		
		case 'select' :
			$html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB ;
			$content = $doc['s_name'][$i];
			$html .= '<td class="document_value">' . $content . '</td>' . LB ;
			break;
			
		case 'decimal':
			$decimal['decimal'] = $doc['v_value'][$i];
			DOCUMENTS_filterVars(array('decimal'=>'number'), $decimal);
				$doc['v_value'][$i] = number_format( $decimal['decimal'], $_CONF['decimal_count'], $_CONF['decimal_separator'], $_CONF['thousand_separator']);

			$html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB ;
			$content = $doc['v_value'][$i];
			$html .= '<td class="document_value">' . $content . '</td>' . LB ;
			break;
			
		case 'date':
			//$date = COM_getUserDateTimeFormat($doc['v_value'][$i]);
			//$doc['v_value'][$i] = $date[0];
			//$date = COM_getUserDateTimeFormat($doc['v_value'][$i]);
			try {
				$date = new DateTime($doc['v_value'][$i]);
				$doc['v_value'][$i] = $date->format($_DOCUMENTS_CONF['date']);
			} catch (Exception $e) {
				
			}

			$html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB ;
			$content = $doc['v_value'][$i];
			$html .= '<td class="document_value">' . $content . '</td>' . LB ;
			
			break;
			

		case 'text':
				
		default:
			$html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB ;
			$content =  nl2br( stripslashes($doc['v_value'][$i]));
			// convert link to url
            $content = preg_replace('/((http:\/\/|https:\/\/)[^ |<)]+)/e', "'<a href=\"$1\" target=\"_blank\" title=\"$1\" >'. ((strlen('$1')>=50 ? substr('$1',0,50).'...':'$1')).'</a> '", $content);
			
			$html .= '<td class="document_value">' . $content . '</td>' . LB ;
			break;
		
	}
	
	// For custom template
	$template->set_var($field['var_name'], PLG_replaceTags($content));
	
	$html = '<tr>' . LB . $html . LB;
	$html .= '</tr>' . LB;

    // replace autotag
	$html = PLG_replaceTags($html);
	return $html;
	
}

function DOCUMENTS_albumGallery($album) {
    
	global $MG_albums, $_TABLES, $_CONF, $_MG_CONF, $_DOCUMENTS_CONF, $_SCRIPTS;

    require_once($_CONF['path'] . 'plugins/mediagallery/include/classMedia.php');
	if(!is_numeric($album)) return;

    $album_gallery = '';
	$album_gallery .= '<div id="mg_album_gallery">';
	
	//Fancybox			
    $fancybox = '<script type="text/javascript">jQuery(document).ready(function() {' . LB;
    $fancybox .= 'jQuery(".various").fancybox({
                    \'transitionIn\'	: \'none\',
                    \'transitionOut\'	: \'none\'
                });' . LB;

    $sql = "SELECT * FROM {$_TABLES['mg_media']} AS m LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id WHERE ma.album_id=$album ORDER BY ma.media_order DESC";
    $result = DB_query($sql,1);
    $nRows = DB_numRows($result);

    for ($x = 0; $x < $nRows; $x++) {

	    $row = DB_fetchArray($result);
        if ( $row['media_mime_ext'] == '.bmp' ) continue;
		$media = new Media($row,$row['album_id']);
		$mfn = 'tn/' . $row['media_filename'][0] . '/' . $row['media_filename'];
		$row['media_mime_ext'] = $media->getMediaExt($_MG_CONF['path_mediaobjects'] . $mfn);
        $tn_size = 11; // include:150x150
		$image = $_MG_CONF['mediaobjects_url'] . '/' . $media->getDefaultThumbnail($row, $tn_size);
		$display_image = $_MG_CONF['mediaobjects_url'] . '/disp/' . $row['media_filename'][0] . '/' . $row['media_filename'] . $row['media_mime_ext'];

		$album_gallery .= '<a class="lightbox_' . $row['media_id'] . '" rel="group' . $album . '" href="' . $display_image . '" alt="' . $row['title'] . '" title="'. $row['title'] . '"><img class="documents_photo_gallery" width="100" height="100" src="' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src='
		. $image . '&w=100&h=100&q=90&zc=1" alt="' . $row['title'] . '" title="'. $row['title'] . '" /></a>';
		
		$fancybox .= '    jQuery("a.lightbox_' . $row['media_id'] . '").fancybox( {
                hideOnContentClick : true
            });' . LB;
    }
	
	$album_gallery .= '</div><div style="clear:both;">&nbsp;</div>';
	$fancybox .= '		});</script>' . LB . LB;
	
	$_SCRIPTS->setJavaScriptLibrary('jquery'); 
	$_SCRIPTS->setJavaScriptFile('documents_mousewheel', '/admin/plugins/documents/js/fancybox/jquery.mousewheel-3.0.4.pack.js', true);
    $_SCRIPTS->setJavaScriptFile('documents_fancybox', '/admin/plugins/documents/js/fancybox/jquery.fancybox-1.3.4.pack.js', true,1000);
    $_SCRIPTS->setCSSFile('documents_css_fancybox', '/admin/plugins/documents/js/fancybox/jquery.fancybox-1.3.4.css', false);
    $_SCRIPTS->setJavaScript($fancybox, false);
	
    return $album_gallery;
}


/**
 *  Increment hit counter for ad
 *
 */
function DOCUMENTS_hit ($doc)
{
    global $_TABLES;
    
    DB_query("UPDATE {$_TABLES['documents_docs']} SET hits = hits + 1 WHERE doc_url = '$doc'");
}

/**
 * Converts all accent characters to ASCII characters.
 *
 * If there are no accent characters, then the string given is just returned.
 *
 * @param string $string Text that might have accent characters
 * @return string Filtered string with replaced "nice" characters.
 */

function DOCUMENTS_remove_accents($string) {
 if (!preg_match('/[\x80-\xff]/', $string))
  return $string;
 if (DOCUMENTS_seems_utf8($string)) {
  $chars = array(
  // Decompositions for Latin-1 Supplement
  chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
  chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
  chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
  chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
  chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
  chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
  chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
  chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
  chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
  chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
  chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
  chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
  chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
  chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
  chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
  chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
  chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
  chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
  chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
  chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
  chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
  chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
  chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
  chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
  chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
  chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
  chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
  chr(195).chr(191) => 'y',
  // Decompositions for Latin Extended-A
  chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
  chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
  chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
  chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
  chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
  chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
  chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
  chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
  chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
  chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
  chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
  chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
  chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
  chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
  chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
  chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
  chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
  chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
  chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
  chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
  chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
  chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
  chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
  chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
  chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
  chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
  chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
  chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
  chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
  chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
  chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
  chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
  chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
  chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
  chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
  chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
  chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
  chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
  chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
  chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
  chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
  chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
  chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
  chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
  chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
  chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
  chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
  chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
  chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
  chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
  chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
  chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
  chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
  chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
  chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
  chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
  chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
  chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
  chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
  chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
  chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
  chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
  chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
  chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
  // Euro Sign
  chr(226).chr(130).chr(172) => 'E',
  // GBP (Pound) Sign
  chr(194).chr(163) => '');
  $string = strtr($string, $chars);
 } else {
  // Assume ISO-8859-1 if not UTF-8
  $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
   .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
   .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
   .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
   .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
   .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
   .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
   .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
   .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
   .chr(252).chr(253).chr(255);
  $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
  $string = strtr($string, $chars['in'], $chars['out']);
  $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
  $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
  $string = str_replace($double_chars['in'], $double_chars['out'], $string);
 }
 return $string;
}

/**
 * Checks to see if a string is utf8 encoded.
 *
 * @author bmorel at ssi dot fr
 *
 * @param string $Str The string to be checked
 * @return bool True if $Str fits a UTF-8 model, false otherwise.
 */
function DOCUMENTS_seems_utf8($Str) { # by bmorel at ssi dot fr
 $length = strlen($Str);
 for ($i = 0; $i < $length; $i++) {
  if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
  elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n = 1; # 110bbbbb
  elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n = 2; # 1110bbbb
  elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n = 3; # 11110bbb
  elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n = 4; # 111110bb
  elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n = 5; # 1111110b
  else return false; # Does not match any model
  for ($j = 0; $j < $n; $j++) { # n bytes matching 10bbbbbb follow ?
   if ((++$i == $length) || ((ord($Str[$i]) & 0xC0) != 0x80))
   return false;
  }
 }
 return true;
}

function DOCUMENTS_utf8_uri_encode($utf8_string, $length = 0) {
 $unicode = '';
 $values = array();
 $num_octets = 1;
 $unicode_length = 0;
 $string_length = strlen($utf8_string);
 for ($i = 0; $i < $string_length; $i++) {
  $value = ord($utf8_string[$i]);
  if ($value < 128) {
   if ($length && ($unicode_length >= $length))
    break;
   $unicode .= chr($value);
   $unicode_length++;
  } else {
   if (count($values) == 0) $num_octets = ($value < 224) ? 2 : 3;
   $values[] = $value;
   if ($length && ($unicode_length + ($num_octets * 3)) > $length)
    break;
   if (count( $values ) == $num_octets) {
    if ($num_octets == 3) {
     $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
     $unicode_length += 9;
    } else {
     $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
     $unicode_length += 6;
    }
    $values = array();
    $num_octets = 1;
   }
  }
 }
 return $unicode;
}

/**
 * Sanitizes title, replacing whitespace with dashes.
 *
 * Limits the output to alphanumeric characters, underscore (_) and dash (-).
 * Whitespace becomes a dash.
 *
 * @param string $title The title to be sanitized.
 * @return string The sanitized title.
 */
function DOCUMENTS_slugify($title) {
 $title = strip_tags($title);
 $title = str_replace(array('\'','_'), '-', $title); // kill entities
 // Preserve escaped octets.
 $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
 // Remove percent signs that are not part of an octet.
 $title = str_replace('%', '', $title);
 // Restore octets.
 $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
 $title = DOCUMENTS_remove_accents($title);
 if (DOCUMENTS_seems_utf8($title)) {
  if (function_exists('mb_strtolower')) {
   $title = mb_strtolower($title, 'UTF-8');
  }
  $title = DOCUMENTS_utf8_uri_encode($title, 200);
 }
 $title = strtolower($title);
 $title = preg_replace('/&.+?;/', '', $title); // kill entities
 $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
 $title = preg_replace('/\s+/', '-', $title);
 $title = preg_replace('|-+|', '-', $title);
 $title = trim($title, '-');
 return $title;
}

function DOCUMENTS_uploadImage ($image_name=array(), $input_name=array(), $fields=array(), $creation) {

    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG24, $_USER, $_GROUPS;
	
	require_once($_CONF['path_system'] . 'classes/upload.class.php');
	$upload = new upload();

	//Debug with story debug function
	if (isset ($_CONF['debug_image_upload']) ) {
		$upload->setLogFile ($_CONF['path'] . 'logs/error.log');
		$upload->setDebug (true);
	}
	$upload->setMaxFileUploads (20);
	if (!empty($_CONF['image_lib'])) {
		if ($_CONF['image_lib'] == 'imagemagick') {
			// Using imagemagick
			$upload->setMogrifyPath ($_CONF['path_to_mogrify']);
		} elseif ($_CONF['image_lib'] == 'netpbm') {
			// using netPBM
			$upload->setNetPBM ($_CONF['path_to_netpbm']);
		} elseif ($_CONF['image_lib'] == 'gdlib') {
			// using the GD library
			$upload->setGDLib ();
		}
		$upload->setAutomaticResize(true);
		$upload->keepOriginalImage (false);

		if (isset($_CONF['jpeg_quality'])) {
			$upload->setJpegQuality($_CONF['jpeg_quality']);
		}
	}
	$upload->setAllowedMimeTypes (array (
			'image/gif'   => '.gif',
			'image/jpeg'  => '.jpg,.jpeg',
			'image/pjpeg' => '.jpg,.jpeg',
			'image/x-png' => '.png',
			'image/png'   => '.png'
			));
	
	if (!$upload->setPath($_DOCUMENTS_CONF['path_images'])) {
		$output = COM_siteHeader ('menu', $LANG24[30]);
		$output .= COM_startBlock ($LANG24[30], '', COM_getBlockTemplate ('_msg_block', 'header'));
		$output .= $upload->printErrors (false);
		$output .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
		$output .= COM_siteFooter ();
		COM_output($output);
		exit;
	}

	// NOTE: if $_CONF['path_to_mogrify'] is set, the call below will
	// force any images bigger than the passed dimensions to be resized.
	// If mogrify is not set, any images larger than these dimensions
	// will get validation errors
	$upload->setMaxDimensions($_DOCUMENTS_CONF['max_image_width'], $_DOCUMENTS_CONF['max_image_height']);
	$upload->setMaxFileSize($_DOCUMENTS_CONF['max_image_size']); // size in bytes, 1048576 = 1MB

	// Set file permissions on file after it gets uploaded (number is in octal)
	$upload->setPerms('0644');
	
	$count = count($image_name);
	$i = 0;

	for ($z = 0; $z < $count; $z++) {
	    
		$curfile = $_FILES[$input_name[$z]];

		if (!empty($curfile['name'])) {
			$pos = strrpos($curfile['name'],'.') + 1;
			$fextension = substr($curfile['name'], $pos);
			$filename[$i] = $image_name[$i] . '.' . $fextension;
			$i++;
		} 
	}

	$upload->setFileNames($filename);
	$upload->uploadFiles();

	if ($upload->areErrors()) {
		$retval = COM_siteHeader('menu', $LANG24[30]);
		$retval .= COM_startBlock ($LANG24[30], '',
					COM_getBlockTemplate ('_msg_block', 'header'));
		$retval .= $upload->printErrors(false);
		$retval .= COM_endBlock(COM_getBlockTemplate ('_msg_block', 'footer'));
		$retval .= COM_siteFooter();
		COM_output($retval);
		exit;
	}
	// group id
	$group_id = DB_getItem($_TABLES['groups'], 'grp_id',
                             "grp_name='Documents Admin'");
							 
	//edit
	if($creation == 0) {
		for ($z = 0; $z < $count; $z++) {
			//check if image exists
			$value = $filename[$z];
			$image = DB_getItem($_TABLES['documents_values'],'vid',"v_value='$value'");

			$sql = "v_value='{$filename[$z]}'";
			$sql = "UPDATE {$_TABLES['documents_values']} SET $sql "
				 . "WHERE field_id = '{$fields[$z]['fid']}' AND  doc_url='" . DOC_URL . "' ";

			DB_query($sql);
			COM_errorLog($sql);
		}
	} else {
	    // Default fields
		if ($_REQUEST['perm_owner'] == '') {
			SEC_setDefaultPermissions($_REQUEST, $_DOCUMENTS_CONF['default_permissions']);
		}
		for ($z = 0; $z < $count; $z++) {
			$sql = "v_value='{$filename[$z]}', "
				. "field_id='{$fields[$z]['fid']}', "
				. "doc_url='" . DOC_URL . "', "
				. "owner_id = '{$_USER['uid']}', "
				. "group_id = '{$group_id}', "
				. "perm_owner = '{$_REQUEST['perm_owner']}', "
				. "perm_group = '{$_REQUEST['perm_group']}', "
				. "perm_members = '{$_REQUEST['perm_members']}', "
				. "perm_anon = '{$_REQUEST['perm_anon']}'
				";
			$sql = "INSERT INTO {$_TABLES['documents_values']} SET $sql ";
			DB_query($sql);
		}			
						
	}
	
	return true;
	
}

function DOCUMENTS_saveMarker ($mid, $mkid, $doc_url) {

	global $_TABLES, $_DOCUMENTS_CONF, $_PLUGINS;
	
	if( !in_array('maps', $_PLUGINS) ) {
	    return;
	}
	
	$sql = "SELECT v.field_id, f.f_type, f.sel_id, v.v_value, s.s_value, d.hits, d.doc_url, d.active, d.owner_id , d.group_id, d.perm_owner, d.perm_group, d.perm_members, d.perm_anon, c.cat_url
             	FROM {$_TABLES['documents_values']} AS v
			LEFT JOIN {$_TABLES['documents_fields']} AS f
			  ON f.fid = v.field_id 
			LEFT JOIN {$_TABLES['documents_selects']} AS s
			  ON s.s_name = v.v_value
			LEFT JOIN {$_TABLES['documents_docs']} AS d
			  ON d.doc_url = v.doc_url
			LEFT JOIN {$_TABLES['documents_cat']} AS c
			  ON c.cid = f.cat_id
			WHERE v.doc_url = '{$doc_url}' ORDER BY f.f_order LIMIT 1";
					
	$res = DB_query($sql);
	$A = DB_fetchArray($res);
	$name = addslashes($A['v_value']);
	
	// prepare strings for insertion
	$created = date("YmdHis");
	$modified = date("YmdHis");
	$from = date("Ymd");
	$to = date("Ymd");
	$web = $_DOCUMENTS_CONF['documents_folder'] . '/' . $A['cat_url'] . '/' . $doc_url;
    $_REQUEST['address'] = addslashes($_REQUEST['address']);
	
	$lat = strval ($_REQUEST['lat']);
	$lng = strval ($_REQUEST['lng']);

	
	// Convert array values to numeric permission values
	if (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {
	list($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']) = SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);
	}
 
	// check if marker exists
	$marker_exist = DB_getItem($_TABLES['maps_markers'],'mid',"mkid='{$mkid}'");
	
	if ( $mkid != '' && $marker_exist != '') { //edit marker

		//TODO check perm user
		
		$sql = "name = '{$name}', "
		 . "modified = '{$modified}', "
		 . "address = '{$_REQUEST['address']}', "
		 . "lat = '{$lat}', "
		 . "lng = '{$lng}', "
		 . "mid = '{$mid}', "
		 . "url = '{$web}', "
		 . "type = 'documents', "
		 . "owner_id = '{$_REQUEST['owner_id']}', "
		 . "group_id = '{$_REQUEST['group_id']}', "
		 . "perm_owner = '{$_REQUEST['perm_owner']}', "
		 . "perm_group = '{$_REQUEST['perm_group']}', "
		 . "perm_members = '{$_REQUEST['perm_members']}', "
		 . "perm_anon = '{$_REQUEST['perm_anon']}'";
		 
		$sql = "UPDATE {$_TABLES['maps_markers']} SET $sql "
			 . "WHERE mkid = {$mkid}";
	} else { // create marker
	
		$mkid = COM_makeSid ();

		$sql = "mkid = '{$mkid}', "
		 . "name = '{$name}', "
		 . "created = '{$created}', "
		 . "modified = '{$modified}', "
		 . "address = '{$_REQUEST['address']}', "
		 . "lat = '{$lat}', "
		 . "lng = '{$lng}', "
		 . "mid = '{$mid}', "
		 . "url = '{$web}', "
		 . "type = 'documents', "
		 . "owner_id = '{$_REQUEST['owner_id']}', "
		 . "group_id = '{$_REQUEST['group_id']}', "
		 . "perm_owner = '{$_REQUEST['perm_owner']}', "
		 . "perm_group = '{$_REQUEST['perm_group']}', "
		 . "perm_members = '{$_REQUEST['perm_members']}', "
		 . "perm_anon = '{$_REQUEST['perm_anon']}'";
		 
		$sql = "INSERT INTO {$_TABLES['maps_markers']} SET $sql ";
	}
	
	DB_query($sql);
	updateMap($mid);
	
	return $mkid;
}

//Filter vars (alpha, number, text, html)

$vars = array('mode'           => 'alpha',
              'op'             => 'alpha',
              'cat'            => 'alpha',
			  'doc'            => 'alpha',
			  'cid'            => 'number',
			  'cat_name'       => 'text',
              'cat_url'        => 'alpha',
              'cat_order'      => 'number',
              'css'            => 'alpha',
			  'map'            => 'number',
              'template'       => 'alpha',
              'list_index'     => 'number',
              'submitable'     => 'number',
              'cat_help'       => 'text',
              'custom_header'  => 'text',
              'custom_footer'  => 'text',
			  'owner_id'       => 'number',
			  'group_id'       => 'number',			  
			  'perm_owner[0]'  => 'number',
              'perm_owner[1]'  => 'number',
              'perm_group[0]'  => 'number',
              'perm_group[1]'  => 'number',
              'perm_members[0]' => 'number',
              'perm_anon[0]'   => 'number',
			  'field'          => 'number',
			  'fid'            => 'number',
			  'cat_id'         => 'number',
              'f_name'         => 'text',
              'f_order'        => 'number',
              'f_type'         => 'alpha',
              'sel_id'         => 'number',
              'var_name'       => 'alpha',
              'f_help'         => 'text',
              'f_required'     => 'number',
			  'f_on_list'      => 'number',
			  'doc_url'        => 'alpha',
			  'group'          => 'number',
			  'select'         => 'number',
			  'group_name'     => 'text',
			  'group_help'     => 'text',
			  'gid'            => 'number',
			  'sid'            => 'number',
			  's_name'         => 'alpha',
			  's_value'        => 'text',
			  's_order'        => 'number',
			  's_group'        => 'number',
			  'active'         => 'number',
			  'address'        => 'text',
			  'lat'            => 'alpha',
			  'lng'            => 'alpha',
			  'mkid'           => 'number',
              );
			  
DOCUMENTS_filterVars($vars, $_REQUEST);
DOCUMENTS_filterVars($vars, $_GET);

$display = '';

// MAIN

switch ($_REQUEST['mode']) {

	case 'view':
	
	    if ($_REQUEST['cat'] != '') {

	        if ($_REQUEST['doc'] != '') {
				
				// Display doc
				//echo $_REQUEST['doc'];exit;
				
				$content = DOCUMENTS_displayDocument( $_REQUEST['cat'], $_REQUEST['doc']);

				break;
				
			} else {
			    
				// Display list of docs
				
				require_once ($_CONF['path']  . 'plugins/documents/include_lists.php');
				$content = DOCUMENTS_listDocs($_REQUEST['cat']);
				
				break;
			}
		} else {
			echo COM_refresh($_CONF['site_url'] . '/404.php');
            exit();
		}
		
	case 'edit_cat' :
	    if (SEC_hasRights('documents.admin')) {
		    require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
			
			// Get the category to edit and display the form
			
			if (is_numeric($_REQUEST['cat'])) {
				$sql = "SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = {$_REQUEST['cat']}";
				$res = DB_query($sql);
				$cat = DB_fetchArray($res);
			} else {
			    $cat = array();
			}
	        $content = DOCUMENTS_editCat($cat);
			break;
	    } else {
	       echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
	    }
		
	case 'save_cat':
			
		if (SEC_hasRights('documents.admin')) {
			
			require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
			
			$missingfields = DOCUMENTS_missingFieldCat();
			
			if ($missingfields != '') {
				$content = COM_startBlock($LANG_DOCUMENTS_1['error']);
				$content .= $LANG_DOCUMENTS_1['missing_field'];
				$content .= '<ul>';
				foreach ($missingfields as $i => $value) {
					$content .= '<li>' . ($missingfields[$i]);
				}
				$content .= '</ul>';
				$content .= COM_endBlock();
				$content .= DOCUMENTS_editCat($_REQUEST);
				break;
			}
			
			// Prepare strings for insertion
			
            // Todo check if cat_url is well formated			
			$_REQUEST['cat_url'] = urlencode($_REQUEST['cat_url']);
			
			// Convert array values to numeric permission values
			
			if (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {
				list($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']) 
				= SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);
		    }
			
			( empty($_REQUEST['cat_order']) ) ? $_REQUEST['cat_order'] = 0 : 0;

			if ( (!empty($_REQUEST['cid'])) && (is_numeric($_REQUEST['cid'])) ) {
				
				//Edit mode 
				
				$sql = "cat_name = '{$_REQUEST['cat_name']}', "
				 . "cat_url = '{$_REQUEST['cat_url']}', "
				 . "cat_order = '{$_REQUEST['cat_order']}', "
				 . "css = '{$_REQUEST['css']}', "
				 . "map = '{$_REQUEST['map']}', "
				 . "template = '{$_REQUEST['template']}', "
				 . "list_index = '{$_REQUEST['list_index']}', "
				 . "submitable = '{$_REQUEST['submitable']}', "
				 . "cat_help = '{$_REQUEST['cat_help']}', "
				 . "custom_header = '{$_REQUEST['custom_header']}', "
				 . "custom_footer = '{$_REQUEST['custom_footer']}', "
				 . "owner_id = '{$_REQUEST['owner_id']}', "
				 . "group_id = '{$_REQUEST['group_id']}', "
				 . "perm_owner = '{$_REQUEST['perm_owner']}', "
				 . "perm_group = '{$_REQUEST['perm_group']}', "
				 . "perm_members = '{$_REQUEST['perm_members']}', "
				 . "perm_anon = '{$_REQUEST['perm_anon']}'
				 ";
				$sql = "UPDATE {$_TABLES['documents_cat']} SET $sql "
					 . "WHERE cid = {$_REQUEST['cid']}";
			} else {
				
				//Create mode				

				$sql = "cat_name = '{$_REQUEST['cat_name']}', "
				 . "cat_url = '{$_REQUEST['cat_url']}', "
				 . "cat_order = '{$_REQUEST['cat_order']}', "
				 . "css = '{$_REQUEST['css']}', "
				 . "map = '{$_REQUEST['map']}', "
				 . "template = '{$_REQUEST['template']}', "
				 . "list_index = '{$_REQUEST['list_index']}', "
				 . "submitable = '{$_REQUEST['submitable']}', "
				 . "cat_help = '{$_REQUEST['cat_help']}', "
				 . "custom_header = '{$_REQUEST['custom_header']}', "
				 . "custom_footer = '{$_REQUEST['custom_footer']}', "
				 . "owner_id = '{$_REQUEST['owner_id']}', "
				 . "group_id = '{$_REQUEST['group_id']}', "
				 . "perm_owner = '{$_REQUEST['perm_owner']}', "
				 . "perm_group = '{$_REQUEST['perm_group']}', "
				 . "perm_members = '{$_REQUEST['perm_members']}', "
				 . "perm_anon = '{$_REQUEST['perm_anon']}'
				 ";
				$sql = "INSERT INTO {$_TABLES['documents_cat']} SET $sql ";
			}
			DB_query($sql);
			if (DB_error()) {
				$msg = $LANG_DOCUMENTS_1['save_fail'];
			} else {
				$msg = $LANG_DOCUMENTS_1['save_success'];
			}
			
			DOCUMENTS_reorderCategories();	
			
			// Save complete, return to cat list
			
			echo COM_refresh($_DOCUMENTS_CONF['site_url'] . "/index.php?msg=" . urlencode($msg) );
			exit();

		} else {
	       echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
	    }
	
    case 'edit_field' :
	    if (SEC_hasRights('documents.admin')) {
		    require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
			
			// Get the field to edit and display the form
			
			if (is_numeric($_REQUEST['field'])) {
				$sql = "SELECT * FROM {$_TABLES['documents_fields']} WHERE fid = {$_REQUEST['field']}";
				$res = DB_query($sql);
				$field = DB_fetchArray($res);
			} else {
			    $field = array();
			}
	        $content = DOCUMENTS_editField($field);
			break;
	    } else {
	       echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
	    }
		break;
		
	case 'save_field':
			
		if (SEC_hasRights('documents.admin')) {
			
			require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
			
			// Delete field
			$new = -1;
			if ($_REQUEST['op'] == 'delete' && !empty($_REQUEST['fid']) && is_numeric($_REQUEST['fid'])) {
			   
			    // delete the field from documents_fields table
			    DB_query ("DELETE FROM {$_TABLES['documents_fields']} WHERE fid = ". $_REQUEST['fid']);
			   
			    // delete all fields from the documents_values table
			    DB_query ("DELETE FROM {$_TABLES['documents_values']} WHERE field_id = ". $_REQUEST['fid']);
			    
				// delete complete, return to field list
				if (DB_error()) {
					$msg = $LANG_DOCUMENTS_1['delete_fail'];
				} else {
					$msg = $LANG_DOCUMENTS_1['delete_success'];
				}
				
				echo COM_refresh($_DOCUMENTS_CONF['site_url'] . "/index.php?mode=list_fields&msg=" . urlencode($msg) );
				exit();
				break; // end of save or edit field
			   
			} else {
						
				$missingfields = DOCUMENTS_missingField($_REQUEST);
				if ($missingfields != '') {
					$display .= COM_startBlock($LANG_DOCUMENTS_1['error']);
					$display .= $LANG_DOCUMENTS_1['missing_field'];
					$display .= '<ul>';
					foreach ($missingfields as $i => $value) {
						$display .= '<li>' . ($missingfields[$i]);
					}
					$display .= '</ul>';
					$display .= COM_endBlock();
					$display .= DOCUMENTS_editField($_REQUEST);
					break;
				}
				
				// Prepare strings for insertion
				
				$_REQUEST['f_name'] = addslashes(COM_getTextContent($_REQUEST['f_name']));
				$_REQUEST['f_help'] = addslashes(COM_getTextContent($_REQUEST['f_help']));

				// Convert array values to numeric permission values
				
				if (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {
					list($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']) 
					= SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);
				}
				
				( empty($_REQUEST['f_order']) ) ? $_REQUEST['f_order'] = 0 : 0;

				if ( (!empty($_REQUEST['fid'])) && (is_numeric($_REQUEST['fid'])) ) {
					
					// Todo query if f_type or sel_id change then update existing documents
					
					//Edit mode 
					$sql = "f_name = '{$_REQUEST['f_name']}', "
					 . "cat_id = '{$_REQUEST['cat_id']}', "
					 . "f_order = '{$_REQUEST['f_order']}', "
					 . "f_type = '{$_REQUEST['f_type']}', "
					 . "sel_id = '{$_REQUEST['sel_id']}', "
					 . "var_name = '{$_REQUEST['var_name']}', "
					 . "f_help = '{$_REQUEST['f_help']}', "
					 . "f_on_list = '{$_REQUEST['f_on_list']}', "
					 . "f_required = '{$_REQUEST['f_required']}', "
					 . "owner_id = '{$_REQUEST['owner_id']}', "
					 . "group_id = '{$_REQUEST['group_id']}', "
					 . "perm_owner = '{$_REQUEST['perm_owner']}', "
					 . "perm_group = '{$_REQUEST['perm_group']}', "
					 . "perm_members = '{$_REQUEST['perm_members']}', "
					 . "perm_anon = '{$_REQUEST['perm_anon']}'
					 ";
					$sql = "UPDATE {$_TABLES['documents_fields']} SET $sql "
						 . "WHERE fid = {$_REQUEST['fid']}";
					$new = 0;
					
				
				} else {
					//Create mode				

					$sql = "f_name = '{$_REQUEST['f_name']}', "
					 . "cat_id = '{$_REQUEST['cat_id']}', "
					 . "f_order = '{$_REQUEST['f_order']}', "
					 . "f_type = '{$_REQUEST['f_type']}', "
					 . "sel_id = '{$_REQUEST['sel_id']}', "
					 . "var_name = '{$_REQUEST['var_name']}', "
					 . "f_help = '{$_REQUEST['f_help']}', "
					 . "f_required = '{$_REQUEST['f_required']}', "
					 . "f_on_list = '{$_REQUEST['f_on_list']}', "
					 . "owner_id = '{$_REQUEST['owner_id']}', "
					 . "group_id = '{$_REQUEST['group_id']}', "
					 . "perm_owner = '{$_REQUEST['perm_owner']}', "
					 . "perm_group = '{$_REQUEST['perm_group']}', "
					 . "perm_members = '{$_REQUEST['perm_members']}', "
					 . "perm_anon = '{$_REQUEST['perm_anon']}'
					 ";
					 
					$sql = "INSERT INTO {$_TABLES['documents_fields']} SET $sql ";
					$new = 1;
				}
				DB_query($sql);
				if (DB_error()) {
					$msg = $LANG_DOCUMENTS_1['save_fail'];
				} else {
					$msg = $LANG_DOCUMENTS_1['save_success'];
				}
				
				// Add new field to existing documents
				
				if ($new == 1) {
					$last_id = DB_insertId();

					// Get all documents from this category
					
					$sql = "SELECT v.doc_url
						FROM {$_TABLES['documents_values']} AS v
						LEFT JOIN {$_TABLES['documents_fields']} AS f
						  ON f.fid = v.field_id
						WHERE f.cat_id= '{$_REQUEST['cat_id']}' AND f.f_order=10";
		
					$res = DB_query($sql);

					while ($A = DB_fetchArray($res)) {
						$sql = "field_id = '{$last_id}', "
							 . "v_value = '', "
							 . "doc_url = '{$A['doc_url']}'
							 ";
						$sql = "INSERT INTO {$_TABLES['documents_values']} SET $sql ";
						DB_query($sql);
					}
				} else {
					// Todo update existing documents
				}
				
				DOCUMENTS_reorderFields($_REQUEST['cat_id']);	
				
				// Save complete, return to field list
				
				echo COM_refresh($_DOCUMENTS_CONF['site_url'] . "/index.php?mode=list_fields&msg=" . urlencode($msg) );
				exit();
				break; // end of save or edit field
			}
		} else {
	       // User is not document admin
		   echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
	    }
	
	case 'edit_group' :
	    
		if (SEC_hasRights('documents.admin')) {
		    require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
			
			// Get the group to edit and display the form
			
			if (is_numeric($_REQUEST['group'])) {
				$sql = "SELECT * FROM {$_TABLES['documents_selects_group']} WHERE gid = {$_REQUEST['group']}";
				$res = DB_query($sql);
				$group = DB_fetchArray($res);
			} else {
			    $group = array();
			}
	        $content = DOCUMENTS_editGroup($group);
			break;
	    } else {
	       echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
	    }
		
		break;
	
	case 'save_group' :
	    
		if (SEC_hasRights('documents.admin')) {
			
			require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
			
			if ($_REQUEST['group_name'] == '') {
				$content = COM_startBlock($LANG_DOCUMENTS_1['error']);
				$content .= $LANG_DOCUMENTS_1['missing_field'];
				$content .= '<ul>';
				$content .= '<li>' . $LANG_DOCUMENTS_1['group_name'];
				$content .= '</ul>';
				$content .= COM_endBlock();
				$content .= DOCUMENTS_editGroup($_REQUEST);
				break;
			}
			
			// Prepare strings for insertion
			
			$_REQUEST['group_name'] = addslashes($_REQUEST['group_name']);	
			$_REQUEST['group_help'] = addslashes($_REQUEST['group_help']);	

			if ( (!empty($_REQUEST['gid'])) && (is_numeric($_REQUEST['gid'])) ) {
				
				//Edit mode 
				
				$sql = "g_name = '{$_REQUEST['group_name']}', "
                    .  "g_help  = '{$_REQUEST['group_help']}'		
				 ";
				$sql = "UPDATE {$_TABLES['documents_selects_group']} SET $sql "
					 . "WHERE gid = {$_REQUEST['gid']}";
			} else {
				
				//Create mode				

				$sql = "g_name = '{$_REQUEST['group_name']}', "
				 .  "g_help  = '{$_REQUEST['group_help']}'
				 ";
				$sql = "INSERT INTO {$_TABLES['documents_selects_group']} SET $sql ";
			}
			DB_query($sql);
			if (DB_error()) {
				$msg = $LANG_DOCUMENTS_1['save_fail'];
			} else {
				$msg = $LANG_DOCUMENTS_1['save_success'];
			}
			
			// Save complete, return to group list
			
			echo COM_refresh($_DOCUMENTS_CONF['site_url'] . "/index.php?mode=list_groups&msg=" . urlencode($msg) );
			exit();

		} else {
	       echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
	    }
		
		break;
		
	case 'edit_select' :
	    
		if (SEC_hasRights('documents.admin')) {
		    require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
			
			// Get the select to edit and display the form
			
			if (is_numeric($_REQUEST['select'])) {
				$sql = "SELECT * FROM {$_TABLES['documents_selects']} WHERE sid = {$_REQUEST['select']}";
				$res = DB_query($sql);
				$select = DB_fetchArray($res);
			} else {
			    $select = array();
			}
	        $content = DOCUMENTS_editSelect($select);
			break;
	    } else {
	       echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
	    }
		
		break;
		break;
	
	case 'save_select' :
	    
		if (SEC_hasRights('documents.admin')) {
			
			require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
			
			if ($_REQUEST['s_name'] == '') {
				$content = COM_startBlock($LANG_DOCUMENTS_1['error']);
				$content .= $LANG_DOCUMENTS_1['missing_field'];
				$content .= '<ul>';
				$content .= '<li>' . $LANG_DOCUMENTS_1['s_name'];
				$content .= '</ul>';
				$content .= COM_endBlock();
				$content .= DOCUMENTS_editSelect($_REQUEST);
				break;
			}
			
			// Prepare strings for insertion
			
			$_REQUEST['s_name'] = addslashes($_REQUEST['s_name']);	
			$_REQUEST['s_value'] = addslashes($_REQUEST['s_value']);	

			if ( (!empty($_REQUEST['sid'])) && (is_numeric($_REQUEST['sid'])) ) {
				
				//Edit mode 
				
				$sql = "s_name = '{$_REQUEST['s_name']}', "
                    .  "s_value  = '{$_REQUEST['s_value']}', "		
                    .  "s_group  = '{$_REQUEST['s_group']}', " 
                    .  "s_order  = '{$_REQUEST['s_order']}'		
				 ";
				 
				$sql = "UPDATE {$_TABLES['documents_selects']} SET $sql "
					 . "WHERE sid = {$_REQUEST['sid']}";
			} else {
				
				//Create mode				

				$sql = "s_name = '{$_REQUEST['s_name']}', "
                    .  "s_value  = '{$_REQUEST['s_value']}', "		
                    .  "s_group  = '{$_REQUEST['s_group']}', " 
                    .  "s_order  = '{$_REQUEST['s_order']}'		
				 ";
				 
				$sql = "INSERT INTO {$_TABLES['documents_selects']} SET $sql ";
			}
			DB_query($sql);
			if (DB_error()) {
				$msg = $LANG_DOCUMENTS_1['save_fail'];
			} else {
				$msg = $LANG_DOCUMENTS_1['save_success'];
			}
			
			DOCUMENTS_reorderSelects();
			
			// Save complete, return to group list
			
			echo COM_refresh($_DOCUMENTS_CONF['site_url'] . "/index.php?mode=list_selects&group=" .  $_REQUEST['s_group'] . "&msg=" . urlencode($msg) );
			exit();

		} else {
	       echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
	    }
		
		break;
			
	case 'list_fields' :
	    require_once ($_CONF['path']  . 'plugins/documents/include_lists.php');
	    $content .= DOCUMENTS_listFields(0);
		break;
		
	case 'list_groups' :
	    require_once ($_CONF['path']  . 'plugins/documents/include_lists.php');
	    $content .= DOCUMENTS_listGroups(0);
		break;
		
	case 'list_selects' :
	    require_once ($_CONF['path']  . 'plugins/documents/include_lists.php');
	    $content .= DOCUMENTS_listSelects($_REQUEST['group']);
		break;
	
	case 'new' :
	    
		require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');

		// Check if submitable
		
		if ($_USER['uid'] < 2 || COM_isAnonUser()) {
		    // anonymous can't submit doc
            // Todo make this customisable			
			$content = SEC_loginRequiredForm();
			break;
		} else if (isset($_GET['cat']) && $_REQUEST['cat'] !='') {
			$sql = "SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url = '{$_REQUEST['cat']}'";
			$res = DB_query($sql);
			$cat = DB_fetchArray($res);
			if ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {
			   echo COM_refresh($_CONF['site_url'] . '/404.php');
			   exit();
			}
		} else {
	       echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
		}
		
	    $doc['cid'] = $cat['cid'];
		$doc['cat_name'] = $cat['cat_name'];
		$doc['cat_url'] = $cat['cat_url'];
		$doc['cat_order'] = $cat['cat_order'];
		$doc['css'] = $cat['css'];
		$doc['template'] = $cat['template'];
		$doc['list_index'] = $cat['list_index'];
		$doc['submitable'] = $cat['submitable'];
		$doc['cat_help'] = $cat['cat_help'];
		$doc['custom_header'] = $cat['custom_header'];
		$doc['custom_footer'] = $cat['custom_footer'];
		$doc['owner_id'] = $cat['owner_id'];
		$doc['group_id'] = $cat['group_id'];
		$doc['perm_owner'] = $cat['perm_owner'];
		$doc['perm_group'] = $cat['perm_group'];
		$doc['perm_members'] = $cat['perm_members'];
		$doc['perm_anon'] = $cat['perm_anon'];
		$doc['active'] = 1; //default
		
		$content = DOCUMENTS_editDoc($doc);
		
		break;
		
	case 'edit' :
	
		if (isset($_GET['doc_url']) &&  $_GET['doc_url']!= '') {
		    
			//Edit mode
			
			if (!defined("DOC_URL")) {
                   define("DOC_URL", $_GET['doc_url']);
			}
			
			$sql = "SELECT v.field_id, f.fid, f.f_type, f.sel_id, v.v_value, d.doc_url, d.active, d.owner_id , d.group_id, d.perm_owner, d.perm_group, d.perm_members, d.perm_anon 
			           FROM {$_TABLES['documents_values']} AS v
			        LEFT JOIN {$_TABLES['documents_fields']} AS f
			           ON f.fid = v.field_id
                    LEFT JOIN {$_TABLES['documents_docs']} AS d
			           ON d.doc_url = v.doc_url					   
					WHERE v.doc_url = '{$_GET['doc_url']}' ORDER BY f.f_order";
			$res = DB_query($sql);
			
			// Build doc array
			
			while ($A = DB_fetchArray($res)) {
				$doc['field_id'][$A['field_id']] = $A['field_id'];
			    $doc['f_type'][$A['field_id']] = $A['f_type'];
			    $doc['sel_id'][$A['field_id']] = $A['sel_id'];
			    $doc['v_value'][$A['field_id']] = $A['v_value'];
			    $doc['doc_url'] = $A['doc_url'];
				$doc['active'] = $A['active'];
				$doc['owner_id'] = $A['owner_id'];
				$doc['group_id'] = $A['group_id'];
				$doc['perm_owner'] = $A['perm_owner'];
				$doc['perm_group'] = $A['perm_group'];
				$doc['perm_members'] = $A['perm_members'];
				$doc['perm_anon'] = $A['perm_anon'];
			}
			
			// check secury access
			$access = SEC_hasAccess($doc['owner_id'], $doc['group_id'],
                                $doc['perm_owner'], $doc['perm_group'],
                                $doc['perm_members'], $doc['perm_anon']);
			
			if ( $access < 3) {
			   echo COM_refresh($_CONF['site_url'] . '/404.php');
			   exit();
			}
			
			if (isset($_GET['cat']) && $_REQUEST['cat'] !='') {

				$sql = "SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = '{$_REQUEST['cat']}'";
				$res = DB_query($sql);
				$cat = DB_fetchArray($res);
				if ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {
				   echo COM_refresh($_CONF['site_url'] . '/404.php');
				   exit();
				}
				if (!defined("CAT_URL")) {
				   define("CAT_URL", $cat['cat_url']);
				}
				if (!defined("CAT_NAME")) {
				   define("CAT_NAME", $cat['cat_name']);
				}
			} else {
			   echo COM_refresh($_CONF['site_url'] . '/404.php');
			   exit();
			}
			
		} else {
			echo COM_refresh($_CONF['site_url'] . '/404.php');
		    exit();
		}
		
		$doc['cid'] = $_REQUEST['cat'];
		$doc['cat_name'] = $cat['cat_name'];
		$doc['cat_url'] = $cat['cat_url'];
		$doc['cat_order'] = $cat['cat_order'];
		$doc['css'] = $cat['css'];
		$doc['template'] = $cat['template'];
		$doc['list_index'] = $cat['list_index'];
		$doc['submitable'] = $cat['submitable'];
		$doc['cat_help'] = $cat['cat_help'];
		$doc['custom_header'] = $cat['custom_header'];
		$doc['custom_footer'] = $cat['custom_footer'];

		
		require_once ($_CONF['path']  . 'plugins/documents/include_edit.php');
		$content = DOCUMENTS_editDoc($doc);
		
	    break;
		
	case 'save' :
	    

		if(!SEC_checkToken()) {
    		echo COM_refresh($_DOCUMENTS_CONF['site_url']);
	    	exit();
		}
		
		//Delete action
		if ($_REQUEST['op'] == 'delete' ) {
			if ( !SEC_hasRights('documents.admin') ) {
			    echo COM_refresh($_CONF['site_url'] . '/404.php');
			    exit();
			}
			//Delete document
			DB_delete($_TABLES['documents_docs'], 'doc_url', $_REQUEST['doc_url']);
			//Delete all fields
			DB_delete($_TABLES['documents_values'], 'doc_url', $_REQUEST['doc_url']);
			
			if( in_array('maps', $_PLUGINS) ) {
    			//Delete marker
			    DB_delete($_TABLES['maps_markers'], 'mkid', $_REQUEST['mkid']);
			}
			// TODO language
			$content = "The document was deleted";
		
		    break;
		}
		
		//Save action
		if (isset($_REQUEST['cid']) &&  $_REQUEST['cid']> 0) {
		    
			// Get category
			
			$sql = "SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = {$_REQUEST['cid']}";
			$res = DB_query($sql);
			$cat = DB_fetchArray($res);
			if ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {
			   echo COM_refresh($_CONF['site_url'] . '/404.php');
			   exit();
			}
		    
			// Get fields
			
			$sql = "SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id = {$_REQUEST['cid']} ORDER BY f_order ASC";
			$fields = DB_query($sql);
			
			// Todo check missing fields
			
			// For each field save value
			
			while ($A = DB_fetchArray($fields)) {

				// Todo clean values and security check
				if (isset($_REQUEST['doc_url']) &&  $_REQUEST['doc_url']!= '') {
				    
					// Field edition
					$creation = 0;
					if (!defined("DOC_URL")) {
						define("DOC_URL", $_REQUEST['doc_url']);
						//define("DOC_URL", strtolower(DOCUMENTS_slugify($_REQUEST['doc_url'])));
					}
					
				    // Todo make doc_url customisable
					$value = addslashes($_REQUEST[$A['var_name']]);
					
					// Todo check decimal to allow only decimal 
					
					// image
					if($A['f_type'] == 'image') {
						$name = 'file' . $A['fid'];
						if(is_uploaded_file($_FILES[$name]['tmp_name'])) {
							
							$image_names[] = $_REQUEST['doc_url'] . '-' . $A['fid'];
							$input_names[] = $name;
							$image_fields[] = $A;
							
						}					
					} else {
					    $fid = DB_getItem($_TABLES['documents_values'],'field_id',"doc_url='{$_REQUEST['doc_url']}' AND field_id={$A['fid']}");
						
						if ($fid == '') {   
							//Value is missing in DB
							
							if($A['f_type'] == 'marker') {
							   //Get map id
							   $mid = DB_getItem($_TABLES['documents_cat'],'map',"cat_url='{$cat['cat_url']}'");
							   //Create marker
							   $A['fid'] = DOCUMENTS_saveMarker ($mid, $_REQUEST['mkid'], $_REQUEST['doc_url']);
							}
							//TODO check missing perms
							$sql = "v_value='{$value}', "
							. "field_id='{$A['fid']}', "
							. "doc_url='" . DOC_URL . "', "
							. "owner_id = '{$_REQUEST['owner_id']}', "
							. "group_id = '{$_REQUEST['group_id']}'
							";
						
						    $sql = "INSERT INTO {$_TABLES['documents_values']} SET $sql ";
							COM_errorLog('DOCUMENTS - Value was missing in DB: '. $_REQUEST['doc_url'] . ' and field_id: ' .$A['fid']);
							
						} else {
						    // Value exists update it

                            if($A['f_type'] == 'marker') {
							   //Get map id
							   $mid = DB_getItem($_TABLES['documents_cat'],'map',"cat_url='{$cat['cat_url']}'");
							   //Create marker
							   $value = DOCUMENTS_saveMarker ($mid, $_REQUEST['mkid'], $_REQUEST['doc_url']);
							}
							
							$sql = "v_value='{$value}'";
							$sql = "UPDATE {$_TABLES['documents_values']} SET $sql "
							 . "WHERE field_id = '{$A['fid']}' AND  doc_url='{$_REQUEST['doc_url']}' ";
					    }
													
						if (DB_query($sql) != 1) COM_errorLog('Error during record: '. $_REQUEST['doc_url'] . ' and field_id: ' .$A['fid']);
					}
					
				} else {
					
					// Field creation mode
					$creation = 1;
					// Active 1: online, 2: draft 3: submission
					$active = 3; 
					
					// group id
					$group_id = DB_getItem($_TABLES['groups'], 'grp_id',
                             "grp_name='Documents Admin'");
					
					//Check if doc url is unique
					$unique = DB_getItem($_TABLES['documents_docs'],'MAX(did)',"1=1") + 1;
					
					if (!defined("DOC_URL")) {
					    
						// Todo use $_REQUEST
						/*
						if ($_POST[$A['var_name']] == '' ) {
						    $content = DOCUMENTS_editDoc($_REQUEST);
							break;
						}
						*/
						$doc_url = $_POST[$A['var_name']];
						$doc_url = DOCUMENTS_slugify($doc_url);
						define("DOC_URL", $unique . '-' . strtolower($doc_url));
					}
					
					// Default fields
					if ($_REQUEST['perm_owner'] == '') {
						SEC_setDefaultPermissions($_REQUEST, $_DOCUMENTS_CONF['default_permissions']);
					}
					
					// Convert array values to numeric permission values
					if (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {
						list($perm_owner, $perm_group, $perm_members, $perm_anon) 
						= SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);
					}
					
					// Todo check decimal to allow only decimal 
					// image
					if($A['f_type'] == 'image') {
						$name = 'file' . $A['fid'];
						$image_names[] = DOC_URL . '-' . $A['fid'];
						$input_names[] = $name;
						$image_fields[] = $A;
				
					} else {
					
						if($A['f_type'] == 'checkbox') {
						    ($_REQUEST[$A['var_name']] == 1 ) ? $value = 1 : $value = 0;
						} else { 
						    if($A['f_type'] == 'marker') {
							   //Get map id
							   $mid = DB_getItem($_TABLES['documents_cat'],'map',"cat_url='{$cat['cat_url']}'");
							   //Create marker
							   $value = DOCUMENTS_saveMarker ($mid, $_REQUEST['mkid'], DOC_URL);
							} else {
						        $value = addslashes($_REQUEST[$A['var_name']]);
							}
						}
						//Todo fix permission regarding field permission
						$sql = "v_value='{$value}', "
							. "field_id='{$A['fid']}', "
							. "doc_url='" . DOC_URL . "', "
							. "owner_id = '{$_USER['uid']}', "
							. "group_id = '{$group_id}', "
							. "perm_owner = '{$perm_owner}', "
							. "perm_group = '{$perm_group}', "
							. "perm_members = '{$perm_members}', "
							. "perm_anon = '{$perm_anon}'
							";
						
						$sql = "INSERT INTO {$_TABLES['documents_values']} SET $sql ";
						DB_query($sql);
					}
				}
			}
			//End of field saving
			
			//upload and record images			
			DOCUMENTS_uploadImage($image_names, $input_names, $image_fields, $creation);

			// Record or update the documents db
			
            if ($creation == 1) {

    			// Submission
					
				if (SEC_hasRights('documents.admin') || SEC_hasRights('documents.publish')) {
					$active = $_REQUEST['active'];
					// Todo if user is publisher email doc_url to admin
					if (!SEC_hasRights('documents.admin')) {
					    ($_REQUEST['active'] == 2) ? $active = 2 : $active = 1;
					}
				} else {
					// Email submission to admin
					$mailsubject = '[' . $_CONF['site_name'] . '] ' . $LANG_DOCUMENTS_1['doc_submission'];
					$mailbody = $LANG_DOCUMENTS_1['doc_submission'] . ' > ' . $_DOCUMENTS_CONF['site_url'] . '/' . $cat['cat_url'] . '/' . DOC_URL;
					COM_mail($_CONF['site_mail'], $mailsubject, $mailbody);
				}
				
				//Get default permissions
				if ($_REQUEST['perm_owner'] == '') {
					SEC_setDefaultPermissions($_REQUEST, $_DOCUMENTS_CONF['default_permissions']);
				}
					
				$sql = "active='$active', "
					. "doc_url='" . DOC_URL . "', "
					. "created =  NOW(), "
					. "modified =  NOW(), "
					. "owner_id = '{$_USER['uid']}', "
					. "group_id = '{$group_id}', "
					. "perm_owner = '{$_REQUEST['perm_owner']}', "
					. "perm_group = '{$_REQUEST['perm_group']}', "
					. "perm_members = '{$_REQUEST['perm_members']}', "
					. "perm_anon = '{$_REQUEST['perm_anon']}'
					";
				$sql = "INSERT INTO {$_TABLES['documents_docs']} SET $sql ";
				DB_query($sql);
				
				//TODO if submission include marker update marker name  
			
			} else {
			    //Edition
				$active = $_REQUEST['active'];
				if (!SEC_hasRights('documents.admin') ) {
				    if (!in_array($active,array(1,2))) $active = 1;
				}
				
				// Convert array values to numeric permission values
				if (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {
					list($perm_owner,$perm_group,$perm_members,$perm_anon) 
					= SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);
					$sql = "modified =  NOW(), "
				             . "active = '{$active}', "
						     . "owner_id = '{$_REQUEST['owner_id']}', "
							 . "group_id = '{$_REQUEST['group_id']}', "
							 . "perm_owner = '{$perm_owner}', "
							 . "perm_group = '{$perm_group}', "
							 . "perm_members = '{$perm_members}', "
							 . "perm_anon = '{$perm_anon}'
					       ";
				} else {					
					if ($_REQUEST['perm_owner'] == '') {
						//Member edit a document. Do not change ownership and rights
						$sql = "modified =  NOW(), "
							   . "active = '{$active}'
							   ";
					} else {
						//Admin edit a document
						$sql = "modified =  NOW(), "
							   . "active = '{$active}', "
								 . "owner_id = '{$_REQUEST['owner_id']}', "
								 . "group_id = '{$_REQUEST['group_id']}', "
								 . "perm_owner = '{$_REQUEST['perm_owner']}', "
								 . "perm_group = '{$_REQUEST['perm_group']}', "
								 . "perm_members = '{$_REQUEST['perm_members']}', "
								 . "perm_anon = '{$_REQUEST['perm_anon']}'
							   ";
				    }
			    }
				$sql = "UPDATE {$_TABLES['documents_docs']} SET $sql "
				 . "WHERE doc_url='{$_REQUEST['doc_url']}' ";
				DB_query($sql);		
			}
			
			
			// Save complete, return to field list
			if ($active == 3) {
    			$msg = $LANG_DOCUMENTS_1['submission_recorded'];
				echo COM_refresh($_DOCUMENTS_CONF['site_url'] . "/index.php?msg=" . urlencode($msg) );
				exit();
			} else {
			    if($creation == 1) {
				    echo COM_refresh($_DOCUMENTS_CONF['site_url'] . '/' . $cat['cat_url'] . '/' . DOC_URL);
					exit();
			    } else {
			       echo COM_refresh($_DOCUMENTS_CONF['site_url'] . '/' . $cat['cat_url'] . '/' . $_REQUEST['doc_url']);
			       exit();
			    }
			}
			
		
		} else {
		   echo COM_refresh($_CONF['site_url'] . '/404.php');
		   exit();
		}
		
		break;

	default :
		require_once ($_CONF['path']  . 'plugins/documents/include_lists.php');
		if ($_DOCUMENTS_CONF['documents_main_header'] != '') $content .= '<div>' . PLG_replaceTags($_DOCUMENTS_CONF['documents_main_header']) . '</div>';
	    $content .= DOCUMENTS_listCategories(0);
		if ($_DOCUMENTS_CONF['documents_main_footer'] != '') $content .= '<div>' . PLG_replaceTags($_DOCUMENTS_CONF['documents_main_footer']) . '</div>';
}

if (defined("DOCUMENT_TITLE")) {
    $page_title = DOCUMENT_TITLE . ' - ' . CAT_NAME;
} else if (defined("CAT_NAME")) {
    $page_title = CAT_NAME;
} else {
     $page_title = $LANG_DOCUMENTS_1['plugin_name'];
}

$display .= COM_siteHeader('menu',  $page_title);
$display .= DOCUMENTS_user_menu();

// If any message
$display .= DOCUMENTS_message($_REQUEST['msg']);

$display .= $content;
$display .= COM_siteFooter();

COM_output($display);

?>
