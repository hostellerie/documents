<?php

/* Public Documents home page. Compatible with Geeklog 2.1.1/PHP 5.6+. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'rewrite.php';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'presentation.php';
DOCUMENTS_writeHtaccess(false);

$requestPath = isset($_SERVER['REQUEST_URI']) ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if (is_string($requestPath) && basename($requestPath) === 'home.php') {
    header('Location: ' . rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/', true, 301);
    exit;
}

DOCUMENTS_preparePublicPresentation(false);

$title = isset($LANG_DOCUMENTS_1['plugin_name']) ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
$content = DOCUMENTS_renderNavigation();
$content .= '<main class="documents-home">';
$content .= '<header class="documents-page-header documents-home__header"><h1>'
    . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
if (!empty($_DOCUMENTS_CONF['documents_main_header'])) {
    $content .= '<div class="documents-page-intro">' . (string) $_DOCUMENTS_CONF['documents_main_header'] . '</div>';
}
$content .= '</header>';

if (isset($_GET['msg']) && !is_array($_GET['msg'])) {
    $message = trim((string) $_GET['msg']);
    if ($message !== '') {
        $content .= '<div class="documents-message" role="status">'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

$sql = "SELECT c.cid, c.cat_name, c.cat_url, c.cat_help FROM {$_TABLES['documents_cat']} AS c WHERE c.list_index=1"
    . COM_getPermSQL('AND', 0, 2, 'c') . " ORDER BY c.cat_order ASC, c.cat_name ASC";
$result = DB_query($sql);
$cards = array();
while ($category = DB_fetchArray($result)) {
    if (!is_array($category) || empty($category['cat_url'])) {
        continue;
    }
    $categoryUrl = DOCUMENTS_interopCanonicalUrl($category['cat_url']);
    $categoryName = stripslashes((string) $category['cat_name']);
    $categoryHelp = trim(stripslashes((string) $category['cat_help']));
    $card = '<article class="documents-category-card"><a class="documents-category-card__link" href="'
        . htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8') . '"><span class="documents-category-card__title">'
        . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</span>';
    if ($categoryHelp !== '') {
        $card .= '<span class="documents-category-card__description">'
            . htmlspecialchars($categoryHelp, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    $card .= '<span class="documents-category-card__arrow" aria-hidden="true">›</span></a></article>';
    $cards[] = $card;
}
$categoryContent = empty($cards)
    ? '<p class="documents-empty">' . htmlspecialchars(isset($LANG_DOCUMENTS_1['none']) ? $LANG_DOCUMENTS_1['none'] : 'No documents.', ENT_QUOTES, 'UTF-8') . '</p>'
    : '<div class="documents-category-grid">' . implode('', $cards) . '</div>';
$content .= DOCUMENTS_sectionBlock(
    isset($LANG_DOCUMENTS_1['categories']) ? $LANG_DOCUMENTS_1['categories'] : 'Categories',
    $categoryContent
);

$stats = DOCUMENTS_homeStatsBlock();
if ($stats !== '') {
    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
    $statsTitle = $isFrench ? 'Statistiques' : 'Statistics';
    $content .= '<section class="documents-secondary-section documents-home-statistics">'
        . '<h2 class="documents-secondary-section__title">'
        . htmlspecialchars($statsTitle, ENT_QUOTES, 'UTF-8') . '</h2>'
        . $stats . '</section>';
}

if (!empty($_DOCUMENTS_CONF['documents_main_footer'])) {
    $moreTitle = isset($LANG_DOCUMENTS_1['more_information']) ? $LANG_DOCUMENTS_1['more_information'] : 'More information';
    $content .= '<section class="documents-secondary-section documents-page-footer">'
        . '<h2 class="documents-secondary-section__title">'
        . htmlspecialchars($moreTitle, ENT_QUOTES, 'UTF-8') . '</h2>'
        . (string) $_DOCUMENTS_CONF['documents_main_footer'] . '</section>';
}
$content .= '</main>';

COM_output(DOCUMENTS_createPublicPage($content, $title));
