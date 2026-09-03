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

function DOCUMENTS_homeCategoryPreviewFields($categoryId)
{
    global $_TABLES;

    $fields = array('title' => 0, 'image' => 0);
    $result = DB_query(
        "SELECT fid,f_type,var_name FROM {$_TABLES['documents_fields']} "
        . "WHERE cat_id=" . (int) $categoryId . " ORDER BY f_order ASC,fid ASC"
    );
    while ($field = DB_fetchArray($result)) {
        if (!is_array($field)) {
            continue;
        }
        $type = strtolower((string) $field['f_type']);
        $variable = strtolower(trim((string) $field['var_name']));
        if ($fields['title'] === 0
            && ($type === 'text' || $type === 'textarea')
            && $variable !== 'metadescription'
            && $variable !== 'schema_type'
        ) {
            $fields['title'] = (int) $field['fid'];
        }
        if ($fields['image'] === 0 && $type === 'image') {
            $fields['image'] = (int) $field['fid'];
        }
        if ($fields['title'] > 0 && $fields['image'] > 0) {
            break;
        }
    }

    return $fields;
}

function DOCUMENTS_homeRecentDocuments($category, $limit)
{
    global $_TABLES;

    $categoryId = isset($category['cid']) ? (int) $category['cid'] : 0;
    $categorySlug = isset($category['cat_url']) ? (string) $category['cat_url'] : '';
    $limit = max(1, min(6, (int) $limit));
    if ($categoryId <= 0 || $categorySlug === '') {
        return array();
    }

    $previewFields = DOCUMENTS_homeCategoryPreviewFields($categoryId);
    $sql = "SELECT d.doc_url,MAX(d.modified) AS modified,MAX(d.created) AS created,MAX(d.did) AS did "
        . "FROM {$_TABLES['documents_docs']} AS d WHERE d.active=1 "
        . "AND EXISTS (SELECT 1 FROM {$_TABLES['documents_values']} AS cv "
        . "INNER JOIN {$_TABLES['documents_fields']} AS cf ON cf.fid=cv.field_id "
        . "WHERE cv.doc_url=d.doc_url AND cf.cat_id={$categoryId})"
        . COM_getPermSQL('AND', 0, 2, 'd')
        . " GROUP BY d.doc_url "
        . "ORDER BY COALESCE(MAX(d.modified),MAX(d.created)) DESC,MAX(d.did) DESC "
        . "LIMIT {$limit}";

    $result = DB_query($sql);
    $documents = array();
    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['doc_url'])) {
            continue;
        }

        $documentSlug = (string) $row['doc_url'];
        $safeDocument = DB_escapeString($documentSlug);
        $title = '';
        if ($previewFields['title'] > 0) {
            $title = DB_getItem(
                $_TABLES['documents_values'],
                'v_value',
                "doc_url='{$safeDocument}' AND field_id=" . (int) $previewFields['title']
            );
        }
        $title = trim(stripslashes((string) $title));
        if ($title === '') {
            $title = str_replace(array('_', '-'), ' ', $documentSlug);
            $title = function_exists('MBYTE_ucfirst') ? MBYTE_ucfirst($title) : ucfirst($title);
        }

        $image = '';
        if ($previewFields['image'] > 0) {
            $image = DB_getItem(
                $_TABLES['documents_values'],
                'v_value',
                "doc_url='{$safeDocument}' AND field_id=" . (int) $previewFields['image']
            );
            $image = basename(trim((string) $image));
        }

        $documents[] = array(
            'title' => $title,
            'url' => DOCUMENTS_interopCanonicalUrl($categorySlug, $documentSlug),
            'image' => $image,
            'modified' => !empty($row['modified']) ? (string) $row['modified'] : (string) $row['created']
        );
    }

    return $documents;
}

function DOCUMENTS_homeSeoMetadata($headerHtml, $fallbackTitle)
{
    global $_CONF;

    $title = trim((string) $fallbackTitle);
    if ($headerHtml !== '' && preg_match('/<h1\b[^>]*>(.*?)<\/h1>/is', $headerHtml, $match)) {
        $candidate = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES, 'UTF-8'));
        if ($candidate !== '') {
            $title = $candidate;
        }
    }

    $descriptionHtml = preg_replace('/<h1\b[^>]*>.*?<\/h1>/is', ' ', (string) $headerHtml);
    $description = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($descriptionHtml), ENT_QUOTES, 'UTF-8')));
    if ($description === '') {
        $description = $title;
        if (!empty($_CONF['site_name']) && $_CONF['site_name'] !== $title) {
            $description .= ' - ' . $_CONF['site_name'];
        }
    }
    if (function_exists('DOCUMENTS_interopExcerpt')) {
        $description = DOCUMENTS_interopExcerpt($description, 160);
    } elseif (strlen($description) > 160) {
        $description = substr($description, 0, 157) . '...';
    }

    return array('title' => $title, 'description' => $description);
}

$requestPath = isset($_SERVER['REQUEST_URI']) ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if (is_string($requestPath) && basename($requestPath) === 'home.php') {
    header('Location: ' . rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/', true, 301);
    exit;
}

DOCUMENTS_preparePublicPresentation(false);

if (isset($_SCRIPTS) && is_object($_SCRIPTS) && method_exists($_SCRIPTS, 'setCSSFile')) {
    $folder = !empty($_DOCUMENTS_CONF['documents_folder'])
        ? trim((string) $_DOCUMENTS_CONF['documents_folder'], '/') : 'documents';
    if (strtolower(get_class($_SCRIPTS)) === 'scripts') {
        $_SCRIPTS->setCSSFile('documents_home', '/' . $folder . '/css/documents-home.css', false);
    } else {
        $_SCRIPTS->setCSSFile(
            'documents_home',
            rtrim((string) $_CONF['site_url'], '/') . '/' . rawurlencode($folder) . '/css/documents-home.css'
        );
    }
}

$title = isset($LANG_DOCUMENTS_1['plugin_name']) ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
$mainHeader = '';
if (!empty($_DOCUMENTS_CONF['documents_main_header'])) {
    $mainHeader = PLG_replaceTags((string) $_DOCUMENTS_CONF['documents_main_header']);
}
$mainHeaderHasH1 = $mainHeader !== '' && preg_match('/<h1\b/i', $mainHeader) === 1;
$homeSeo = DOCUMENTS_homeSeoMetadata($mainHeader, $title);
$DOCUMENTS_PAGE_META_OVERRIDE = array(
    'title' => $homeSeo['title'],
    'description' => $homeSeo['description'],
    'canonical' => rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/',
    'schema_type' => 'CollectionPage'
);
$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$recentLabel = isset($LANG_DOCUMENTS_1['whatsnew_title'])
    ? $LANG_DOCUMENTS_1['whatsnew_title'] : ($isFrench ? 'Documents récents' : 'Recent documents');
$allDocumentsLabel = isset($LANG_DOCUMENTS_1['see_all_docs'])
    ? $LANG_DOCUMENTS_1['see_all_docs'] : ($isFrench ? 'Voir tous les documents' : 'View all documents');

$content = '<main class="documents-home">';
$content .= '<header class="documents-page-header documents-home__header">';
if (!$mainHeaderHasH1) {
    $content .= '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
}
if ($mainHeader !== '') {
    $content .= '<div class="documents-page-intro">' . $mainHeader . '</div>';
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
    $recentDocuments = DOCUMENTS_homeRecentDocuments($category, 3);

    $card = '<article class="documents-category-card">'
        . '<header class="documents-category-card__header">'
        . '<a class="documents-category-card__heading-link" href="'
        . htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8') . '">'
        . '<span class="documents-category-card__title">'
        . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</span>'
        . '<span class="documents-category-card__arrow" aria-hidden="true">›</span></a>';
    if ($categoryHelp !== '') {
        $card .= '<p class="documents-category-card__description">'
            . htmlspecialchars($categoryHelp, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $card .= '</header>';

    if (!empty($recentDocuments)) {
        $card .= '<div class="documents-category-card__recent">'
            . '<span class="documents-category-card__recent-label">'
            . htmlspecialchars($recentLabel, ENT_QUOTES, 'UTF-8') . '</span>';
        foreach ($recentDocuments as $recent) {
            $card .= '<a class="documents-category-card__recent-item" href="'
                . htmlspecialchars($recent['url'], ENT_QUOTES, 'UTF-8') . '">';
            if ($recent['image'] !== '') {
                $imageUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
                    . '/image.php?src=' . rawurlencode($recent['image']) . '&w=120&h=90';
                $card .= '<span class="documents-category-card__thumb"><img src="'
                    . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="" width="80" height="60" loading="lazy"></span>';
            }
            $card .= '<span class="documents-category-card__recent-title">'
                . htmlspecialchars($recent['title'], ENT_QUOTES, 'UTF-8') . '</span></a>';
        }
        $card .= '</div>';
    }

    $card .= '<footer class="documents-category-card__footer"><a href="'
        . htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($allDocumentsLabel, ENT_QUOTES, 'UTF-8')
        . ' <span aria-hidden="true">→</span></a></footer></article>';
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
    $statsTitle = $isFrench ? 'Statistiques' : 'Statistics';
    $content .= '<section class="documents-secondary-section documents-home-statistics">'
        . '<h2 class="documents-secondary-section__title">'
        . htmlspecialchars($statsTitle, ENT_QUOTES, 'UTF-8') . '</h2>'
        . $stats . '</section>';
}

if (!empty($_DOCUMENTS_CONF['documents_main_footer'])) {
    $moreTitle = isset($LANG_DOCUMENTS_1['more_information'])
        ? $LANG_DOCUMENTS_1['more_information'] : ($isFrench ? 'En savoir plus' : 'More information');
    $mainFooter = PLG_replaceTags((string) $_DOCUMENTS_CONF['documents_main_footer']);
    $mainFooterHasH2 = $mainFooter !== '' && preg_match('/<h2\b/i', $mainFooter) === 1;
    $content .= '<section class="documents-secondary-section documents-page-footer">';
    if (!$mainFooterHasH2) {
        $content .= '<h2 class="documents-secondary-section__title">'
            . htmlspecialchars($moreTitle, ENT_QUOTES, 'UTF-8') . '</h2>';
    }
    $content .= $mainFooter . '</section>';
}
$content .= '</main>';

COM_output(DOCUMENTS_createPublicPage($content, $homeSeo['title']));
