<?php

/* Modern Documents selection-group editor. PHP 5.6+. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)
    || !SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'admin_styles.php';
DOCUMENTS_loadAdminStyles();

$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

$text = $isFrench ? array(
    'title_new' => 'Créer un groupe de choix',
    'title_edit' => 'Modifier le groupe de choix',
    'lead' => 'Un groupe rassemble les valeurs proposées dans un champ de type liste. Après l’enregistrement, ajoutez ses options puis associez le groupe au champ concerné.',
    'name' => 'Nom du groupe',
    'name_help' => 'Utilisez un nom court et explicite, par exemple « Niveau », « Région » ou « Type de matériau ». Ce nom sert surtout à l’administration.',
    'help' => 'Texte d’aide',
    'help_help' => 'Texte facultatif pouvant guider l’utilisateur lorsqu’il choisit une valeur. Évitez d’y recopier simplement le nom du groupe.',
    'save' => 'Enregistrer',
    'delete' => 'Supprimer',
    'action' => 'Action',
    'required' => 'Champ obligatoire',
    'back' => 'Retour aux groupes',
    'options' => 'Gérer les options',
    'options_count' => 'Options actuellement enregistrées',
    'danger' => 'La suppression du groupe supprime également toutes ses options. Vérifiez d’abord qu’aucun champ actif ne dépend encore de ce groupe.'
) : array(
    'title_new' => 'Create a selection group',
    'title_edit' => 'Edit selection group',
    'lead' => 'A group contains the values offered by a list field. After saving it, add its options and then assign the group to the relevant field.',
    'name' => 'Group name',
    'name_help' => 'Use a short, explicit name such as “Level”, “Region” or “Material type”. This name is mainly used in administration.',
    'help' => 'Help text',
    'help_help' => 'Optional guidance shown when a user chooses a value. Avoid simply repeating the group name.',
    'save' => 'Save',
    'delete' => 'Delete',
    'action' => 'Action',
    'required' => 'Required field',
    'back' => 'Back to groups',
    'options' => 'Manage options',
    'options_count' => 'Options currently stored',
    'danger' => 'Deleting the group also deletes all of its options. First make sure that no active field still depends on this group.'
);

$siteUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
$gid = isset($_GET['group']) ? max(0, (int) $_GET['group']) : 0;
$group = array('gid' => 0, 'g_name' => '', 'g_help' => '');
$optionCount = 0;

if ($gid > 0) {
    $result = DB_query(
        "SELECT gid, g_name, g_help FROM {$_TABLES['documents_selects_group']} WHERE gid={$gid} LIMIT 1"
    );
    $row = DB_fetchArray($result);
    if (!is_array($row) || empty($row['gid'])) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
    $group = array_merge($group, $row);
    $optionCount = (int) DB_getItem(
        $_TABLES['documents_selects'],
        'COUNT(*)',
        's_group=' . $gid
    );
}

$title = $gid > 0 ? $text['title_edit'] : $text['title_new'];
$token = SEC_createToken();

$content = '<main class="documents-admin-page"><header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p class="documents-admin-page__lead">'
    . htmlspecialchars($text['lead'], ENT_QUOTES, 'UTF-8') . '</p></header>';

if (!empty($_GET['msg'])) {
    $content .= '<div class="documents-admin-message">'
        . htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}

$content .= '<div class="documents-admin-toolbar"><a class="documents-admin-button" href="'
    . htmlspecialchars($siteUrl . '/index.php?mode=list_groups', ENT_QUOTES, 'UTF-8') . '">← '
    . htmlspecialchars($text['back'], ENT_QUOTES, 'UTF-8') . '</a>';
if ($gid > 0) {
    $content .= '<a class="documents-admin-button" href="'
        . htmlspecialchars($siteUrl . '/index.php?mode=list_selects&group=' . $gid, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($text['options'], ENT_QUOTES, 'UTF-8') . ' (' . $optionCount . ')</a>';
}
$content .= '</div>';

$content .= '<form class="documents-admin-form" action="'
    . htmlspecialchars($siteUrl . '/index.php', ENT_QUOTES, 'UTF-8') . '" method="post">'
    . '<section class="documents-admin-form__section">'
    . '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-group-name">'
    . htmlspecialchars($text['name'], ENT_QUOTES, 'UTF-8') . ' <span class="documents-required-mark">*</span></label>'
    . '<input class="documents-admin-form__control" id="documents-group-name" type="text" name="g_name" maxlength="255" required="required" value="'
    . htmlspecialchars(stripslashes((string) $group['g_name']), ENT_QUOTES, 'UTF-8') . '">'
    . '<span class="documents-admin-form__help">' . htmlspecialchars($text['name_help'], ENT_QUOTES, 'UTF-8') . '</span></div>'
    . '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-group-help">'
    . htmlspecialchars($text['help'], ENT_QUOTES, 'UTF-8') . '</label>'
    . '<textarea class="documents-admin-form__control" id="documents-group-help" name="g_help" maxlength="255">'
    . htmlspecialchars(stripslashes((string) $group['g_help']), ENT_QUOTES, 'UTF-8') . '</textarea>'
    . '<span class="documents-admin-form__help">' . htmlspecialchars($text['help_help'], ENT_QUOTES, 'UTF-8') . '</span></div>'
    . '</section>';

$content .= '<div class="documents-admin-form__actions"><label for="documents-group-action"><strong>'
    . htmlspecialchars($text['action'], ENT_QUOTES, 'UTF-8') . '</strong></label><select id="documents-group-action" name="op">'
    . '<option value="save">' . htmlspecialchars($text['save'], ENT_QUOTES, 'UTF-8') . '</option>';
if ($gid > 0) {
    $content .= '<option value="delete">' . htmlspecialchars($text['delete'], ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select><input class="documents-admin-submit" type="submit" value="'
    . htmlspecialchars($text['save'], ENT_QUOTES, 'UTF-8') . '"></div>';

if ($gid > 0) {
    $content .= '<p class="documents-admin-danger-note">'
        . htmlspecialchars($text['danger'], ENT_QUOTES, 'UTF-8') . '</p>';
}

$content .= '<input type="hidden" name="mode" value="save_group">'
    . '<input type="hidden" name="gid" value="' . (int) $gid . '">'
    . '<input type="hidden" name="' . htmlspecialchars(CSRF_TOKEN, ENT_QUOTES, 'UTF-8') . '" value="'
    . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '"></form></main>';

$pageOptions = array('pagetitle' => $title);
COM_output(COM_createHTMLDocument($content, $pageOptions));
