<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.8                                                    |
// +---------------------------------------------------------------------------+
// | english.php                                                               |
// |                                                                           |
// | English language file                                                     |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
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
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the              |
// | GNU General Public License for more details.                              |
// +---------------------------------------------------------------------------+

/**
 * @package Documents
 */

global $LANG32;

$LANG_DOCUMENTS_1 = array(
    'plugin_name'         => 'Documents',
    'categories'          => 'Categories',
    'documents'           => 'Documents',
    'category'            => 'Category',
    'new_cat'             => 'Create a new category',
    'edit_cat'            => 'Edit a category',
    'cat_name'            => 'Category name',
    'cat_url'             => 'URL name (no spaces)',
    'cat_url_exists'      => 'This URL name already exists. Please choose another one.',
    'css'                 => 'CSS',
    'template'            => 'Template',
    'cat_order'           => 'Order',
    'existing_cat'        => 'Existing categories (order, name and URL name)',
    'none'                => 'None',
    'cat_help'            => 'Help for the submission form (may contain an autotag)',
    'list_index'          => 'Display this category in the list',
    'submitable'          => 'Users can submit documents to this category',
    'custom_header'       => 'Custom header',
    'custom_footer'       => 'Custom footer',
    'required_field'      => 'Indicates a required field',
    'admin'               => 'Admin',
    'list_categories'     => 'Categories',
    'edit'                => 'Edit',
    'save_button'         => 'Save',
    'delete_button'       => 'Delete',
    'error'               => 'Error',
    'missing_field'       => 'At least one field is missing. Please check:',
    'save_fail'           => 'Save failed',
    'save_success'        => 'Saved successfully',
    'delete_fail'         => 'Delete failed',
    'delete_success'      => 'Deleted successfully',
    'message'             => 'Message',
    'validate_button'     => 'OK',
    'fields'              => 'Fields',
    'new_field'           => 'Create a new field',
    'list_fields'         => 'List of fields',
    'field_name'          => 'Field name',
    'field_order'         => 'Field order',
    'existing_field'      => 'Existing fields (order and name)',
    'var_name'            => 'Variable name',
    'field_help'          => 'Help for the submission form',
    'type'                => 'Type',
    'sel_group'           => 'Selection group',
    'create_new_doc'      => 'Create a new document',
    'field_require'       => 'Require this field on submission and edit forms',
    'field_on_list'       => 'Display this field as a column in document lists',
    'edit_doc'            => 'Edit a document',
    'selects'             => 'Selections',
    'list_groups'         => 'List of groups',
    'new_group'           => 'Create a new group',
    'list_selects'        => 'List of selections',
    'new_select'          => 'Create a new selection',
    'new_option'          => 'Create a new option',
    'group'               => 'Group',
    'group_name'          => 'Group name',
    'group_help'          => 'Group help',
    'edit_group'          => 'Edit group',
    'select_name'         => 'Name (or option value)',
    'select_value'        => 'Value displayed to users',
    'existing_select'     => 'Existing selections',
    'select_order'        => 'Selection order',
    'doc_submission'      => 'Document submission',
    'submission'          => 'Submission',
    'not_active'          => 'Not active',
    'draft'               => 'Draft',
    'active_label'        => 'Document is active',
    'active'              => 'Active',
    'submission_recorded' => 'Your document has been saved. It will be reviewed before being published. Thank you.',
    'submissions_list'    => 'List of submissions',
    'submissions_list_2'  => 'List of documents under review',
    'drafts_list'         => 'List of draft documents',
    'documents_list'      => 'List of documents',
    'reserved_to'         => 'To access this document, you must belong to the group:',
    'cat_hidden'          => 'Hidden category',
    'see_all_docs'        => 'All documents',
    'use_map'             => 'Use map',
    'use_map_details'     => 'To geolocate documents, select the map on which markers should be added.',
    'nonactive'           => 'Inactive',
    'limited_access'      => 'Limited access',
    'private'             => 'Private',
    'doc_by'              => 'This document by',
    'displayed'           => 'was displayed',
    'times'               => 'times.',
    'select_album'        => 'Select album:',
    'no_map'              => '-- None --',
    'read_more_marker'    => 'Display document',
    'document_draft'      => 'This document is in draft mode. Access is reserved to its owner.',
    'document_submit'     => 'This document has not yet been approved and is not currently available.',
    'new_comment'         => 'New comment on',
    'integrity_audit_title'              => 'Data integrity audit',
    'integrity_audit_notice'             => 'This report is read-only. No data or files are modified.',
    'integrity_check'                    => 'Check',
    'integrity_result'                   => 'Result',
    'integrity_duplicate_category_slugs' => 'Duplicate category slugs',
    'integrity_duplicate_document_slugs' => 'Duplicate document slugs',
    'integrity_documents_without_values' => 'Documents without values',
    'integrity_values_without_document'  => 'Values without document',
    'integrity_values_without_field'     => 'Values without field',
    'integrity_fields_without_category'  => 'Fields without category',
    'integrity_missing_images'           => 'Referenced image files missing on disk',
    'integrity_unreferenced_images'      => 'Image files not referenced by Documents',
    'integrity_back_admin'               => 'Back to Documents administration'
);

// Messages for the plugin upgrade.
$PLG_documents_MESSAGE3002 = $LANG32[9];

// Localization of the Admin Configuration UI.
$LANG_configsections['documents'] = array(
    'label' => 'Documents',
    'title' => 'Documents Configuration'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Documents folder',
    'documents_main_header' => 'Documents main header',
    'documents_main_footer' => 'Documents main footer',
    'max_image_width'       => 'Maximum image width (pixels)',
    'max_image_height'      => 'Maximum image height (pixels)',
    'max_image_size'        => 'Maximum image file size (bytes)',
    'default_permissions'   => 'Default permissions'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'Main Settings'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Documents Main Settings'
);

$LANG_fs['documents'] = array(
    'fs_main'        => 'Documents Main Settings',
    'fs_images'      => 'Images',
    'fs_permissions' => 'Default Permissions'
);

$LANG_configselects['documents'] = array(
    0  => array('True' => 1, 'False' => 0),
    1  => array('True' => true, 'False' => false),
    12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3)
);
