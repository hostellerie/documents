<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | italian_utf-8.php                                                               |
// |                                                                           |
// | Italian language file                                                     |
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
    'plugin_name'         => 'Documenti',
    'categories'          => 'Categorie',
    'browse_categories'   => 'Esplora documenti pratici per categoria',
    'browse_documents'    => 'Esplora documenti',
    'documents'           => 'Documenti',
    'category'            => 'Categoria',
    'new_cat'             => 'Crea una nuova categoria',
    'edit_cat'            => 'Modifica una categoria',
    'cat_name'            => 'Nome categoria',
    'cat_url'             => 'Nome URL (senza spazi)',
    'cat_url_exists'      => 'Questo nome URL esiste già. Scegline un altro.',
    'css'                 => 'CSS',
    'template'            => 'Modello',
    'cat_order'           => 'Ordine',
    'existing_cat'        => 'Categorie esistenti (ordine, nome e nome URL)',
    'none'                => 'Nessuno',
    'cat_help'            => 'Aiuto per il modulo di invio (può contenere un autotag)',
    'list_index'          => 'Mostra questa categoria nell\'elenco',
    'submitable'          => 'Gli utenti possono inviare documenti a questa categoria',
    'custom_header'       => 'Intestazione personalizzata',
    'custom_footer'       => 'Piè di pagina personalizzato',
    'required_field'      => 'Indica un campo obbligatorio',
    'admin'               => 'Amministrazione',
    'list_categories'     => 'Categorie',
    'edit'                => 'Modifica',
    'save_button'         => 'Salva',
    'delete_button'       => 'Elimina',
    'error'               => 'Errore',
    'missing_field'       => 'Manca almeno un campo. Controlla:',
    'save_fail'           => 'Salvataggio non riuscito',
    'save_success'        => 'Salvataggio riuscito',
    'delete_fail'         => 'Eliminazione non riuscita',
    'delete_success'      => 'Eliminazione riuscita',
    'message'             => 'Messaggio',
    'validate_button'     => 'OK',
    'fields'              => 'Campi',
    'new_field'           => 'Crea un nuovo campo',
    'list_fields'         => 'Elenco dei campi',
    'field_name'          => 'Nome campo',
    'field_order'         => 'Ordine campo',
    'existing_field'      => 'Campi esistenti (ordine e nome)',
    'var_name'            => 'Nome variabile',
    'field_help'          => 'Aiuto per il modulo di invio',
    'type'                => 'Tipo',
    'sel_group'           => 'Gruppo di selezione',
    'create_new_doc'      => 'Crea un nuovo documento',
    'field_require'       => 'Rendi obbligatorio questo campo nei moduli di invio e modifica',
    'field_on_list'       => 'Mostra questo campo come colonna negli elenchi dei documenti',
    'edit_doc'            => 'Modifica un documento',
    'selects'             => 'Selezioni',
    'list_groups'         => 'Elenco dei gruppi',
    'new_group'           => 'Crea un nuovo gruppo',
    'list_selects'        => 'Elenco delle selezioni',
    'new_select'          => 'Crea una nuova selezione',
    'new_option'          => 'Crea una nuova opzione',
    'group'               => 'Gruppo',
    'group_name'          => 'Nome gruppo',
    'group_help'          => 'Aiuto gruppo',
    'edit_group'          => 'Modifica gruppo',
    'select_name'         => 'Nome (o valore opzione)',
    'select_value'        => 'Valore mostrato agli utenti',
    'existing_select'     => 'Selezioni esistenti',
    'select_order'        => 'Ordine selezione',
    'doc_submission'      => 'Invio documento',
    'submission'          => 'Invio',
    'pending_moderation'  => 'In attesa di moderazione',
    'not_active'          => 'Inattivo',
    'draft'               => 'Bozza',
    'active_label'        => 'Stato del documento',
    'active'              => 'Attivo',
    'submission_recorded' => 'Il documento è stato salvato. Verrà revisionato prima della pubblicazione. Grazie.',
    'submissions_list'    => 'Elenco degli invii',
    'submissions_list_2'  => 'Elenco dei documenti in revisione',
    'drafts_list'         => 'Elenco dei documenti in bozza',
    'documents_list'      => 'Elenco dei documenti',
    'reserved_to'         => 'Per accedere a questo documento devi appartenere al gruppo:',
    'cat_hidden'          => 'Categoria nascosta',
    'see_all_docs'        => 'Tutti i documenti',
    'use_map'             => 'Usa mappa',
    'use_map_details'     => 'Per geolocalizzare i documenti, seleziona la mappa sulla quale aggiungere i marcatori.',
    'nonactive'           => 'Inattivo',
    'limited_access'      => 'Accesso limitato',
    'private'             => 'Privato',
    'doc_by'              => 'Questo documento di',
    'displayed'           => 'è stato visualizzato',
    'times'               => 'volte.',
    'select_album'        => 'Seleziona album:',
    'no_map'              => '-- Nessuno --',
    'read_more_marker'    => 'Visualizza documento',
    'document_draft'      => 'Questo documento è in modalità bozza. L\'accesso è riservato al proprietario.',
    'document_submit'     => 'Questo documento non è ancora stato approvato e non è attualmente disponibile.',
    'new_comment'         => 'Nuovo commento su',
    'stats_title'         => 'Primi dieci documenti',
    'stats_documents'     => 'Documenti pubblicati',
    'stats_views'         => 'Visualizzazioni',
    'whatsnew_title'      => 'Documenti recenti',
    'whatsnew_none'       => 'Nessun documento recente.',
    'more_information'    => 'Maggiori informazioni',
    'integrity_audit_title'              => 'Verifica integrità dati',
    'integrity_audit_notice'             => 'Questo rapporto è di sola lettura. Nessun dato o file viene modificato.',
    'integrity_check'                    => 'Controllo',
    'integrity_result'                   => 'Risultato',
    'integrity_duplicate_category_slugs' => 'Slug di categoria duplicati',
    'integrity_duplicate_document_slugs' => 'Slug di documento duplicati',
    'integrity_documents_without_values' => 'Documenti senza valori',
    'integrity_values_without_document'  => 'Valori senza documento',
    'integrity_values_without_field'     => 'Valori senza campo',
    'integrity_fields_without_category'  => 'Campi senza categoria',
    'integrity_missing_images'           => 'File immagine referenziati mancanti sul disco',
    'integrity_unreferenced_images'      => 'File immagine non referenziati da Documenti',
    'integrity_back_admin'               => 'Torna all\'amministrazione Documenti'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => 'Documenti',
    'title' => 'Configurazione Documenti'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Cartella Documenti',
    'documents_main_header' => 'Intestazione principale Documenti',
    'documents_main_footer' => 'Piè di pagina principale Documenti',
    'whatsnew_enabled'      => 'Mostra Documenti in Novità',
    'whatsnew_interval'     => 'Periodo Novità (secondi)',
    'whatsnew_limit'        => 'Numero massimo di documenti recenti',
    'stats_visibility'      => 'Visibilità statistiche',
    'max_image_width'       => 'Larghezza massima immagine (pixel)',
    'max_image_height'      => 'Altezza massima immagine (pixel)',
    'max_image_size'        => 'Dimensione massima file immagine (byte)',
    'default_permissions'   => 'Permessi predefiniti'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'Impostazioni principali'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Impostazioni principali Documenti'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'Impostazioni principali Documenti',
    'fs_integrations' => 'Visualizzazione e integrazioni',
    'fs_images'       => 'Immagini',
    'fs_permissions'  => 'Permessi predefiniti'
);

$LANG_configselects['documents'] = array(
    0  => array('Sì' => 1, 'No' => 0),
    1  => array('Sì' => true, 'No' => false),
    12 => array('Nessun accesso' => 0, 'Sola lettura' => 2, 'Lettura-scrittura' => 3),
    20 => array(
        'Nascosto' => 0,
        'Solo amministratori' => 1,
        'Utenti autenticati e amministratori' => 2,
        'Tutti, inclusi i visitatori anonimi' => 3
    )
);