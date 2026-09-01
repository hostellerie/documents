<?php

/* Modern Documents fields administration view. PHP 5.6+. */

require_once dirname(__DIR__) . '/lib-common.php';

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
    'title' => 'Champs des documents',
    'lead' => 'Les champs définissent la structure des documents d’une catégorie : titre, texte, date, image, liste de choix, etc. Leur ordre détermine aussi l’ordre d’affichage et de saisie.',
    'new' => 'Créer un champ',
    'groups' => 'Groupes de choix',
    'guide_title' => 'Comment organiser les champs ?',
    'guide' => '<p>Chaque champ appartient à une catégorie. Le premier champ est généralement utilisé comme titre du document. La variable technique, affichée sous la forme <code>{variable}</code>, permet de réutiliser la valeur dans les templates et les intégrations.</p><ul><li>Utilisez des ordres 10, 20, 30… pour garder de la place entre les champs.</li><li>Activez « afficher dans la liste » seulement pour les informations utiles dans les listes publiques.</li><li>Pour un champ de type liste, créez d’abord un groupe de choix puis ses options.</li></ul>',
    'field' => 'Champ',
    'category' => 'Catégorie',
    'variable' => 'Variable',
    'order' => 'Ordre',
    'actions' => 'Actions',
    'edit' => 'Modifier',
    'empty' => 'Aucun champ n’est encore défini. Créez le premier champ d’une catégorie avant de créer des documents dans celle-ci.',
    'filter' => 'Filtrer par catégorie',
    'all' => 'Toutes les catégories'
) : array(
    'title' => 'Document fields',
    'lead' => 'Fields define the structure of documents in a category: title, text, date, image, selection list, and more. Their order also controls input and display order.',
    'new' => 'Create a field',
    'groups' => 'Selection groups',
    'guide_title' => 'How should fields be organised?',
    'guide' => '<p>Each field belongs to one category. The first field is usually used as the document title. The technical variable, shown as <code>{variable}</code>, can be reused in templates and integrations.</p><ul><li>Prefer orders 10, 20, 30… to leave room between fields.</li><li>Enable “show in list” only for information useful in public lists.</li><li>For selection fields, create a selection group and its options first.</li></ul>',
    'field' => 'Field',
    'category' => 'Category',
    'variable' => 'Variable',
    'order' => 'Order',
    'actions' => 'Actions',
    'edit' => 'Edit',
    'empty' => 'No fields are defined yet. Create the first field for a category before creating documents in it.',
    'filter' => 'Filter by category',
    'all' => 'All categories'
);

$siteUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
$selectedCategory = isset($_GET['cat']) ? max(0, (int) $_GET['cat']) : 0;

$categories = array();
$categoryResult = DB_query(
    "SELECT cid, cat_name FROM {$_TABLES['documents_cat']} ORDER BY cat_order ASC, cat_name ASC"
);
while ($category = DB_fetchArray($categoryResult)) {
    if (is_array($category)) {
        $categories[] = $category;
    }
}

$where = ' WHERE 1=1 ';
if ($selectedCategory > 0) {
    $where .= ' AND f.cat_id=' . $selectedCategory . ' ';
}
$where .= COM_getPermSQL('AND', 0, 3, 'c');

$result = DB_query(
    "SELECT f.fid, f.f_name, f.var_name, f.f_order, f.f_type, f.cat_id, c.cat_name "
    . "FROM {$_TABLES['documents_fields']} AS f "
    . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id"
    . $where
    . " ORDER BY c.cat_order ASC, c.cat_name ASC, f.f_order ASC, f.fid ASC"
);

$content = '<main class="documents-admin-page">'
    . DOCUMENTS_adminNavigation('list_fields')
    . '<header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($text['title'], ENT_QUOTES, 'UTF-8') . '</h1><p class="documents-admin-page__lead">'
    . htmlspecialchars($text['lead'], ENT_QUOTES, 'UTF-8') . '</p></header>';

if (!empty($_GET['msg'])) {
    $content .= '<div class="documents-admin-message">'
        . htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}

$newFieldUrl = $siteUrl . '/index.php?mode=edit_field';
if ($selectedCategory > 0) {
    $newFieldUrl .= '&cat=' . $selectedCategory;
}

$content .= '<div class="documents-admin-toolbar">'
    . '<a class="documents-admin-button documents-admin-button--primary" href="'
    . htmlspecialchars($newFieldUrl, ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($text['new'], ENT_QUOTES, 'UTF-8') . '</a>'
    . '<a class="documents-admin-button" href="'
    . htmlspecialchars($siteUrl . '/index.php?mode=list_groups', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($text['groups'], ENT_QUOTES, 'UTF-8') . '</a></div>';

$content .= '<details class="documents-admin-guide"><summary>'
    . htmlspecialchars($text['guide_title'], ENT_QUOTES, 'UTF-8')
    . '</summary><div class="documents-admin-guide__content">' . $text['guide'] . '</div></details>';

if (!empty($categories)) {
    $content .= '<form method="get" action="' . htmlspecialchars($siteUrl . '/index.php', ENT_QUOTES, 'UTF-8')
        . '" class="documents-admin-card"><div class="documents-admin-card__body">'
        . '<input type="hidden" name="mode" value="list_fields">'
        . '<label for="documents-field-category"><strong>'
        . htmlspecialchars($text['filter'], ENT_QUOTES, 'UTF-8') . '</strong></label> '
        . '<select id="documents-field-category" name="cat" onchange="this.form.submit()">'
        . '<option value="0">' . htmlspecialchars($text['all'], ENT_QUOTES, 'UTF-8') . '</option>';
    foreach ($categories as $category) {
        $selected = ((int) $category['cid'] === $selectedCategory) ? ' selected="selected"' : '';
        $content .= '<option value="' . (int) $category['cid'] . '"' . $selected . '>'
            . htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $content .= '</select></div></form>';
}

$rows = array();
while ($field = DB_fetchArray($result)) {
    if (!is_array($field)) {
        continue;
    }
    $editUrl = $siteUrl . '/index.php?mode=edit_field&field=' . (int) $field['fid'];
    $rows[] = '<tr><td><span class="documents-admin-order">'
        . (int) $field['f_order'] . '</span><strong>'
        . htmlspecialchars(stripslashes((string) $field['f_name']), ENT_QUOTES, 'UTF-8')
        . '</strong><div class="documents-admin-muted">'
        . htmlspecialchars((string) $field['f_type'], ENT_QUOTES, 'UTF-8') . '</div></td><td>'
        . htmlspecialchars(stripslashes((string) $field['cat_name']), ENT_QUOTES, 'UTF-8') . '</td><td><span class="documents-admin-badge">{'
        . htmlspecialchars((string) $field['var_name'], ENT_QUOTES, 'UTF-8') . '}</span></td><td class="documents-admin-table__actions"><a href="'
        . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($text['edit'], ENT_QUOTES, 'UTF-8') . '</a></td></tr>';
}

if (empty($rows)) {
    $content .= '<p class="documents-admin-empty">'
        . htmlspecialchars($text['empty'], ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    $content .= '<section class="documents-admin-card"><div class="documents-admin-table-wrap"><table class="documents-admin-table">'
        . '<thead><tr><th>' . htmlspecialchars($text['field'], ENT_QUOTES, 'UTF-8') . '</th><th>'
        . htmlspecialchars($text['category'], ENT_QUOTES, 'UTF-8') . '</th><th>'
        . htmlspecialchars($text['variable'], ENT_QUOTES, 'UTF-8') . '</th><th class="documents-admin-table__actions">'
        . htmlspecialchars($text['actions'], ENT_QUOTES, 'UTF-8') . '</th></tr></thead><tbody>'
        . implode('', $rows) . '</tbody></table></div></section>';
}

$content .= '</main>';
$content = DOCUMENTS_wrapBlock($content, 'admin', 'list_fields');
$pageOptions = array('pagetitle' => $text['title']);
COM_output(COM_createHTMLDocument($content, $pageOptions));
