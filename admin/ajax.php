<?php
// +--------------------------------------------------------------------------+
// | Documents Plugin 1.0 - geeklog CMS                                       |
// +--------------------------------------------------------------------------+
// | ajax.php                                                                 |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2012 by the following authors:                             |
// |                                                                          |
// | Authors: ::Ben - ben AT geeklog DOT fr                                   |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+


require_once '../../../lib-common.php';

// Incoming variable filter
$vars = array('cat_id'        => 'number',
              's_group'       => 'number',
              'action'        => 'text',
			  );
			  
DOCUMENTS_filterVars($vars, $_REQUEST);

$_DOCUMENTS_CONF['ajax'] = true;

$anon_actions = array();

if(COM_isAnonUser()) {
    if ( !in_array($_REQUEST['action'], $anon_actions) ) {
		exit();
	}
}


switch ($_REQUEST['action']) {
	case 'change_field_cat' :
	
	    $cat_id =$_REQUEST['cat_id'];
		$max  = DB_getItem($_TABLES['documents_fields'],'MAX(f_order)',"cat_id={$cat_id}") + 10;
		
		$res = DB_query("SELECT f_order, f_name 
						 FROM {$_TABLES['documents_fields']}
						 WHERE cat_id = {$cat_id} 
						 ORDER by f_order
						 ");

		while ($A = DB_fetchArray($res)) {
			$fields_order .=  $A['f_order'] .  '. ' . $A['f_name'] . '<br' . XHTML . '>';
		}
		if ($fields_order == '') $fields_order = '--';
		
		echo json_encode(array("a" => $max, "b" => $fields_order));
		
		break;
	
	case 'change_select_group' :
	
	    $s_group = $_REQUEST['s_group'];
		$max  = DB_getItem($_TABLES['documents_selects'],'MAX(s_order)',"s_group={$s_group}") + 10;
		
		$res = DB_query("SELECT s_order, s_name 
						 FROM {$_TABLES['documents_selects']}
						 WHERE s_group = {$s_group} 
						 ORDER by s_order
						 ");

		while ($A = DB_fetchArray($res)) {
			$select_order .=  $A['s_order'] .  '. ' . $A['s_name'] . '<br' . XHTML . '>';
		}
		
		if ($select_order == '') $select_order = '--';
		echo json_encode(array("a" => $max, "b" => $select_order));
		
		break;
	
	default :
	
	    echo $LANG_SPHERE_1['loading'];
		break;

}

?>