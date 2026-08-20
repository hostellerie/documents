<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.0                                                      |
// +---------------------------------------------------------------------------+
// | english.php                                                               |
// |                                                                           |
// | English language file                                                     |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012 by the following authors:                              |
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
* Import Geeklog plugin messages for reuse
*
* @global array $LANG32
*/
global $LANG32;

// +---------------------------------------------------------------------------+
// | Array Format:                                                             |
// | $LANGXX[YY]:  $LANG - variable name                                       |
// |               XX    - specific array name                                 |
// |               YY    - phrase id or number                                 |
// +---------------------------------------------------------------------------+

$LANG_DOCUMENTS_1 = array(
    'plugin_name'     => 'Documents',
    'categories'      => 'Categories',
	'documents'       => 'Documents',
	'category'        => 'Category',
	'new_cat'         => 'Create a new category',
	'edit_cat'        => 'Edit a category',
	'cat_name'        => 'Name of the category',
	'cat_url'         => 'Name for URL (no space)', 
	'cat_url_exists'  => 'The name for URL already exists. Please change it.',
	'css'             => 'CSS',
	'template'        => 'Template',
    'cat_order'	      => 'Order',
	'existing_cat'    => 'Existing categories (order, name and name for url)',
	'none'            => 'None',
	'cat_help'        => 'Help for submission form (can be a autotag)',
	'list_index'      => 'Display this category in  the list',
	'submitable'      => 'User can submit documents for this category',
	'custom_header'   => 'Custom header',
	'custom_footer'   => 'Custom footer',
	'required_field'  => 'Indicates required field',
	'admin'           => 'Admin',
	'list_categories' => 'Categories',
	'edit'            => 'Edit',
	'save_button'     => 'Save',
	'delete_button'   => 'Delete',
	'error'           => 'Error',
	'missing_field'   => 'At least one field is missing. Please check:',
	'save_fail'       => 'Save failed',
	'save_success'    => 'Save success',
	'delete_fail'     => 'Delete failed',
	'delete_success'  => 'Delete success',
	'message'         => 'Message',
	'validate_button' => 'OK',
	'fields'          => 'Fields',
	'new_field'       => 'Create a new field',
	'list_fields'     => 'List of the fields',
	'field_name'      => 'Name of the field',
	'field_order'     => 'Field order',
	'var_name'        => 'Name of the variable',
	'field_help'      => 'Help for submission form',
	'type'            => 'Type',
	'sel_group'       => 'Select group',
	'create_new_doc'  => 'Create a new document',
	'field_require'   => 'Point out this is a required field on submission and edit forms',
	'field_on_list'   => 'This field must be a column on the list of documents',
	'edit_doc'        => 'Edit a document',
	'selects'         => 'Selects',
	'list_groups'     => 'List of groups',
	'new_group'       => 'Create a new group',
	'list_selects'    => 'List of selects',
	'new_select'      => 'Create a new select',
	'new_option'      => 'Create a new option',
	'group'           => 'Group',
	'group_name'      => 'Group name',
	'group_help'      => 'Group help',
	'edit_group'      => 'Edit group',
	'select_name'     => 'Name (or option value)',
	'select_value'    => 'Value to display for users',
	'existing_select' => 'Existings selects',
	'select_order'    => 'Select order',
	'doc_submission'  => 'Document submission',
	'submission'      => 'Submission',
	'not_active'      => 'Not active',
	'draft'           => 'Draft',
	'active_label'    => 'Document is active',
	'active'          => 'Active',
	'submission_recorded' => 'Your document has been saved. It will be evaluated before being posted online. Thank you.', 
	'submissions_list'    => 'List of submissions',
	'submissions_list_2'  => 'List of documents under study',
	'drafts_list'         => 'List of documents in draft mode',
	'documents_list'      => 'List of documents',
	'reserved_to'         => 'To access this document, you must belong to group:',
	'cat_hidden'          => 'Category hidden',
	'see_all_docs'        => 'All documents',
	'use_map'             => 'Use map',
	'use_map_details'     => 'If you need to geolocalise each document, set here the map where you want to add markers',
	'nonactive'           => 'Non-active',
	'limited_access'      => 'Limited access',
	'private'             => 'Private',
	'doc_by'              => 'This document by',
	'displayed'           => 'was displayed',
	'times'               => 'times.',
	'select_album'        => 'Select album:',
	'no_map'              => '-- None --',
	'read_more_marker'    => 'Display document',
	'document_draft'      => 'This document is in draft mode. Access is reserved to its owner.',
	'document_submit'     => 'This document has not been approved and is therefore not available for the moment.',
	'new_comment'         => 'New comment on',
	
);

// Messages for the plugin upgrade
$PLG_documents_MESSAGE3002 = $LANG32[9]; // "requires a newer version of Geeklog"

// Localization of the Admin Configuration UI
$LANG_configsections['documents'] = array(
    'label' => 'Documents',
    'title' => 'Documents Configuration'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Document folder',
    'documents_main_header' => 'Documents main header',
    'documents_main_footer' => 'Documents main footer',
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'Main Settings'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Documents Main Settings'
);

$LANG_fs['documents'] = array(
    'fs_main' => 'Documents Main Settings',
	'fs_permissions'     => 'Default Permissions',
);

$LANG_configselects['documents'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('True' => true, 'False' => false),
	12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3),
);
?>
