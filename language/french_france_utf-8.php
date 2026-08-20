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
    'categories'      => 'Catégories',
	'documents'       => 'Documents',
	'category'        => 'Categorie',
	'new_cat'         => 'Créer une nouvelle catégorie',
	'edit_cat'        => 'Editer une catégorie',
	'cat_name'        => 'Nom de la catégorie',
	'cat_url'         => 'Nom pour l\'URL (sans d\'espace)', 
	'cat_url_exists'  => 'Le nom pour l\'URL existe déjà. Merci de le changer.',
	'css'             => 'CSS',
	'template'        => 'Template',
    'cat_order'	      => 'Ordre',
	'existing_cat'    => 'Catégories existantes (ordre, nom et url)',
	'none'            => 'Aucune',
	'cat_help'        => 'Help for submission form (can be a autotag)',
	'list_index'      => 'Display this category in  the list',
	'submitable'      => 'User can submit documents for this category',
	'custom_header'   => 'Custom header',
	'custom_footer'   => 'Custom footer',
	'required_field'  => 'Indique un champ requis',
	'admin'           => 'Admin',
	'list_categories' => 'Catégories',
	'edit'            => 'Editer',
	'save_button'     => 'Sauvegarder',
	'delete_button'   => 'Effacer',
	'error'           => 'Erreur',
	'missing_field'   => 'Au moins un champ est manquant. Vérifiez :',
	'save_fail'       => 'Sauvgarde échouée',
	'save_success'    => 'Sauvegarde réussie',
	'delete_fail'     => 'La suppression a échoué',
	'delete_success'  => 'La suppression a bien été effectuée',
	'message'         => 'Message',
	'validate_button' => 'OK',
	'fields'          => 'Champs',
	'new_field'       => 'Create a new field',
	'list_fields'     => 'List of the fields',
	'field_name'      => 'Name of the field',
	'field_order'     => 'Field order',
	'existing_field' => 'Champs existants (ordre et nom)',
	'var_name'        => 'Name of the variable',
	'field_help'      => 'Help for submission form',
	'type'            => 'Type',
	'sel_group'       => 'Select group',
	'create_new_doc'  => 'Créer un nouveau document',
	'field_require'   => 'Point out this is a required field on submission and edit forms',
	'field_on_list'   => 'This field must be a column on the list of documents',
	'edit_doc'        => 'Edition d\'un document',
	'selects'         => 'Selects',
	'list_groups'     => 'List of groups',
	'new_group'       => 'Create a new group',
	'list_selects'    => 'Liste des selects',
	'new_select'      => 'Créer un nouveau select',
	'new_option'      => 'Créer une nouvelle option',
	'group'           => 'Group',
	'group_name'      => 'Group name',
	'group_help'      => 'Group help',
	'edit_group'      => 'Edit group',
	'select_name'     => 'Name (or option value)',
	'select_value'    => 'Value to display for users',
	'existing_select' => 'Existings selects',
	'select_order'    => 'Select order',
	'doc_submission'  => 'Document submission',
	'submission'      => 'Soumission',
	'not_active'      => 'Non actif',
	'draft'           => 'Brouillon',
	'active_label'    => 'En ligne',
	'active'          => 'Active',
	'submission_recorded' => 'Votre document a bien été sauvegardé. Il va être vérifié avant d\'être disponible en ligne. Merci.', 
	'submissions_list'    => 'Liste des soumissions',
	'submissions_list_2'  => 'Liste des documents en cours d\'étude',
	'drafts_list'         => 'Liste des documents en cours de rédaction',
	'documents_list'      => 'Liste des documents',
	'reserved_to'         => 'Pour accéder à ce document vous devez faire partie du groupe :',
	'cat_hidden'          => 'Catégorie cachée',
	'see_all_docs'        => 'Voir toutes les fiches',
	'use_map'             => 'Utiliser la carte',
	'use_map_details'     => 'Si vous avez besoin de géolocaliser chaque document, paramétrez ici la carte sur laquelle vous souhaitez ajouter les marqueurs.',
	'nonactive'           => 'Inactive',
	'limited_access'      => 'Accès limité',
	'private'             => 'Privé',
	'doc_by'              => 'Ce document proposé par',
    'displayed'           => 'a été affiché',
	'times'               => 'fois.',
	'select_album'        => 'Selectionner l\'album :',
	'no_map'              => '-- Aucune --',
	'read_more_marker'    => 'Afficher le document',
	'document_draft'      => 'Ce document est en mode brouillon. Son accès est réservé à son propriétaire.',
	'document_submit'     => 'Ce document n\'a pas encore été approuvé et n\'est donc pas accessible pour le moment.',
	'new_comment'         => 'Nouveau commentaire sur',
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
