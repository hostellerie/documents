<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.2                                                    |
// +---------------------------------------------------------------------------+
// | french_france_utf-8.php                                                   |
// |                                                                           |
// | French language file                                                      |
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

global $LANG32;

$LANG_DOCUMENTS_1 = array(
    'plugin_name'     => 'Documents',
    'categories'      => 'Catégories',
	'documents'       => 'Documents',
	'category'        => 'Catégorie',
	'new_cat'         => 'Créer une nouvelle catégorie',
	'edit_cat'        => 'Éditer une catégorie',
	'cat_name'        => 'Nom de la catégorie',
	'cat_url'         => 'Nom pour l\'URL (sans espace)',
	'cat_url_exists'  => 'Le nom pour l\'URL existe déjà. Merci de le changer.',
	'css'             => 'CSS',
	'template'        => 'Template',
    'cat_order'      => 'Ordre',
	'existing_cat'    => 'Catégories existantes (ordre, nom et URL)',
	'none'            => 'Aucune',
	'cat_help'        => 'Aide pour le formulaire de soumission (peut contenir un autotag)',
	'list_index'      => 'Afficher cette catégorie dans la liste',
	'submitable'      => 'Les utilisateurs peuvent proposer des documents dans cette catégorie',
	'custom_header'   => 'En-tête personnalisé',
	'custom_footer'   => 'Pied de page personnalisé',
	'required_field'  => 'Indique un champ requis',
	'admin'           => 'Admin',
	'list_categories' => 'Catégories',
	'edit'            => 'Éditer',
	'save_button'     => 'Sauvegarder',
	'delete_button'   => 'Supprimer',
	'error'           => 'Erreur',
	'missing_field'   => 'Au moins un champ est manquant. Vérifiez :',
	'save_fail'       => 'La sauvegarde a échoué',
	'save_success'    => 'Sauvegarde réussie',
	'delete_fail'     => 'La suppression a échoué',
	'delete_success'  => 'La suppression a bien été effectuée',
	'message'         => 'Message',
	'validate_button' => 'OK',
	'fields'          => 'Champs',
	'new_field'       => 'Créer un nouveau champ',
	'list_fields'     => 'Liste des champs',
	'field_name'      => 'Nom du champ',
	'field_order'     => 'Ordre du champ',
	'existing_field'  => 'Champs existants (ordre et nom)',
	'var_name'        => 'Nom de la variable',
	'field_help'      => 'Aide pour le formulaire de soumission',
	'type'            => 'Type',
	'sel_group'       => 'Groupe de sélection',
	'create_new_doc'  => 'Créer un nouveau document',
	'field_require'   => 'Indiquer que ce champ est obligatoire dans les formulaires de soumission et d\'édition',
	'field_on_list'   => 'Afficher ce champ comme colonne dans la liste des documents',
	'edit_doc'        => 'Édition d\'un document',
	'selects'         => 'Sélections',
	'list_groups'     => 'Liste des groupes',
	'new_group'       => 'Créer un nouveau groupe',
	'list_selects'    => 'Liste des sélections',
	'new_select'      => 'Créer une nouvelle sélection',
	'new_option'      => 'Créer une nouvelle option',
	'group'           => 'Groupe',
	'group_name'      => 'Nom du groupe',
	'group_help'      => 'Aide du groupe',
	'edit_group'      => 'Éditer le groupe',
	'select_name'     => 'Nom (ou valeur de l\'option)',
	'select_value'    => 'Valeur affichée aux utilisateurs',
	'existing_select' => 'Sélections existantes',
	'select_order'    => 'Ordre de la sélection',
	'doc_submission'  => 'Soumission de document',
	'submission'      => 'Soumission',
	'not_active'      => 'Non actif',
	'draft'           => 'Brouillon',
	'active_label'    => 'Document actif',
	'active'          => 'Actif',
	'submission_recorded' => 'Votre document a bien été sauvegardé. Il va être vérifié avant d\'être disponible en ligne. Merci.',
	'submissions_list'    => 'Liste des soumissions',
	'submissions_list_2'  => 'Liste des documents en cours d\'étude',
	'drafts_list'         => 'Liste des documents en cours de rédaction',
	'documents_list'      => 'Liste des documents',
	'reserved_to'         => 'Pour accéder à ce document vous devez faire partie du groupe :',
	'cat_hidden'          => 'Catégorie cachée',
	'see_all_docs'        => 'Voir tous les documents',
	'use_map'             => 'Utiliser la carte',
	'use_map_details'     => 'Si vous avez besoin de géolocaliser chaque document, paramétrez ici la carte sur laquelle vous souhaitez ajouter les marqueurs.',
	'nonactive'           => 'Inactif',
	'limited_access'      => 'Accès limité',
	'private'             => 'Privé',
	'doc_by'              => 'Ce document proposé par',
    'displayed'         => 'a été affiché',
	'times'               => 'fois.',
	'select_album'        => 'Sélectionner l\'album :',
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
    'title' => 'Configuration de Documents'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Dossier public de Documents',
    'documents_main_header' => 'En-tête principal de Documents',
    'documents_main_footer' => 'Pied de page principal de Documents',
    'max_image_width'       => 'Largeur maximale des images (pixels)',
    'max_image_height'      => 'Hauteur maximale des images (pixels)',
    'max_image_size'        => 'Taille maximale des fichiers image (octets)',
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'Paramètres principaux'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Paramètres principaux de Documents'
);

$LANG_fs['documents'] = array(
    'fs_main'        => 'Paramètres principaux de Documents',
    'fs_images'      => 'Images',
	'fs_permissions' => 'Permissions par défaut',
);

$LANG_configselects['documents'] = array(
    0 => array('Oui' => 1, 'Non' => 0),
    1 => array('Oui' => true, 'Non' => false),
	12 => array('Aucun accès' => 0, 'Lecture seule' => 2, 'Lecture-écriture' => 3),
);
?>
