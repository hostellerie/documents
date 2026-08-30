<?php

/* Modern Documents selection-groups administration view. PHP 5.6+. */

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
    'title' => 'Groupes de choix',
    'lead' => 'Un groupe de choix rassemble les options disponibles pour un champ de type liste. Exemple : un groupe « Niveau » peut contenir Débutant, Intermédiaire et Expert.',
    'new' => 'Créer un groupe',
    'fields' => 'Champs',
    'guide_title' => 'À quoi servent les groupes de choix ?',
    'guide' => '<p>Créez un groupe lorsqu’un même ensemble de valeurs doit être proposé dans une liste déroulante. Vous pourrez ensuite ajouter et ordonner les options de ce groupe, puis l’associer à un champ de type sélection.</p><p>Le texte d’aide du groupe peut servir d’indication dans le formulaire de saisie. Donnez aux groupes des noms explicites et réutilisables.</p>',
    'group' => 'Groupe',
    'help' => 'Aide',
    'options' => 'Options',
    'actions' => 'Actions',
    'manage' => 'Gérer les options',
    'edit' => 'Modifier',
    'empty' => 'Aucun groupe de choix n’existe encore. Créez-en un si vous souhaitez utiliser des champs de type liste.'
) : array(
    'title' => 'Selection groups',
    'lead' => 'A selection group contains the options available to a list field. For example, a “Level” group could contain Beginner, Intermediate and Expert.',
    'new' => 'Create a group',
    'fields' => 'Fields',
    'guide_title' => 'What are selection groups for?',
    'guide' => '<p>Create a group when the same set of values should be offered in a drop-down list. You can then add and order its options and assign the group to a selection field.</p><p>The group help text can be displayed as guidance in the submission form. Use clear, reusable group names.</p>',
    'group' => 'Group',
    'help' => 'Help',
    'options' => 'Options',
    'actions' => 'Actions',
    'manage' => 'Manage options',
    'edit' => 'Edit',
    'empty' => 'No selection groups exist yet. Create one when you need a list-type field.'
);

$siteUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
$result = DB_query(
    "SELECT g.gid, g.g_name, g.g_help, COUNT(s.sid) AS option_count "
    . "FROM {$_TABLES['documents_selects_group']} AS g "
    . "LEFT JOIN {$_TABLES['documents_selects']} AS s ON s.s_group=g.gid "
    . "GROUP BY g.gid, g.g_name, g.g_help ORDER BY g.g_name ASC, g.gid ASC"
);

$content = '<main class="documents-admin-page">';
$content .= '<header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($text['title'], ENT_QUOTES, 'UTF-8') . '</h1><p class="documents-admin-page__lead">'
    . htmlspecialchars($text['lead'], ENT_QUOTES, 'UTF-8') . '</p></header>';

if (!empty($_GET['msg'])) {
    $content .= '<div class="documents-admin-message">'
        . htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}

$content .= '<div class="documents-admin-toolbar">'
    . '<a class="documents-admin-button documents-admin-button--primary" href="'
    . htmlspecialchars($siteUrl . '/index.php?mode=edit_group', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($text['new'], ENT_QUOTES, 'UTF-8') . '</a>'
    . '<a class="documents-admin-button" href="'
    . htmlspecialchars($siteUrl . '/index.php?mode=list_fields', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($text['fields'], ENT_QUOTES, 'UTF-8') . '</a></div>';

$content .= '<details class="documents-admin-guide"><summary>'
    . htmlspecialchars($text['guide_title'], ENT_QUOTES, 'UTF-8')
    . '</summary><div class="documents-admin-guide__content">' . $text['guide'] . '</div></details>';

$rows = array();
while ($group = DB_fetchArray($result)) {
    if (!is_array($group)) {
        continue;
    }
    $editUrl = $siteUrl . '/index.php?mode=edit_group&group=' . (int) $group['gid'];
    $optionsUrl = $siteUrl . '/index.php?mode=list_selects&group=' . (int) $group['gid'];
    $help = trim(stripslashes((string) $group['g_help']));
    if ($help === '') {
        $help = '—';
    }
    $rows[] = '<tr><td><strong>'
        . htmlspecialchars(stripslashes((string) $group['g_name']), ENT_QUOTES, 'UTF-8')
        . '</strong></td><td class="documents-admin-muted">'
        . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</td><td>'
        . (int) $group['option_count'] . '</td><td class="documents-admin-table__actions"><a href="'
        . htmlspecialchars($optionsUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($text['manage'], ENT_QUOTES, 'UTF-8') . '</a> · <a href="'
        . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($text['edit'], ENT_QUOTES, 'UTF-8') . '</a></td></tr>';
}

if (empty($rows)) {
    $content .= '<p class="documents-admin-empty">'
        . htmlspecialchars($text['empty'], ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    $content .= '<section class="documents-admin-card"><div class="documents-admin-table-wrap"><table class="documents-admin-table">'
        . '<thead><tr><th>' . htmlspecialchars($text['group'], ENT_QUOTES, 'UTF-8') . '</th><th>'
        . htmlspecialchars($text['help'], ENT_QUOTES, 'UTF-8') . '</th><th>'
        . htmlspecialchars($text['options'], ENT_QUOTES, 'UTF-8') . '</th><th class="documents-admin-table__actions">'
        . htmlspecialchars($text['actions'], ENT_QUOTES, 'UTF-8') . '</th></tr></thead><tbody>'
        . implode('', $rows) . '</tbody></table></div></section>';
}

$content .= '</main>';
$pageOptions = array('pagetitle' => $text['title']);
COM_output(COM_createHTMLDocument($content, $pageOptions));
