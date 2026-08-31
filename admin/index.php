<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | admin/index.php                                                           |
// |                                                                           |
// | Dedicated administration router and dashboard.                            |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'admin_styles.php';

DOCUMENTS_loadAdminStyles();

if (!SEC_hasRights('documents.admin')) {
    $username = isset($_USER['username']) ? $_USER['username'] : 'unknown';
    COM_accessLog('User ' . $username . ' tried to access Documents administration.');
    COM_output(COM_createHTMLDocument(
        COM_showMessageText($MESSAGE[29], $MESSAGE[30]),
        array('pagetitle' => $MESSAGE[30])
    ));
    exit;
}

$adminUrl = rtrim((string) $_CONF['site_admin_url'], '/') . '/plugins/documents';
$publicUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
$mode = isset($_REQUEST['mode']) && !is_array($_REQUEST['mode'])
    ? trim((string) $_REQUEST['mode']) : '';

$adminViews = array(
    'edit_cat' => 'category-editor.php',
    'list_fields' => 'admin-fields.php',
    'edit_field' => 'field-editor.php',
    'list_groups' => 'admin-groups.php',
    'edit_group' => 'group-editor.php',
    'list_selects' => 'admin-selects.php',
    'edit_select' => 'select-editor.php'
);

if (isset($adminViews[$mode])) {
    $publicDir = rtrim((string) $_DOCUMENTS_CONF['path_html'], "/\\") . DIRECTORY_SEPARATOR;
    $target = $publicDir . $adminViews[$mode];
    if (!is_file($target)) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }

    $_DOCUMENTS_CONF['site_url'] = $adminUrl;
    $oldDirectory = getcwd();
    @chdir($publicDir);

    ob_start();
    require $target;
    $renderedAdminPage = ob_get_clean();

    if ($oldDirectory !== false) {
        @chdir($oldDirectory);
    }

    /* The Geeklog page header/welcome area must stay before plugin controls.
     * Insert the shared Documents navigation inside the page's <main>, never
     * before it. Do not duplicate navigation in views that already render it. */
    if (is_string($renderedAdminPage) && $renderedAdminPage !== '') {
        if (strpos($renderedAdminPage, 'documents-admin-navigation') === false) {
            $mainPos = strpos($renderedAdminPage, '<main');
            if ($mainPos !== false) {
                $mainEnd = strpos($renderedAdminPage, '>', $mainPos);
                if ($mainEnd !== false) {
                    $insertPos = $mainEnd + 1;
                    $renderedAdminPage = substr($renderedAdminPage, 0, $insertPos)
                        . DOCUMENTS_adminNavigation($mode)
                        . substr($renderedAdminPage, $insertPos);
                }
            }
        }
        echo $renderedAdminPage;
    }
    exit;
}

$adminSaveModes = array('save_cat', 'save_field', 'save_group', 'save_select');
if (in_array($mode, $adminSaveModes, true)) {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
    if (!SEC_checkToken()) {
        if (function_exists('http_response_code')) {
            http_response_code(403);
        } else {
            header('HTTP/1.1 403 Forbidden');
        }
        exit;
    }

    $_DOCUMENTS_CONF['site_url'] = $adminUrl;
    require_once $pluginPath . 'admin_dispatch.php';
    list($ok, $returnUrl) = DOCUMENTS_adminDispatchMutation($mode, $_REQUEST);
    echo COM_refresh($returnUrl);
    exit;
}

$pluginName = isset($LANG_DOCUMENTS_1['plugin_name'])
    ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

if ($mode === 'integrity') {
    require_once $pluginPath . 'integrity.php';
    $report = DOCUMENTS_integrityReport();

    $checks = array(
        ($isFrench ? 'Slugs de catégories dupliqués' : 'Duplicate category slugs')
            => count($report['duplicate_category_slugs']),
        ($isFrench ? 'Slugs de documents dupliqués' : 'Duplicate document slugs')
            => count($report['duplicate_document_slugs']),
        ($isFrench ? 'Documents sans valeurs' : 'Documents without values')
            => (int) $report['orphan_documents_without_values'],
        ($isFrench ? 'Valeurs sans document' : 'Values without document')
            => (int) $report['orphan_values_without_document'],
        ($isFrench ? 'Valeurs sans champ' : 'Values without field')
            => (int) $report['orphan_values_without_field'],
        ($isFrench ? 'Champs sans catégorie' : 'Fields without category')
            => (int) $report['orphan_fields_without_category'],
        ($isFrench ? 'Images manquantes' : 'Missing images')
            => count($report['missing_image_files']),
        ($isFrench ? 'Images non référencées' : 'Unreferenced images')
            => count($report['unreferenced_image_files'])
    );

    $content = '<main class="documents-admin-page">'
        . DOCUMENTS_adminNavigation('integrity')
        . '<header class="documents-admin-page__header"><h1>'
        . htmlspecialchars($isFrench ? 'Intégrité des données' : 'Data integrity', ENT_QUOTES, 'UTF-8')
        . '</h1></header><section class="documents-admin-card"><div class="documents-admin-card__body"><ul>';
    foreach ($checks as $label => $count) {
        $content .= '<li>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' : <strong>'
            . (int) $count . '</strong></li>';
    }
    $content .= '</ul></div></section></main>';

    COM_output(COM_createHTMLDocument($content, array('pagetitle' => $pluginName)));
    exit;
}

$content = '<main class="documents-admin-page">'
    . DOCUMENTS_adminNavigation('')
    . '<header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($pluginName, ENT_QUOTES, 'UTF-8') . '</h1><p class="documents-admin-page__lead">'
    . htmlspecialchars(
        $isFrench
            ? 'Gérez ici la structure du plugin. Les pages publiques restent réservées à la consultation et à la contribution aux documents.'
            : 'Manage the plugin structure here. Public pages remain dedicated to reading and document contribution.',
        ENT_QUOTES,
        'UTF-8'
    ) . '</p></header>';

$content .= '<div class="documents-admin-toolbar">'
    . '<a class="documents-admin-button documents-admin-button--primary" href="'
    . htmlspecialchars($adminUrl . '/index.php?mode=edit_cat', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($isFrench ? 'Nouvelle catégorie' : 'New category', ENT_QUOTES, 'UTF-8') . '</a>'
    . '<a class="documents-admin-button" href="'
    . htmlspecialchars($publicUrl . '/', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($isFrench ? 'Voir les documents' : 'View documents', ENT_QUOTES, 'UTF-8') . '</a></div>';

$rows = array();
$result = DB_query(
    "SELECT c.cid, c.cat_name, c.cat_url, COUNT(f.fid) AS field_count "
    . "FROM {$_TABLES['documents_cat']} AS c "
    . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.cat_id=c.cid "
    . "GROUP BY c.cid, c.cat_name, c.cat_url "
    . "ORDER BY c.cat_order ASC, c.cat_name ASC"
);
while ($category = DB_fetchArray($result)) {
    if (!is_array($category) || empty($category['cid'])) {
        continue;
    }

    $cid = (int) $category['cid'];
    $slug = (string) $category['cat_url'];
    $name = htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8');
    $rows[] = '<tr><td><strong>' . $name . '</strong><div class="documents-admin-muted">/'
        . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '</div></td><td>'
        . (int) $category['field_count'] . '</td><td class="documents-admin-table__actions">'
        . '<a href="' . htmlspecialchars($adminUrl . '/index.php?mode=edit_cat&cid=' . $cid, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($isFrench ? 'Modifier' : 'Edit', ENT_QUOTES, 'UTF-8') . '</a> · '
        . '<a href="' . htmlspecialchars($adminUrl . '/index.php?mode=list_fields&cat=' . $cid, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($isFrench ? 'Champs' : 'Fields', ENT_QUOTES, 'UTF-8') . '</a>'
        . ($slug !== '' && (int) $category['field_count'] > 0
            ? ' · <a href="' . htmlspecialchars($publicUrl . '/index.php?mode=new&cat=' . rawurlencode($slug), ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($isFrench ? 'Créer un document' : 'Create document', ENT_QUOTES, 'UTF-8') . '</a>'
            : '')
        . '</td></tr>';
}

if (empty($rows)) {
    $content .= '<p class="documents-admin-empty">'
        . htmlspecialchars($isFrench ? 'Aucune catégorie.' : 'No categories.', ENT_QUOTES, 'UTF-8')
        . '</p>';
} else {
    $content .= '<section class="documents-admin-card"><div class="documents-admin-table-wrap"><table class="documents-admin-table">'
        . '<thead><tr><th>' . htmlspecialchars($isFrench ? 'Catégorie' : 'Category', ENT_QUOTES, 'UTF-8')
        . '</th><th>' . htmlspecialchars($isFrench ? 'Champs' : 'Fields', ENT_QUOTES, 'UTF-8')
        . '</th><th>' . htmlspecialchars($isFrench ? 'Actions' : 'Actions', ENT_QUOTES, 'UTF-8')
        . '</th></tr></thead><tbody>' . implode('', $rows) . '</tbody></table></div></section>';
}

$content .= '</main>';
COM_output(COM_createHTMLDocument($content, array('pagetitle' => $pluginName)));
