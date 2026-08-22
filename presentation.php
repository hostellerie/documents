<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.10                                                   |
// +---------------------------------------------------------------------------+
// | presentation.php                                                          |
// |                                                                           |
// | Display formatting and lightweight presentation helpers.                  |
// +---------------------------------------------------------------------------+

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'presentation.php') !== false) {
    die('This file can not be used on its own.');
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

/**
 * Apply a text field's display convention without changing the stored value.
 * sel_id is unused by text fields. Values 1001-1004 are reserved for text
 * display modes so they survive Geeklog's historical numeric request filter.
 */
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

/**
 * Statistics visibility levels:
 * 0 = disabled
 * 1 = Documents administrators only
 * 2 = authenticated users and administrators
 * 3 = everyone, including anonymous visitors
 */
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
        ? $LANG_DOCUMENTS_1['stats_documents'] : 'Documents';
    $viewsLabel = isset($LANG_DOCUMENTS_1['stats_views'])
        ? $LANG_DOCUMENTS_1['stats_views'] : 'Views';

    $content = '<p>' . htmlspecialchars($documents, ENT_QUOTES, 'UTF-8') . ': <strong>'
        . COM_numberFormat($total) . '</strong><br' . XHTML . '>'
        . htmlspecialchars($viewsLabel, ENT_QUOTES, 'UTF-8') . ': <strong>'
        . COM_numberFormat($views) . '</strong></p>';

    return COM_startBlock($title) . $content . COM_endBlock();
}
