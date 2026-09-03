from pathlib import Path


def replace_once(path, old, new):
    p = Path(path)
    text = p.read_text(encoding='utf-8')
    count = text.count(old)
    if count != 1:
        raise SystemExit('%s: expected one match, found %d' % (path, count))
    p.write_text(text.replace(old, new, 1), encoding='utf-8')


replace_once(
    'public_html/document.php',
    """$categoryName = isset($page['category_name'])
    ? trim((string) $page['category_name']) : '';
$categoryUrl = DOCUMENTS_interopCanonicalUrl(
    isset($page['category_slug']) ? (string) $page['category_slug'] : $categorySlug
);""",
    """$categoryName = isset($page['category_name'])
    ? trim((string) $page['category_name'])
    : (isset($page['category']['cat_name'])
        ? trim(stripslashes((string) $page['category']['cat_name'])) : '');
$categoryRouteSlug = isset($page['category_slug'])
    ? (string) $page['category_slug']
    : (isset($page['category']['cat_url'])
        ? (string) $page['category']['cat_url'] : $categorySlug);
$categoryUrl = DOCUMENTS_interopCanonicalUrl($categoryRouteSlug);"""
)

old_category = """$categoryName = stripslashes((string) $category['cat_name']);
$documentsLabel = isset($LANG_DOCUMENTS_1['plugin_name']) ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
$content = '<main class=\"documents-category\"><nav class=\"documents-breadcrumb\" aria-label=\"Breadcrumb\">'
    . '<a href=\"' . htmlspecialchars(rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/', ENT_QUOTES, 'UTF-8') . '\">'
    . htmlspecialchars($documentsLabel, ENT_QUOTES, 'UTF-8') . '</a>'
    . '<span class=\"documents-breadcrumb__separator\" aria-hidden=\"true\"> &gt; </span>'
    . '<span aria-current=\"page\">' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</span></nav>'
    . '<header class=\"documents-page-header\"><h1>' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</h1>';
if (!empty($category['cat_help'])) {
    $content .= '<p class=\"documents-page-description\">'
        . htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8') . '</p>';
}
$content .= '</header>';

if (!empty($category['custom_header'])) {
    $header = (string) $category['custom_header'];
    $content .= '<div class=\"documents-category-header\">'
        . (function_exists('PLG_replaceTags') ? PLG_replaceTags($header) : $header) . '</div>';
}
"""
new_category = """$categoryName = stripslashes((string) $category['cat_name']);
$documentsLabel = isset($LANG_DOCUMENTS_1['plugin_name']) ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
$customHeader = '';
if (!empty($category['custom_header'])) {
    $header = (string) $category['custom_header'];
    $customHeader = function_exists('PLG_replaceTags') ? PLG_replaceTags($header) : $header;
}
$customHeaderHasH1 = $customHeader !== '' && preg_match('/<h1\\b/i', $customHeader) === 1;

$content = '<main class=\"documents-category\"><nav class=\"documents-breadcrumb\" aria-label=\"Breadcrumb\">'
    . '<a href=\"' . htmlspecialchars(rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/', ENT_QUOTES, 'UTF-8') . '\">'
    . htmlspecialchars($documentsLabel, ENT_QUOTES, 'UTF-8') . '</a>'
    . '<span class=\"documents-breadcrumb__separator\" aria-hidden=\"true\"> &gt; </span>'
    . '<span aria-current=\"page\">' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</span></nav>'
    . '<header class=\"documents-page-header\">';
if (!$customHeaderHasH1) {
    $content .= '<h1>' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') . '</h1>';
}
if ($customHeader !== '') {
    $content .= '<div class=\"documents-category-header\">' . $customHeader . '</div>';
}
if (!empty($category['cat_help'])) {
    $content .= '<p class=\"documents-page-description\">'
        . htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8') . '</p>';
}
$content .= '</header>';
"""
replace_once('public_html/category-list.php', old_category, new_category)

replace_once(
    'public_html/home.php',
    """$stats = DOCUMENTS_homeStatsBlock();
if ($stats !== '') {
    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
    $statsTitle = $isFrench ? 'Statistiques' : 'Statistics';""",
    """$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$stats = DOCUMENTS_homeStatsBlock();
if ($stats !== '') {
    $statsTitle = $isFrench ? 'Statistiques' : 'Statistics';"""
)

replace_once(
    'public_html/home.php',
    """if (!empty($_DOCUMENTS_CONF['documents_main_footer'])) {
    $moreTitle = isset($LANG_DOCUMENTS_1['more_information']) ? $LANG_DOCUMENTS_1['more_information'] : 'More information';
    $mainFooter = PLG_replaceTags((string) $_DOCUMENTS_CONF['documents_main_footer']);
    $content .= '<section class=\"documents-secondary-section documents-page-footer\">'
        . '<h2 class=\"documents-secondary-section__title\">'
        . htmlspecialchars($moreTitle, ENT_QUOTES, 'UTF-8') . '</h2>'
        . $mainFooter . '</section>';
}""",
    """if (!empty($_DOCUMENTS_CONF['documents_main_footer'])) {
    $moreTitle = isset($LANG_DOCUMENTS_1['more_information'])
        ? $LANG_DOCUMENTS_1['more_information'] : ($isFrench ? 'En savoir plus' : 'More information');
    $mainFooter = PLG_replaceTags((string) $_DOCUMENTS_CONF['documents_main_footer']);
    $mainFooterHasH2 = $mainFooter !== '' && preg_match('/<h2\\b/i', $mainFooter) === 1;
    $content .= '<section class=\"documents-secondary-section documents-page-footer\">';
    if (!$mainFooterHasH2) {
        $content .= '<h2 class=\"documents-secondary-section__title\">'
            . htmlspecialchars($moreTitle, ENT_QUOTES, 'UTF-8') . '</h2>';
    }
    $content .= $mainFooter . '</section>';
}"""
)

for path, anchor, line in [
    ('language/english.php', "    'whatsnew_none'       => 'No recent documents.',\n", "    'more_information'    => 'More information',\n"),
    ('language/french_france_utf-8.php', "    'whatsnew_none'       => 'Aucun document récent.',\n", "    'more_information'    => 'En savoir plus',\n")
]:
    p = Path(path)
    text = p.read_text(encoding='utf-8')
    if "'more_information'" not in text:
        if text.count(anchor) != 1:
            raise SystemExit('%s: language anchor missing' % path)
        p.write_text(text.replace(anchor, anchor + line, 1), encoding='utf-8')

Path('tests/public_navigation_headings_test.php').write_text("""<?php

$document = file_get_contents(dirname(__DIR__) . '/public_html/document.php');
$category = file_get_contents(dirname(__DIR__) . '/public_html/category-list.php');
$home = file_get_contents(dirname(__DIR__) . '/public_html/home.php');
$french = file_get_contents(dirname(__DIR__) . '/language/french_france_utf-8.php');

$checks = array(
    array($document, "page['category']['cat_name']", 'document breadcrumb category fallback'),
    array($category, 'customHeaderHasH1', 'category custom H1 detection'),
    array($category, 'if (!$customHeaderHasH1)', 'category default H1 guard'),
    array($home, 'mainFooterHasH2', 'footer H2 detection'),
    array($home, 'if (!$mainFooterHasH2)', 'footer default H2 guard'),
    array($french, "'more_information'    => 'En savoir plus'", 'French more information label')
);

foreach ($checks as $check) {
    if (strpos($check[0], $check[1]) === false) {
        fwrite(STDERR, 'Missing ' . $check[2] . "\\n");
        exit(1);
    }
}

echo "Public navigation and heading guards are present.\\n";
""", encoding='utf-8')
