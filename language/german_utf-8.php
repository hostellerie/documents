<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | german_utf-8.php                                                               |
// |                                                                           |
// | German language file                                                     |
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
    'plugin_name'         => 'Dokumente',
    'categories'          => 'Kategorien',
    'browse_categories'   => 'Praktische Dokumente nach Kategorie durchsuchen',
    'browse_documents'    => 'Dokumente durchsuchen',
    'documents'           => 'Dokumente',
    'category'            => 'Kategorie',
    'new_cat'             => 'Neue Kategorie erstellen',
    'edit_cat'            => 'Kategorie bearbeiten',
    'cat_name'            => 'Kategoriename',
    'cat_url'             => 'URL-Name (ohne Leerzeichen)',
    'cat_url_exists'      => 'Dieser URL-Name existiert bereits. Bitte wählen Sie einen anderen.',
    'css'                 => 'CSS',
    'template'            => 'Vorlage',
    'cat_order'           => 'Reihenfolge',
    'existing_cat'        => 'Vorhandene Kategorien (Reihenfolge, Name und URL-Name)',
    'none'                => 'Keine',
    'cat_help'            => 'Hilfe für das Einreichungsformular (kann ein Autotag enthalten)',
    'list_index'          => 'Diese Kategorie in der Liste anzeigen',
    'submitable'          => 'Benutzer können Dokumente in dieser Kategorie einreichen',
    'custom_header'       => 'Benutzerdefinierte Kopfzeile',
    'custom_footer'       => 'Benutzerdefinierte Fußzeile',
    'required_field'      => 'Kennzeichnet ein Pflichtfeld',
    'admin'               => 'Administration',
    'list_categories'     => 'Kategorien',
    'edit'                => 'Bearbeiten',
    'save_button'         => 'Speichern',
    'delete_button'       => 'Löschen',
    'error'               => 'Fehler',
    'missing_field'       => 'Mindestens ein Feld fehlt. Bitte prüfen Sie:',
    'save_fail'           => 'Speichern fehlgeschlagen',
    'save_success'        => 'Erfolgreich gespeichert',
    'delete_fail'         => 'Löschen fehlgeschlagen',
    'delete_success'      => 'Erfolgreich gelöscht',
    'message'             => 'Nachricht',
    'validate_button'     => 'OK',
    'fields'              => 'Felder',
    'new_field'           => 'Neues Feld erstellen',
    'list_fields'         => 'Feldliste',
    'field_name'          => 'Feldname',
    'field_order'         => 'Feldreihenfolge',
    'existing_field'      => 'Vorhandene Felder (Reihenfolge und Name)',
    'var_name'            => 'Variablenname',
    'field_help'          => 'Hilfe für das Einreichungsformular',
    'type'                => 'Typ',
    'sel_group'           => 'Auswahlgruppe',
    'create_new_doc'      => 'Neues Dokument erstellen',
    'field_require'       => 'Dieses Feld in Einreichungs- und Bearbeitungsformularen verlangen',
    'field_on_list'       => 'Dieses Feld als Spalte in Dokumentlisten anzeigen',
    'edit_doc'            => 'Dokument bearbeiten',
    'selects'             => 'Auswahlen',
    'list_groups'         => 'Gruppenliste',
    'new_group'           => 'Neue Gruppe erstellen',
    'list_selects'        => 'Auswahlliste',
    'new_select'          => 'Neue Auswahl erstellen',
    'new_option'          => 'Neue Option erstellen',
    'group'               => 'Gruppe',
    'group_name'          => 'Gruppenname',
    'group_help'          => 'Gruppenhilfe',
    'edit_group'          => 'Gruppe bearbeiten',
    'select_name'         => 'Name (oder Optionswert)',
    'select_value'        => 'Für Benutzer angezeigter Wert',
    'existing_select'     => 'Vorhandene Auswahlen',
    'select_order'        => 'Auswahlreihenfolge',
    'doc_submission'      => 'Dokumenteinreichung',
    'submission'          => 'Einreichung',
    'pending_moderation'  => 'Moderation ausstehend',
    'not_active'          => 'Inaktiv',
    'draft'               => 'Entwurf',
    'active_label'        => 'Dokumentstatus',
    'active'              => 'Aktiv',
    'submission_recorded' => 'Ihr Dokument wurde gespeichert. Es wird vor der Veröffentlichung geprüft. Vielen Dank.',
    'submissions_list'    => 'Liste der Einreichungen',
    'submissions_list_2'  => 'Liste der geprüften Dokumente',
    'drafts_list'         => 'Liste der Dokumententwürfe',
    'documents_list'      => 'Dokumentliste',
    'reserved_to'         => 'Um auf dieses Dokument zuzugreifen, müssen Sie der folgenden Gruppe angehören:',
    'cat_hidden'          => 'Versteckte Kategorie',
    'see_all_docs'        => 'Alle Dokumente',
    'use_map'             => 'Karte verwenden',
    'use_map_details'     => 'Wählen Sie zur Geolokalisierung von Dokumenten die Karte aus, auf der Markierungen hinzugefügt werden sollen.',
    'nonactive'           => 'Inaktiv',
    'limited_access'      => 'Eingeschränkter Zugriff',
    'private'             => 'Privat',
    'doc_by'              => 'Dieses Dokument von',
    'displayed'           => 'wurde angezeigt',
    'times'               => 'Mal.',
    'select_album'        => 'Album auswählen:',
    'no_map'              => '-- Keine --',
    'read_more_marker'    => 'Dokument anzeigen',
    'document_draft'      => 'Dieses Dokument befindet sich im Entwurfsmodus. Der Zugriff ist seinem Eigentümer vorbehalten.',
    'document_submit'     => 'Dieses Dokument wurde noch nicht genehmigt und ist derzeit nicht verfügbar.',
    'new_comment'         => 'Neuer Kommentar zu',
    'stats_title'         => 'Top-Ten-Dokumente',
    'stats_documents'     => 'Veröffentlichte Dokumente',
    'stats_views'         => 'Aufrufe',
    'whatsnew_title'      => 'Aktuelle Dokumente',
    'whatsnew_none'       => 'Keine aktuellen Dokumente.',
    'more_information'    => 'Weitere Informationen',
    'integrity_audit_title'              => 'Datenintegritätsprüfung',
    'integrity_audit_notice'             => 'Dieser Bericht ist schreibgeschützt. Es werden keine Daten oder Dateien geändert.',
    'integrity_check'                    => 'Prüfung',
    'integrity_result'                   => 'Ergebnis',
    'integrity_duplicate_category_slugs' => 'Doppelte Kategorie-Slugs',
    'integrity_duplicate_document_slugs' => 'Doppelte Dokument-Slugs',
    'integrity_documents_without_values' => 'Dokumente ohne Werte',
    'integrity_values_without_document'  => 'Werte ohne Dokument',
    'integrity_values_without_field'     => 'Werte ohne Feld',
    'integrity_fields_without_category'  => 'Felder ohne Kategorie',
    'integrity_missing_images'           => 'Referenzierte Bilddateien fehlen auf dem Datenträger',
    'integrity_unreferenced_images'      => 'Bilddateien, auf die Documents nicht verweist',
    'integrity_back_admin'               => 'Zurück zur Documents-Administration'
);

$PLG_documents_MESSAGE3002 = $LANG32[9];

$LANG_configsections['documents'] = array(
    'label' => 'Dokumente',
    'title' => 'Documents-Konfiguration'
);

$LANG_confignames['documents'] = array(
    'documents_folder'      => 'Documents-Ordner',
    'documents_main_header' => 'Documents-Hauptkopfzeile',
    'documents_main_footer' => 'Documents-Hauptfußzeile',
    'whatsnew_enabled'      => 'Documents in Neuigkeiten anzeigen',
    'whatsnew_interval'     => 'Zeitraum für Neuigkeiten (Sekunden)',
    'whatsnew_limit'        => 'Maximale Anzahl aktueller Dokumente',
    'stats_visibility'      => 'Sichtbarkeit der Statistiken',
    'max_image_width'       => 'Maximale Bildbreite (Pixel)',
    'max_image_height'      => 'Maximale Bildhöhe (Pixel)',
    'max_image_size'        => 'Maximale Bilddateigröße (Bytes)',
    'default_permissions'   => 'Standardberechtigungen'
);

$LANG_configsubgroups['documents'] = array(
    'sg_main' => 'Haupteinstellungen'
);

$LANG_tab['documents'] = array(
    'tab_main' => 'Documents-Haupteinstellungen'
);

$LANG_fs['documents'] = array(
    'fs_main'         => 'Documents-Haupteinstellungen',
    'fs_integrations' => 'Anzeige und Integrationen',
    'fs_images'       => 'Bilder',
    'fs_permissions'  => 'Standardberechtigungen'
);

$LANG_configselects['documents'] = array(
    0  => array('Ja' => 1, 'Nein' => 0),
    1  => array('Ja' => true, 'Nein' => false),
    12 => array('Kein Zugriff' => 0, 'Nur Lesen' => 2, 'Lesen-Schreiben' => 3),
    20 => array(
        'Versteckt' => 0,
        'Nur Administratoren' => 1,
        'Angemeldete Benutzer und Administratoren' => 2,
        'Alle, einschließlich anonymer Besucher' => 3
    )
);