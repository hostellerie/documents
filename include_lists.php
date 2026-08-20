<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                      |
// +---------------------------------------------------------------------------+
// | include_lists.php                                                         |
// |                                                                           |
// | Plugin administration page                                                |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                              |
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

$_SCRIPTS->setCSSFile('document_css', '/admin/plugins/documents/documents.css');

//TODO custom list design (category, documents)

function DOCUMENTS_listCategories($admin=0)
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;

    require_once $_CONF['path_system'] . 'lib-admin.php';

    $retval = '';

    if (SEC_hasRights('documents.admin')) {
	    $header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $LANG_DOCUMENTS_1['list_categories'], 'field' => 'cat_name', 'sort' => false),
			//array('text' => $LANG_DOCUMENTS_1['edit'], 'field' => 'edit', 'sort' => false)
		);

		$sql = "SELECT
	            *
            FROM {$_TABLES['documents_cat']}
			WHERE 1=1 ";
	} else {
		$header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $LANG_DOCUMENTS_1['list_categories'], 'field' => 'cat_name', 'sort' => false)
		);
		$sql = "SELECT
	            *
            FROM {$_TABLES['documents_cat']}
			WHERE list_index=1 ";
	}
    $defsort_arr = array('field' => 'cat_order', 'direction' => 'asc');

    $text_arr = array(
        'has_extras' => false,
        'form_url' => $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=cat'
    );

    $query_arr = array(
        'sql'            => $sql,
        'default_filter' => COM_getPermSQL ('AND', 0, 3)
    );
	
	$retval .= ADMIN_list('documents_cat', 'plugin_getListField_documents_categories',
                          $header_arr, $text_arr, $query_arr, $defsort_arr) . '<p>&nbsp;</p>';

    if (SEC_hasRights('documents.admin')) {

	    $retval .= '<p style="margin:0px 0px 45px 15px;"><a class="document_button_link" href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_cat">' . $LANG_DOCUMENTS_1['new_cat'] . '</a></p>';

	}
	
	return $retval;
}

/**
*   Get an individual field for the documents screen.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function plugin_getListField_documents_categories($fieldname, $fieldvalue, $A, $icon_arr)
{

    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $_TABLES, $_USER;

    $retval = '';
    $doc_titles = '';
    $edit = '';
	
	switch($fieldname) {

        case 'cat_name':
		    
			$left_join = "LEFT JOIN
                        {$_TABLES['users']} AS u 
                    ON
                        doc.owner_id = u.uid
					LEFT JOIN
					    {$_TABLES['documents_values']} AS doc_val
					  ON doc.doc_url = doc_val.doc_url
					LEFT JOIN {$_TABLES['documents_fields']} AS doc_field
					  ON doc_field.fid = doc_val.field_id
					LEFT JOIN {$_TABLES['documents_cat']} AS doc_cat
					  ON doc_field.cat_id = doc_cat.cid
					LEFT JOIN
					    {$_TABLES['documents_values']} AS img_val
					  ON doc_val.doc_url = img_val.doc_url AND img_val.field_id = (SELECT DISTINCT fid FROM {$_TABLES['documents_fields']} WHERE cat_id=doc_cat.cid AND f_type='image' ORDER BY f_order LIMIT 1)
                    ";
			/*		
			$left_join = "LEFT JOIN
                        {$_TABLES['users']} AS u 
                    ON
                        doc.owner_id = u.uid
					LEFT JOIN
					    {$_TABLES['documents_values']} AS doc_val
					  ON doc.doc_url = doc_val.doc_url
					LEFT JOIN {$_TABLES['documents_fields']} AS doc_field
					  ON doc_field.fid = doc_val.field_id
					LEFT JOIN {$_TABLES['documents_cat']} AS doc_cat
					  ON doc_field.cat_id = doc_cat.cid
					LEFT JOIN
					    {$_TABLES['documents_values']} AS img_val
					  ON doc_val.doc_url = img_val.doc_url AND img_val.field_id = (SELECT fid FROM {$_TABLES['documents_fields']} WHERE cat_id=doc_cat.cid AND f_type='image' ORDER BY f_order LIMIT 1)
                    ";
			*/
			
		    $sql = "SELECT DISTINCT doc.doc_url, doc.owner_id, doc_val.v_value as name, doc_cat.cat_name, doc_cat.cat_url, doc.modified, img_val.v_value, u.username, u.photo, u.email FROM {$_TABLES['documents_docs']} AS doc {$left_join} WHERE doc_cat.cid='{$A['cid']}' AND doc_field.f_order=10 AND doc.active=1"  . COM_getPermSQL('AND', $_USER['uid'], 2, 'doc') . " ORDER BY doc.modified DESC";
			
			$result = DB_query ($sql);
            $nb_result = DB_numRows($result);
			$doc_images = '';
			$it = 1;
			$images = 0;
			
			while ($B = DB_fetchArray($result, false)) {
			    if ($it >= 6) break;
				$B['name'] = stripslashes($B['name']);
				$display_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=view&cat=' . rawurlencode($A['cat_url']) . '&doc=' . rawurlencode($B['doc_url']);
				(strlen($B['name'])>=30) ? $title = substr($B['name'],0,30).'...' : $title = $B['name'];
				
				if ($B['v_value'] != '') {
				    
					$doc_images .= '<div class="document_light" style="float:left; width:75px; min-height: 150px; margin:0px 10px;">' . COM_createLink('<img class="document_img" src="' 
					. $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode(basename($B['v_value'])) 
					. '&amp;w=75&amp;h=75" vspace="5" hspace="0" width="75" height="75"
					border="0" align="none" alt="' . $B['name'] . '" title="' . $B['name'] . '" />', $display_url) . '<br'.XHTML.'>' . $title. '</div>';
					$images++;
			    } else {
                    $doc_images .= '<div class="document_light" style="float:left; width:75px; min-height: 150px; margin:0px 10px;">' . COM_createLink('<img class="document_img" src="' 
					. $_CONF['site_url'] . '/admin/plugins/documents/images/1px.jpg" vspace="5" hspace="0" width="75" height="75" border="0" align="none" alt="' 
					. $B['name'] . '" title="' . $B['name'] . '" />', $display_url) . '<br'.XHTML.'>' . $title . '</div>';
				}
				
				$doc_titles .= '<div style="background:#FFFFFF; margin:5px 5px; padding:5px; float:left; border: 1px solid #EEE;"><div class="document_light" style="width:70px; min-height: 150px; background:#EEE; padding :5px; overflow:hidden;">' . COM_createLink($title, $display_url) . '</div></div>';
				
				$it++;
			}
			
			while ($it < 6) {
			    $doc_images .= '<div style="float:left; width:75px; min-height: 150px; margin:0px 10px;"><img class="document_img" src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/1px.jpg" vspace="5" hspace="0" 
			width="75" height="75" border="0" align="none" alt="" title="" /></div>';
			    $doc_titles .= '<div style="float:left; width:75px; margin:0px 10px;"><img class="document_img" src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/1px.jpg" vspace="5" hspace="0" 
			width="75" height="75" border="0" align="none" alt="" title="" /></div>';
				$it++;
			}
			
			$cat_hidden = '';
			if($A['list_index'] == 0) $cat_hidden = ' <span style="color:red;">[' .  $LANG_DOCUMENTS_1['cat_hidden'] . ']</span>';
			
			if (SEC_hasRights('documents.admin')) {
			    $edit_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_cat&cat=' . $A['cid'];
                $edit = ' ' . COM_createLink($icon_arr['edit'], $edit_url);
			}
			
            $retval = '<h2 style="font-size:large;margin-top:10px; margin-left:8px;"><a href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=view&cat=' . rawurlencode($A['cat_url']) . '">' . stripslashes($fieldvalue) . '</a>' . $cat_hidden . $edit . '</h2>';
			
			
			//images if any
			if ($images > 0) {
    			$retval .= '<p>' . $doc_images . '<div style="clear:both;"></div>';
			} else {
			    $retval .= '<p>' . $doc_titles . '<div style="clear:both;"></div>';
			}
			$retval .= '</p><p class="document_read_more"><small><a href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=view&cat=' . rawurlencode($A['cat_url']) . '">' . $LANG_DOCUMENTS_1['see_all_docs'] . ' (' . $nb_result . ')</a></small></p>';
            break;
			
		case 'edit':
		    $edit_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_cat&cat=' . $A['cid'];
            $retval = COM_createLink($icon_arr['edit'], $edit_url);
            break;
			
		default;
		    break;
    }
    return $retval;
}

function DOCUMENTS_listFields ($admin=0)
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;

    require_once $_CONF['path_system'] . 'lib-admin.php';

    $retval = '';

    if (SEC_hasRights('documents.admin')) {
	    $header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $LANG_DOCUMENTS_1['list_fields'], 'field' => 'f_name', 'sort' => true),
			array('text' => $LANG_DOCUMENTS_1['list_categories'], 'field' => 'cat_name', 'sort' => true),
			array('text' => $LANG_DOCUMENTS_1['edit'], 'field' => 'edit', 'sort' => false)
		);
	    $retval .= '<p style="margin:20px 0px 25px 15px;"><a class="document_button_link" href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_field">' . $LANG_DOCUMENTS_1['new_field'] . '</a></p>';
	} else {
		$header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $LANG_DOCUMENTS_1['list_fields'], 'field' => 'f_name', 'sort' => true),
			array('text' => $LANG_DOCUMENTS_1['list_cat'], 'field' => 'cat_name', 'sort' => true),
		);
	}
	
    $defsort_arr = array('field' => 'cat_name,f_order', 'direction' => 'asc');

    $text_arr = array(
        'has_extras' => true,
        'form_url' => $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=list_fields'
    );
	
	$sql = "SELECT
	            f.f_name,f.fid, f.var_name,f.f_order,c.cat_name,c.cat_url
            FROM {$_TABLES['documents_fields']} AS f
			LEFT JOIN {$_TABLES['documents_cat']} AS c
			  ON f.cat_id = c.cid
			WHERE 1=1";

    $query_arr = array(
        'sql'            => $sql,
        'default_filter' => COM_getPermSQL ('AND', 0, 3, 'c'),
		'query_fields'   => array('f.f_name','f.fid','c.cat_name','c.cat_url'),
    );

    $retval .= ADMIN_list('documents_fields', 'plugin_getListField_documents_fields',
                          $header_arr, $text_arr, $query_arr, $defsort_arr);

    return $retval;
}

/**
*   Get an individual field for the documents screen.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function plugin_getListField_documents_fields($fieldname, $fieldvalue, $A, $icon_arr)
{

    global $_DOCUMENTS_CONF;
	
	switch($fieldname) {
			
		case 'edit':
		    $edit_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_field&field=' . $A['fid'];
            $retval = COM_createLink($icon_arr['edit'], $edit_url);
            break;
		case 'f_name' :
		    $retval = ($A['f_order']/10) . '. ' .stripslashes($fieldvalue) . '&nbsp;&nbsp;<span style="color:red;">&#123;' .  $A['var_name'] . '&#125;</span>';
			break;
			
		default;
		     $retval = stripslashes($fieldvalue);
			break;
    }
    return $retval;
}

/* List documents from a category */

function DOCUMENTS_listDocs($cat='')
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1, $_SCRIPTS;

    require_once $_CONF['path_system'] . 'lib-admin.php';

    $retval = '';
    $morefields = '';
    $leftjoin = '';

	if ($cat == '') return $retval;
	$cat = addslashes((string) $cat);
	
	$css = '/admin/plugins/documents/document.css';
    $_SCRIPTS->setCSSFile('documents_css', $css, true);
	
	if (!defined('CAT_URL')) {
		define('CAT_URL', $cat);
	}
    
	// get cat infos from cat url
	$sql = "SELECT
	            *
            FROM {$_TABLES['documents_cat']}
			WHERE cat_url='$cat'
			";
			
	$category = DB_fetchArray(DB_query($sql));
	if (!is_array($category) || empty($category['cid'])) {
		return $retval;
	}

	//is cat submitable
	$submitable = $category['submitable'];
	$catname = $category['cat_name'];
	if ($catname == '')  return $retval;
	if (!defined("CAT_NAME")) {
		define("CAT_NAME",$catname);
	}
	
	// get fields name for columns
	$cid = $category['cid'];
		
	if ($cid == '')  return $retval;
		
	// get all fields for this category
	$sql = "SELECT
	            f.f_name, f.fid, f.f_type
            FROM {$_TABLES['documents_fields']} AS f

			WHERE cat_id='$cid' AND f_on_list=1 ORDER BY f_order
			";
			
	$res = DB_query($sql);
	$nrows = DB_numRows($res);
	$header_arr_more = array();

    for ($i = 0; $i < $nrows; $i++) {
	    $A = DB_fetchArray($res);
	    
		// Todo make numeric and date field sortable

		switch ($A['f_type']) {
			
			case 'decimal' :
				 
				$morefields .= ', CAST(m' . $A['fid'] . '.v_value AS DECIMAL(12,2)) AS f' . $A['fid'] . ' ';
				$leftjoin .= " LEFT JOIN (
								  SELECT val.v_value, val.doc_url
								      FROM {$_TABLES['documents_values']} AS val
								  WHERE field_id = {$A['fid']}
							   ) AS m{$A['fid']} ON d.doc_url=m{$A['fid']}.doc_url
							    ";
				$header_arr_more[] = array('text' => $A['f_name'], 'field' => 'f'.$A['fid'], 'sort' => true);
				break;
			
			case 'date' :
				
				$morefields .= ', DATE_FORMAT(CAST(m' . $A['fid'] . '.v_value AS DATE),\'' . $_DOCUMENTS_CONF['db_date'] . '\') AS f' . $A['fid'] . ' ';
				$leftjoin .= " LEFT JOIN (
								  SELECT val.v_value, val.doc_url
								      FROM {$_TABLES['documents_values']} AS val
								  WHERE field_id = {$A['fid']}
							   ) AS m{$A['fid']} ON d.doc_url=m{$A['fid']}.doc_url
							    ";
				$header_arr_more[] = array('text' => $A['f_name'], 'field' => 'f'.$A['fid'], 'sort' => false);
				break;
				
			case 'select' :
			    $morefields .= ', m' . $A['fid'] . '.s_value AS f' . $A['fid'] . ' ';
				$leftjoin .= " LEFT JOIN (
								  SELECT sel.s_value, val.doc_url
								      FROM {$_TABLES['documents_values']} AS val
								  LEFT JOIN {$_TABLES['documents_selects']} AS sel
			                          ON sel.s_name = val.v_value
								  WHERE field_id = {$A['fid']}
							   ) AS m{$A['fid']} ON d.doc_url=m{$A['fid']}.doc_url
							    ";
				$header_arr_more[] = array('text' => $A['f_name'], 'field' => 'f'.$A['fid'], 'sort' => true);
				break;
			
			case 'checkbox' :
			    $unchecked = '<img src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/disabled.png" align="top" alt="" /> ';
				$checked = '<img src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/enabled.png" align="top" alt="" /> ';
				$morefields .= ', CONCAT(IF(m' . $A['fid'] . '.v_value=1,\'' . $checked . '\',\'' . $unchecked . '\') ) AS f' . $A['fid'] . ' ';
				$leftjoin .= " LEFT JOIN (
								  SELECT val.v_value, val.doc_url
								      FROM {$_TABLES['documents_values']} AS val
								  WHERE field_id = {$A['fid']}
							   ) AS m{$A['fid']} ON d.doc_url=m{$A['fid']}.doc_url
							    ";
				$header_arr_more[] = array('text' => $A['f_name'], 'field' => 'f'.$A['fid'], 'sort' => true);
				break;
				
			default :
			    
				$morefields .= ', m' . $A['fid'] . '.v_value AS f' . $A['fid'] . ' ';
				$leftjoin .= " LEFT JOIN (
								  SELECT val.v_value, val.doc_url
								      FROM {$_TABLES['documents_values']} AS val
								  WHERE field_id = {$A['fid']}
							   ) AS m{$A['fid']} ON d.doc_url=m{$A['fid']}.doc_url
							   
							    ";
		        $header_arr_more[] = array('text' => $A['f_name'], 'field' => 'f'.$A['fid'], 'sort' => true);
				break;
		}
	}
	
	if ( SEC_hasRights('documents.publish') ) {
		$header_arr_admin = array(      // display 'text' and use table field 'field'
			//array('text' => $LANG_DOCUMENTS_1['edit'], 'field' => 'edit', 'sort' => false)
		) ;
        $active = ' AND (d.active=1 OR d.active=0)';
	} else {
		$header_arr_admin = array();
		$active = ' AND (d.active=1) ';
	}
	
	$header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $catname, 'field' => 'v_value', 'sort' => true)
		);
		
	$header_arr_total = array_merge ( $header_arr,  $header_arr_more, $header_arr_admin);
	
    $defsort_arr = array('field' => 'v_value', 'direction' => 'asc');

    $text_arr = array(
        'has_extras' => true,
        'form_url' => $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=view&cat=' . $cat
    );
	
	$text_arr_submission = array(
        'has_extras' => false,
        'form_url' => $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=view&cat=' . $cat
    );

	$catid = $cid;
	
	$sql = "SELECT DISTINCT
	            f.fid, f.f_type, v.field_id, v.v_value, v.doc_url, f.cat_id, c.cat_url, marker_val.v_value AS marker, img_val.v_value AS image, d.* $morefields
            FROM {$_TABLES['documents_values']} 
			  AS v
			LEFT JOIN {$_TABLES['documents_fields']} AS f
			  ON f.fid = v.field_id
			LEFT JOIN {$_TABLES['documents_cat']} AS c
			  ON f.cat_id = c.cid
			LEFT JOIN {$_TABLES['documents_docs']} AS d
			  ON d.doc_url = v.doc_url
			LEFT JOIN
				{$_TABLES['documents_values']} AS img_val
			  ON d.doc_url = img_val.doc_url AND img_val.field_id = (SELECT fid FROM {$_TABLES['documents_fields']} WHERE cat_id=c.cid AND f_type='image' ORDER BY f_order LIMIT 1)
			LEFT JOIN
				{$_TABLES['documents_values']} AS marker_val
			  ON d.doc_url = marker_val.doc_url AND marker_val.field_id = (SELECT fid FROM {$_TABLES['documents_fields']} WHERE cat_id=c.cid AND f_type='marker' ORDER BY f_order LIMIT 1)
			$leftjoin 
			WHERE f.cat_id= '$catid' AND f.f_order=10 $active";

    $query_arr = array(
		'sql'            => $sql,
        'default_filter' => COM_getPermSQL ('AND', 0, 0, 'd'),
		'query_fields'   => array('v.v_value'),
    );
	
	//if (SEC_hasRights('documents.admin') == 1 ) {
		$sql_submissions = "SELECT
					f.fid,f.f_type,v.field_id,v.v_value,v.doc_url,f.cat_id, c.cat_url, d.*
				FROM {$_TABLES['documents_values']} 
				  AS v
				LEFT JOIN {$_TABLES['documents_fields']} AS f
				  ON f.fid = v.field_id
				LEFT JOIN {$_TABLES['documents_cat']} AS c
				  ON f.cat_id = c.cid
				LEFT JOIN {$_TABLES['documents_docs']} AS d
				  ON d.doc_url = v.doc_url
				WHERE f.cat_id= '$catid' AND f.f_order=10  AND (d.active=3)";
		
		$submissions = DB_numRows(DB_query($sql_submissions));
    //}
	
	$sql_drafts = "SELECT
			f.fid,f.f_type,v.field_id,v.v_value,v.doc_url,f.cat_id, c.cat_url, d.*
		FROM {$_TABLES['documents_values']} 
		  AS v
		LEFT JOIN {$_TABLES['documents_fields']} AS f
		  ON f.fid = v.field_id
		LEFT JOIN {$_TABLES['documents_cat']} AS c
		  ON f.cat_id = c.cid
		LEFT JOIN {$_TABLES['documents_docs']} AS d
		  ON d.doc_url = v.doc_url
		WHERE f.cat_id= '$catid' AND f.f_order=10  AND (d.active=2)";
		
	$drafts = DB_numRows(DB_query($sql_drafts));
	
	$query_arr_submissions = array(
		'sql'            => $sql_submissions,
        'default_filter' => COM_getPermSQL ('AND', 0, 3, 'd'),
		'query_fields'   => array('v.v_value'),
    );
	
	$query_arr_drafts = array(
		'sql'            => $sql_drafts,
        'default_filter' => COM_getPermSQL ('AND', 0, 3, 'd'),
		'query_fields'   => array('v.v_value'),
    );
	
	// header
	$retval .= PLG_replaceTags($category['custom_header']);
	
	//Display map if category use map
	if (DOCUMENTS_hasMaps() && $category['map'] != '' && $category['map'] > 0) {
		$retval .= PLG_replaceTags("[maps:{$category['map']}]");
	}
	
	if (SEC_hasRights('documents.admin') == 1 && $submissions > 0) {	
		if (DB_count($_TABLES['documents_docs'],'active',3) > 0) {
		$retval .= '<h2>' . $LANG_DOCUMENTS_1['submissions_list']. '</h2>' . ADMIN_list('documents_list', 'plugin_getListField_documents_docs',
							  $header_arr_total, $text_arr_submission, $query_arr_submissions, $defsort_arr);
	    }
	}
	
	$retval .= '<h2 style="padding-bottom:40px;">' . $LANG_DOCUMENTS_1['documents_list']. '</h2>' . ADMIN_list('documents_list', 'plugin_getListField_documents_docs',
                          $header_arr_total, $text_arr, $query_arr, $defsort_arr);
						  
	//List of document submission for non admin
	if (SEC_hasRights('documents.admin') != 1 && $submissions > 0) {	
		if (DB_count($_TABLES['documents_docs'],'active',3) > 0) {
		$retval .= '<h2>' . $LANG_DOCUMENTS_1['submissions_list_2']. '</h2>' . ADMIN_list('documents_list', 'plugin_getListField_documents_docs',
							  $header_arr_total, $text_arr_submission, $query_arr_submissions, $defsort_arr);
	    }
	}
	
	//List of draft documents for non admin		
	if ($drafts > 0) {
	$retval .= '<h2>' . $LANG_DOCUMENTS_1['drafts_list']. '</h2>' . ADMIN_list('documents_list', 'plugin_getListField_documents_docs',
						  $header_arr_total, $text_arr_submission, $query_arr_drafts, $defsort_arr);
	}
	
	if (SEC_hasRights('documents.admin') == 1 || $submitable  == 1) {
	    $cat_name = DB_getItem($_TABLES['documents_cat'],'cat_name',"cat_url='$cat'");
	    $retval .= '<p style="margin:20px 0px 25px 15px;"><a class="document_button_link" href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=new&cat=' . $cat . '">' . $LANG_DOCUMENTS_1['create_new_doc'] . ' "' . $cat_name . '"</a></p>';
	}
	
	// footer
	$retval .= '<p>&nbsp;</p>' . PLG_replaceTags($category['custom_footer']);

    return $retval;
}

/**
*   Get an individual field for the documents screen.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function plugin_getListField_documents_docs($fieldname, $fieldvalue, $A, $icon_arr)
{

    global $_DOCUMENTS_CONF, $_CONF, $LANG_DOCUMENTS_1;

    $retval = '';
    $edit = '';
    $inactive = '';

	switch($fieldname) {

        //Edit button
		case 'edit':
		    $access = SEC_hasAccess($A['owner_id'], $A['group_id'],
                                $A['perm_owner'], $A['perm_group'],
                                $A['perm_members'], $A['perm_anon']);
			
			if ( $access == 3 ) {
			    $edit_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit&doc_url=' . $A['doc_url'] . '&cat=' . $A['cat_id'];
                $retval = COM_createLink($icon_arr['edit'], $edit_url);
			} else {
			    return;
			}
            break;
			
		//Doc name and link
		case 'v_value' :
		    $access = SEC_hasAccess($A['owner_id'], $A['group_id'],
                                $A['perm_owner'], $A['perm_group'],
                                $A['perm_members'], $A['perm_anon']);
			
			$display_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=view&cat=' . rawurlencode($A['cat_url']) . '&doc=' . rawurlencode($A['doc_url']);
			
			if (SEC_hasRights('documents.admin')) {
			    $edit_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit&doc_url=' . $A['doc_url'] . '&cat=' . $A['cat_id'];
                $edit = ' ' . COM_createLink($icon_arr['edit'], $edit_url);
			}
			
			if ( $access >= 1) {
				( $A['perm_anon'] == 0 ) ? $inactive = '<small>[' . $LANG_DOCUMENTS_1['private'] . ']</small>' : 1;
				($A['active']==0) ? $inactive = '<small>[' . $LANG_DOCUMENTS_1['nonactive'] .']</small>' : 1;
				($A['active']==2) ? $inactive = '<small>[' . $LANG_DOCUMENTS_1['draft'] .']</small>' : 1;
                $retval = '<h2>' . $inactive . ' ' . COM_createLink(stripslashes($fieldvalue), $display_url) . $edit . '</h2>';
			} else {
				$limited = '<small>[' . $LANG_DOCUMENTS_1['private'] . ']</small>';
				($A['active']==2) ? $limited = '<small>[' . $LANG_DOCUMENTS_1['draft'] .']</small>' : 1;
			    $retval = '<h2>' . $limited . ' ' . COM_createLink(stripslashes($fieldvalue), $display_url) . '</h2>';
			}
			
			
			break;
			
		//Others
		default:

			$access = SEC_hasAccess($A['owner_id'], $A['group_id'],
                                $A['perm_owner'], $A['perm_group'],
                                $A['perm_members'], $A['perm_anon']);
								
			if ( $access >= 1) {
				$imageValue = isset($A['image']) ? $A['image'] : '';
				$markerValue = isset($A['marker']) ? $A['marker'] : '';
				if ($fieldvalue == $imageValue && $fieldvalue != '') {
					//image
					$doc_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=view&cat=' . rawurlencode($A['cat_url']) . '&doc=' . rawurlencode($A['doc_url']);
					$image = '<img class="document_img" src="' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode(basename($fieldvalue)) .
						'&amp;w=200&amp;h=200" align="top" alt="" />';
						
					$retval = COM_createLink($image, $doc_url);

				} else if ($fieldvalue == $markerValue && $fieldvalue != '') {
					if (DOCUMENTS_hasMaps()) {
						$retval = PLG_replaceTags('<div style="width:450px;">[marker:' . $fieldvalue . ' width:400px]</div>');
					} else {
						$retval = '';
					}
				} else {
					$retval = stripslashes($fieldvalue);
				}
			} else {
			    $retval = '*****';
			}
            break;
    }
	
    return $retval;
}

// Todo list selects groups + selects items
function DOCUMENTS_listGroups($admi=0)
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;

    require_once $_CONF['path_system'] . 'lib-admin.php';

    $retval = '';

    if (SEC_hasRights('documents.admin')) {
	    $header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $LANG_DOCUMENTS_1['list_groups'], 'field' => 'g_name', 'sort' => true),
			array('text' => $LANG_DOCUMENTS_1['edit'], 'field' => 'edit', 'sort' => false)
		);
		
		$retval .= '<p style="margin:20px 0px 25px 15px;"><a class="document_button_link" href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_group">' . $LANG_DOCUMENTS_1['new_group'] . '</a></p>';
		
	} else {
		$header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $LANG_DOCUMENTS_1['list_groups'], 'field' => 'g_name', 'sort' => true),
		);
	}
	
    $defsort_arr = array('field' => 'g_name', 'direction' => 'asc');

    $text_arr = array(
        'has_extras' => true,
        'form_url' => $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=list_groups'
    );
	
	$sql = "SELECT
	            g.g_name,g.gid
            FROM {$_TABLES['documents_selects_group']} AS g

			WHERE 1=1";

    $query_arr = array(
        'sql'            => $sql,
        //'default_filter' => COM_getPermSQL ('AND', 0, 3),
		'query_fields'   => array('g.g_name','g.gid'),
    );

    $retval .= ADMIN_list('documents_groups', 'plugin_getListField_groups_fields',
                          $header_arr, $text_arr, $query_arr, $defsort_arr);

    return $retval;
}

/**
*   Get an individual field for the documents screen.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function plugin_getListField_groups_fields($fieldname, $fieldvalue, $A, $icon_arr)
{

    global $_DOCUMENTS_CONF;

    $retval = '';
	
	switch($fieldname) {
			
		case 'edit':
		    $edit_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_group&group=' . $A['gid'];
            $retval = COM_createLink($icon_arr['edit'], $edit_url);
            break;
		case 'g_name' :
		    $list_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=list_selects&group=' . $A['gid'];
			$retval = COM_createLink($fieldvalue, $list_url);

			break;
			
		default;
		     $retval = stripslashes($fieldvalue);
			break;
    }
    return $retval;
}


function DOCUMENTS_listSelects($group)
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;

    $group = (int) $group;

    require_once $_CONF['path_system'] . 'lib-admin.php';

    $retval = '';

    if (SEC_hasRights('documents.admin')) {
	    $header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $LANG_DOCUMENTS_1['list_selects'], 'field' => 's_name', 'sort' => true),
			array('text' => $LANG_DOCUMENTS_1['group'], 'field' => 'g_name', 'sort' => true),
			array('text' => $LANG_DOCUMENTS_1['edit'], 'field' => 'edit', 'sort' => false)
		);
	    $retval .= '<p style="margin:20px 0px 25px 15px;"><a class="document_button_link" href="' . $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_select">' . $LANG_DOCUMENTS_1['new_option'] . '</a></p>';
	} else {
		$header_arr = array(      // display 'text' and use table field 'field'
			array('text' => $LANG_DOCUMENTS_1['list_selects'], 'field' => 's_name', 'sort' => true),
			array('text' => $LANG_DOCUMENTS_1['group'], 'field' => 'g_name', 'sort' => true),
		);
	}
	
    $defsort_arr = array('field' => 's_name', 'direction' => 'asc');

    $text_arr = array(
        'has_extras' => true,
        'form_url' => $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=list_selects&group=' . $group
    );
	
	$sql = "SELECT
	            s.s_name,s.sid, g.g_name,g.gid
            FROM {$_TABLES['documents_selects']} AS s
			LEFT JOIN {$_TABLES['documents_selects_group']} AS g
			  ON g.gid = s.s_group

			WHERE 1=1";
			
				
	if ($group > 0) {
		$sql .= " AND s_group = '{$group}'";
	}

    $query_arr = array(
        'sql'            => $sql,
        //'default_filter' => COM_getPermSQL ('AND', 0, 3),
		'query_fields'   => array('s.s_name','s.sid'),
    );

    $retval .= ADMIN_list('documents_groups', 'plugin_getListField_selects_fields',
                          $header_arr, $text_arr, $query_arr, $defsort_arr);

    return $retval;
}

/**
*   Get an individual field for the documents screen.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function plugin_getListField_selects_fields($fieldname, $fieldvalue, $A, $icon_arr)
{

    global $_DOCUMENTS_CONF;

    $retval = '';
	
	switch($fieldname) {
			
		case 'edit':
		    $edit_url = $_DOCUMENTS_CONF['site_url'] . '/index.php?mode=edit_select&select=' . $A['sid'];
            $retval = COM_createLink($icon_arr['edit'], $edit_url);

			break;
			
		default;
		     $retval = stripslashes($fieldvalue);
			break;
    }
    return $retval;
}

?>
