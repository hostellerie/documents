<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | presentation.php                                                          |
// |                                                                           |
// | Side-effect-free public presentation helpers.                             |
// +---------------------------------------------------------------------------+

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'presentation.php') !== false) {
    die('This file can not be used on its own.');
}

if (isset($_CONF['path'])) {
    $documentsCustomAssetsFile = $_CONF['path'] . 'plugins/documents/custom_assets.php';
    if (is_file($documentsCustomAssetsFile)) {
        require_once $documentsCustomAssetsFile;
    }
    $documentsLayoutFile = $_CONF['path'] . 'plugins/documents/page_layout.php';
    if (is_file($documentsLayoutFile)) {
        require_once $documentsLayoutFile;
    }
}

function DOCUMENTS_loadPublicStyles()
{
    global $_CONF, $_DOCUMENTS_CONF, $_SCRIPTS;

    if (!isset($_SCRIPTS) || !is_object($_SCRIPTS)
        || !method_exists($_SCRIPTS, 'setCSSFile')) {
        return false;
    }

    $folder = isset($_DOCUMENTS_CONF['documents_folder'])
        ? trim((string) $_DOCUMENTS_CONF['documents_folder'], '/')
        : 'documents';
    if ($folder === '') {
        $folder = 'documents';
    }

    if (strtolower(get_class($_SCRIPTS)) === 'scripts') {
        return (bool) $_SCRIPTS->setCSSFile(
            'documents_public',
            '/' . $folder . '/css/documents.css',
            false
        );
    }

    $url = rtrim((string) $_CONF['site_url'], '/')
        . '/' . rawurlencode($folder) . '/css/documents.css';

    return (bool) $_SCRIPTS->setCSSFile('documents_public', $url);
}

function DOCUMENTS_preparePublicPresentation($loadCategoryStyle = true)
{
    DOCUMENTS_loadPublicStyles();

    if ($loadCategoryStyle && function_exists('DOCUMENTS_loadRequestedCategoryStyle')) {
        DOCUMENTS_loadRequestedCategoryStyle();
    }
}

function DOCUMENTS_applyPageMetaOverride($html, $meta)
{
    global $_CONF;

    if (!is_string($html) || $html === '' || !is_array($meta) || empty($meta['title'])) {
        return $html;
    }

    $title = (string) $meta['title'];
    $description = isset($meta['description']) ? (string) $meta['description'] : '';
    $canonical = isset($meta['canonical']) ? (string) $meta['canonical'] : '';
    $schemaType = isset($meta['schema_type']) ? (string) $meta['schema_type'] : 'CollectionPage';
    $escape = function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    $html = preg_replace('/<title>.*?<\/title>/is', '<title>' . $escape($title) . '</title>', $html, 1);

    $replacements = array(
        'description' => $description,
        'og:title' => $title,
        'og:description' => $description,
        'og:url' => $canonical,
        'twitter:title' => $title,
        'twitter:description' => $description
    );
    foreach ($replacements as $name => $value) {
        if ($value === '') {
            continue;
        }
        $quoted = preg_quote($name, '/');
        $pattern = '/<meta\s+(?:name|property)=["\']' . $quoted . '["\'][^>]*>/i';
        $attribute = strpos($name, 'og:') === 0 ? 'property' : 'name';
        $tag = '<meta ' . $attribute . '="' . $escape($name) . '" content="' . $escape($value) . '" />';
        if (preg_match($pattern, $html)) {
            $html = preg_replace($pattern, $tag, $html, 1);
        } elseif (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $tag . "\n</head>", $html, 1);
        }
    }

    if ($canonical !== '') {
        $canonicalTag = '<link rel="canonical" href="' . $escape($canonical) . '" />';
        if (preg_match('/<link\s+rel=["\']canonical["\'][^>]*>/i', $html)) {
            $html = preg_replace('/<link\s+rel=["\']canonical["\'][^>]*>/i', $canonicalTag, $html, 1);
        } elseif (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $canonicalTag . "\n</head>", $html, 1);
        }
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => $schemaType,
                'name' => $title,
                'description' => $description,
                'url' => $canonical,
                'isPartOf' => array(
                    '@type' => 'WebSite',
                    'name' => isset($_CONF['site_name']) ? (string) $_CONF['site_name'] : '',
                    'url' => isset($_CONF['site_url']) ? (string) $_CONF['site_url'] : ''
                )
            )
        )
    );
    $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json !== false) {
        $script = '<script type="application/ld+json">' . $json . '</script>';
        if (preg_match('/<script\s+type=["\']application\/ld\+json["\']>.*?<\/script>/is', $html)) {
            $html = preg_replace('/<script\s+type=["\']application\/ld\+json["\']>.*?<\/script>/is', $script, $html, 1);
        } elseif (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $script . "\n</head>", $html, 1);
        }
    }

    return $html;
}

function DOCUMENTS_createPublicPage($content, $title)
{
    global $_CONF, $DOCUMENTS_PAGE_META_OVERRIDE;

    if (function_exists('DOCUMENTS_wrapBlock')) {
        $content = DOCUMENTS_wrapBlock($content, 'public');
    }

    $page = COM_createHTMLDocument(
        $content,
        array('pagetitle' => (string) $title)
    );

    if (isset($_CONF['path'])) {
        $seoFile = $_CONF['path'] . 'plugins/documents/seo.php';
        if (is_file($seoFile)) {
            require_once $seoFile;
        }
    }

    if (function_exists('DOCUMENTS_seoOutputFilter')) {
        $filtered = DOCUMENTS_seoOutputFilter($page);
        if (is_string($filtered) && $filtered !== '') {
            $page = $filtered;
        }
    }

    if (isset($DOCUMENTS_PAGE_META_OVERRIDE) && is_array($DOCUMENTS_PAGE_META_OVERRIDE)) {
        $page = DOCUMENTS_applyPageMetaOverride($page, $DOCUMENTS_PAGE_META_OVERRIDE);
    }

    return $page;
}

function DOCUMENTS_stringLower($value)
{
    return function_exists('mb_strtolower')
        ? mb_strtolower((string) $value, 'UTF-8')
        : strtolower((string) $value);
}

function DOCUMENTS_stringUpper($value)
{
    return function_exists('mb_strtoupper')
        ? mb_strtoupper((string) $value, 'UTF-8')
        : strtoupper((string) $value);
}

function DOCUMENTS_stringUcfirst($value)
{
    $value = DOCUMENTS_stringLower(trim((string) $value));
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return DOCUMENTS_stringUpper(mb_substr($value, 0, 1, 'UTF-8'))
            . mb_substr($value, 1, null, 'UTF-8');
    }

    return ucfirst($value);
}

function DOCUMENTS_stringUcwords($value)
{
    $value = DOCUMENTS_stringLower(trim((string) $value));
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_convert_case') && defined('MB_CASE_TITLE')) {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    return ucwords($value);
}

function DOCUMENTS_formatTextDisplay($value, $formatCode)
{
    switch ((int) $formatCode) {
        case 1001:
            return DOCUMENTS_stringLower($value);
        case 1002:
            return DOCUMENTS_stringUpper($value);
        case 1003:
            return DOCUMENTS_stringUcfirst($value);
        case 1004:
            return DOCUMENTS_stringUcwords($value);
        default:
            return (string) $value;
    }
}

function DOCUMENTS_textFormatOptions($selected, $labels)
{
    $options = array(
        0 => isset($labels['raw']) ? $labels['raw'] : 'As entered',
        1001 => isset($labels['lower']) ? $labels['lower'] : 'lowercase',
        1002 => isset($labels['upper']) ? $labels['upper'] : 'UPPERCASE',
        1003 => isset($labels['sentence']) ? $labels['sentence'] : 'First letter uppercase',
        1004 => isset($labels['title']) ? $labels['title'] : 'Each Word Capitalized'
    );

    $html = '<select name="sel_id" id="documents_text_format">';
    foreach ($options as $value => $label) {
        $isSelected = ((int) $selected === (int) $value) ? ' selected="selected"' : '';
        $html .= '<option value="' . (int) $value . '"' . $isSelected . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $html .= '</select>';

    return $html;
}

function DOCUMENTS_canShowStats()
{
    global $_DOCUMENTS_CONF;

    $visibility = isset($_DOCUMENTS_CONF['stats_visibility'])
        ? (int) $_DOCUMENTS_CONF['stats_visibility'] : 1;

    if ($visibility <= 0) {
        return false;
    }
    if (SEC_hasRights('documents.admin')) {
        return true;
    }
    if ($visibility >= 3) {
        return true;
    }

    return $visibility >= 2 && !COM_isAnonUser();
}

function DOCUMENTS_homeStatsBlock()
{
    global $_CONF, $_TABLES, $LANG_DOCUMENTS_1;

    if (!DOCUMENTS_canShowStats()) {
        return '';
    }

    $sql = "SELECT COUNT(*) AS total, COALESCE(SUM(hits),0) AS views "
        . "FROM {$_TABLES['documents_docs']} AS d WHERE d.active=1"
        . COM_getPermSQL('AND', 0, 2, 'd');
    $result = DB_query($sql);
    $row = DB_fetchArray($result);
    $total = is_array($row) && isset($row['total']) ? (int) $row['total'] : 0;
    $views = is_array($row) && isset($row['views']) ? (int) $row['views'] : 0;

    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
    $title = $isFrench ? 'Statistiques' : 'Statistics';
    $documents = isset($LANG_DOCUMENTS_1['stats_documents'])
        ? $LANG_DOCUMENTS_1['stats_documents'] : 'Published documents';
    $viewsLabel = isset($LANG_DOCUMENTS_1['stats_views'])
        ? $LANG_DOCUMENTS_1['stats_views'] : 'Views';

    return '<section class="documents-home__stats" aria-label="'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
        . '<div class="documents-stat"><strong class="documents-stat__value">'
        . COM_numberFormat($total) . '</strong><span class="documents-stat__label">'
        . htmlspecialchars($documents, ENT_QUOTES, 'UTF-8') . '</span></div>'
        . '<div class="documents-stat"><strong class="documents-stat__value">'
        . COM_numberFormat($views) . '</strong><span class="documents-stat__label">'
        . htmlspecialchars($viewsLabel, ENT_QUOTES, 'UTF-8') . '</span></div>'
        . '</section>';
}
