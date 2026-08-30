<?php

/* Modern Documents selection-options administration view. PHP 5.6+. */

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
    'title' => 'Options des groupes de choix',
    'lead' => 'Les options sont les valeurs proposées dans les champs de type liste. Chaque option possède une valeur interne stable et un libellé affiché à l’utilisateur.',
    'new' => 'Créer une option',
    'groups' => 'Groupes de choix',
    'fields' => 'Champs',
    'guide_title' => 'Valeur interne ou libellé affiché ?',
    'guide' => '<p>La <strong>valeur interne</strong> est enregistrée dans les documents. Choisissez-la courte et stable, par exemple <code>expert</code>. Le <strong>libellé affiché</strong> peut être plus naturel, par exemple « Expert / confirmé ».</p><p>Vous pouvez faire évoluer le libellé sans modifier la valeur technique. L’ordre 10, 20, 30… détermine l’ordre proposé dans la liste.</p>',
    'group' => 'Groupe',
    'internal' => 'Valeur interne',
    'label' => 'Libellé affiché',
    'order' => 'Ordre',
    'actions' => 'Actions',
    'edit' => 'Modifier',
    'filter' => 'Afficher le groupe',
    'all' => 'Tous les groupes',
    'empty' => 'Aucune option n’est encore définie pour ce groupe.'
) : array(
    'title' => 'Selection group options',
    'lead' => 'Options are the values offered in list fields. Each option has a stable internal value and a label shown to users.',
    'new' => 'Create an option',
    'groups' => 'Selection groups',
    'fields' => 'Fields',
    'guide_title' => 'Internal value or displayed label?',
    'guide' => '<p>The <strong>internal value</strong> is stored in documents. Keep it short and stable, for example <code>expert</code>. The <strong>displayed label</strong> can be more natural, for example “Expert / advanced”.</p><p>You can evolve the label without changing the technical value. Orders 10, 20, 30… control list ordering.</p>',
    'group' => 'Group',
    'internal' => 'Internal value',
    'label' => 'Displayed label',
    'order' => 'Order',
    'actions' => 'Actions',
    'edit' => 'Edit',
    'filter' => 'Show group',
    'all' => 'All groups',
    'empty' => 'No option is defined for this group yet.'
);

$siteUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
$selectedGroup = isset($_GET['group']) ? max(0, (int) $_GET['group']) : 0;
$groups = array();
$groupResult = DB_query("SELECT gid, g_name FROM {$_TABLES['documents_selects_group']} ORDER BY g_name ASC, gid ASC");
while ($group = DB_fetchArray($groupResult)) {
    if (is_array($group)) {
        $groups[] = $group;
    }
}

$sql = "SELECT s.sid, s.s_name, s.s_value, s.s_order, s.s_group, g.g_name "
    . "FROM {$_TABLES['documents_selects']} AS s LEFT JOIN {$_TABLES['documents_selects_group']} AS g ON g.gid=s.s_group";
if ($selectedGroup > 0) {
    $sql .= ' WHERE s.s_group=' . $selectedGroup;
}
$sql .= ' ORDER BY g.g_name ASC, s.s_order ASC, s.sid ASC';
$result = DB_query($sql);

$content = '<main class="documents-admin-page"><header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($text['title'], ENT_QUOTES, 'UTF-8') . '</h1><p class="documents-admin-page__lead">'
    . htmlspecialchars($text['lead'], ENT_QUOTES, 'UTF-8') . '</p></header>';
if (!empty($_GET['msg'])) {
    $content .= '<div class="documents-admin-message">' . htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}

$newUrl = $siteUrl . '/index.php?mode=edit_select' . ($selectedGroup > 0 ? '&group=' . $selectedGroup : '');
$content .= '<div class="documents-admin-toolbar"><a class="documents-admin-button documents-admin-button--primary" href="'
    . htmlspecialchars($newUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text['new'], ENT_QUOTES, 'UTF-8') . '</a>'
    . '<a class="documents-admin-button" href="' . htmlspecialchars($siteUrl . '/index.php?mode=list_groups', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($text['groups'], ENT_QUOTES, 'UTF-8') . '</a><a class="documents-admin-button" href="'
    . htmlspecialchars($siteUrl . '/index.php?mode=list_fields', ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text['fields'], ENT_QUOTES, 'UTF-8') . '</a></div>';
$content .= '<details class="documents-admin-guide"><summary>' . htmlspecialchars($text['guide_title'], ENT_QUOTES, 'UTF-8')
    . '</summary><div class="documents-admin-guide__content">' . $text['guide'] . '</div></details>';

if (!empty($groups)) {
    $content .= '<form method="get" action="' . htmlspecialchars($siteUrl . '/index.php', ENT_QUOTES, 'UTF-8')
        . '" class="documents-admin-card"><div class="documents-admin-card__body"><input type="hidden" name="mode" value="list_selects">'
        . '<label for="documents-select-group-filter"><strong>' . htmlspecialchars($text['filter'], ENT_QUOTES, 'UTF-8') . '</strong></label> '
        . '<select id="documents-select-group-filter" name="group" onchange="this.form.submit()"><option value="0">'
        . htmlspecialchars($text['all'], ENT_QUOTES, 'UTF-8') . '</option>';
    foreach ($groups as $group) {
        $selected = ((int) $group['gid'] === $selectedGroup) ? ' selected="selected"' : '';
        $content .= '<option value="' . (int) $group['gid'] . '"' . $selected . '>'
            . htmlspecialchars(stripslashes((string) $group['g_name']), ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $content .= '</select></div></form>';
}

$rows = array();
while ($option = DB_fetchArray($result)) {
    if (!is_array($option)) {
        continue;
    }
    $editUrl = $siteUrl . '/index.php?mode=edit_select&select=' . (int) $option['sid'];
    $display = trim(stripslashes((string) $option['s_value']));
    if ($display === '') {
        $display = stripslashes((string) $option['s_name']);
    }
    $rows[] = '<tr><td><span class="documents-admin-order">' . (int) $option['s_order'] . '</span><span class="documents-admin-badge">'
        . htmlspecialchars(stripslashes((string) $option['s_name']), ENT_QUOTES, 'UTF-8') . '</span></td><td><strong>'
        . htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . '</strong></td><td>'
        . htmlspecialchars(stripslashes((string) $option['g_name']), ENT_QUOTES, 'UTF-8') . '</td><td class="documents-admin-table__actions"><a href="'
        . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($text['edit'], ENT_QUOTES, 'UTF-8') . '</a></td></tr>';
}

if (empty($rows)) {
    $content .= '<p class="documents-admin-empty">' . htmlspecialchars($text['empty'], ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    $content .= '<section class="documents-admin-card"><div class="documents-admin-table-wrap"><table class="documents-admin-table"><thead><tr><th>'
        . htmlspecialchars($text['internal'], ENT_QUOTES, 'UTF-8') . '</th><th>' . htmlspecialchars($text['label'], ENT_QUOTES, 'UTF-8')
        . '</th><th>' . htmlspecialchars($text['group'], ENT_QUOTES, 'UTF-8') . '</th><th class="documents-admin-table__actions">'
        . htmlspecialchars($text['actions'], ENT_QUOTES, 'UTF-8') . '</th></tr></thead><tbody>' . implode('', $rows) . '</tbody></table></div></section>';
}
$content .= '</main>';
$pageOptions = array('pagetitle' => $text['title']);
COM_output(COM_createHTMLDocument($content, $pageOptions));
