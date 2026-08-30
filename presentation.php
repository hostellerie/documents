<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | presentation.php                                                          |
// |                                                                           |
// | Display formatting and lightweight presentation helpers.                  |
// +---------------------------------------------------------------------------+

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'presentation.php') !== false) {
    die('This file can not be used on its own.');
}

if (isset($_CONF['path'])) {
    $documentsCustomAssetsFile = $_CONF['path'] . 'plugins/documents/custom_assets.php';
    if (is_file($documentsCustomAssetsFile)) {
        require_once $documentsCustomAssetsFile;
    }
}

/**
 * Load the standard public Documents stylesheet in a way that works with
 * Geeklog 2.1.1 and 2.2.x. setCSSFile() expects a public_html-relative URI on
 * older Geeklog releases, not an absolute site URL.
 *
 * @return bool
 */
function DOCUMENTS_loadPublicStyles()
{
    global $_DOCUMENTS_CONF, $_SCRIPTS;

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

    return (bool) $_SCRIPTS->setCSSFile(
        'documents_public',
        '/' . $folder . '/css/documents.css?v=1.2.0-4'
    );
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
    global $_TABLES, $LANG_DOCUMENTS_1;

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

    $title = isset($LANG_DOCUMENTS_1['stats_title'])
        ? $LANG_DOCUMENTS_1['stats_title'] : 'Statistics';
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

/* Public presentation bootstrap. Runtime.php is loaded only by Documents
 * endpoints. SEO must run only on addressable public content surfaces, not on
 * edit/save/administration modes that happen to use the public entry point. */
$documentsPresentationScript = isset($_SERVER['SCRIPT_NAME'])
    ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
$documentsPresentationMode = isset($_REQUEST['mode']) ? trim((string) $_REQUEST['mode']) : '';
$documentsPresentationIsPublicIndex = $documentsPresentationScript !== ''
    && strpos($documentsPresentationScript, '/admin/') === false
    && (basename($documentsPresentationScript) === 'index.php'
        || basename($documentsPresentationScript) === 'category.php'
        || basename($documentsPresentationScript) === 'document.php');
$documentsPresentationIsSeoView = $documentsPresentationMode === ''
    || $documentsPresentationMode === 'view';

if ($documentsPresentationIsPublicIndex) {
    DOCUMENTS_loadPublicStyles();

    if (function_exists('DOCUMENTS_loadRequestedCategoryStyle')) {
        DOCUMENTS_loadRequestedCategoryStyle();
    }

    if ($documentsPresentationIsSeoView && isset($_CONF['path'])) {
        $documentsSeoFile = $_CONF['path'] . 'plugins/documents/seo.php';
        if (is_file($documentsSeoFile)) {
            require_once $documentsSeoFile;
            if (function_exists('DOCUMENTS_seoOutputFilter')
                && !defined('DOCUMENTS_SEO_BUFFER_STARTED')) {
                define('DOCUMENTS_SEO_BUFFER_STARTED', true);
                ob_start('DOCUMENTS_seoOutputFilter');
            }
        }
    }
}
