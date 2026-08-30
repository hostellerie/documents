<?php

/* Shared Documents navigation for public and administration pages. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'navigation.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_navigationCategories()
{
    global $_TABLES;

    $items = array();
    if (empty($_TABLES['documents_cat'])) {
        return $items;
    }

    $result = DB_query(
        "SELECT cid, cat_name, cat_url, owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
        . "FROM {$_TABLES['documents_cat']} WHERE list_index=1 ORDER BY cat_order ASC, cat_name ASC"
    );

    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['cat_url'])) {
            continue;
        }
        $access = SEC_hasAccess(
            (int) $row['owner_id'],
            (int) $row['group_id'],
            (int) $row['perm_owner'],
            (int) $row['perm_group'],
            (int) $row['perm_members'],
            (int) $row['perm_anon']
        );
        if ($access < 2) {
            continue;
        }
        $items[] = $row;
    }

    return $items;
}

function DOCUMENTS_renderNavigation()
{
    global $_CONF, $_DOCUMENTS_CONF;

    $siteUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
    $adminUrl = isset($_CONF['site_admin_url'])
        ? rtrim((string) $_CONF['site_admin_url'], '/') . '/plugins/documents/index.php'
        : $siteUrl . '/';
    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

    $homeLabel = $isFrench ? 'Accueil' : 'Home';
    $adminLabel = $isFrench ? 'Administration' : 'Administration';
    $adminHomeLabel = $isFrench ? 'Admin' : 'Admin';
    $newCategoryLabel = $isFrench ? 'Nouvelle catégorie' : 'New category';
    $fieldsLabel = $isFrench ? 'Champs' : 'Fields';
    $groupsLabel = $isFrench ? 'Groupes de choix' : 'Selection groups';

    $html = '<nav class="documents-navigation" aria-label="Documents">'
        . '<div class="documents-navigation__main">'
        . '<a class="documents-navigation__home" href="'
        . htmlspecialchars($siteUrl . '/', ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($homeLabel, ENT_QUOTES, 'UTF-8') . '</a>';

    foreach (DOCUMENTS_navigationCategories() as $category) {
        $url = $siteUrl . '/' . rawurlencode((string) $category['cat_url']);
        $html .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8') . '</a>';
    }
    $html .= '</div>';

    if (SEC_hasRights('documents.admin')) {
        $html .= '<div class="documents-navigation__admin"><span class="documents-navigation__label">'
            . htmlspecialchars($adminLabel, ENT_QUOTES, 'UTF-8') . '</span>'
            . '<a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($adminHomeLabel, ENT_QUOTES, 'UTF-8') . '</a>'
            . '<a href="' . htmlspecialchars($siteUrl . '/index.php?mode=edit_cat', ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($newCategoryLabel, ENT_QUOTES, 'UTF-8') . '</a>'
            . '<a href="' . htmlspecialchars($siteUrl . '/index.php?mode=list_fields', ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($fieldsLabel, ENT_QUOTES, 'UTF-8') . '</a>'
            . '<a href="' . htmlspecialchars($siteUrl . '/index.php?mode=list_groups', ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($groupsLabel, ENT_QUOTES, 'UTF-8') . '</a></div>';
    }

    return $html . '</nav>';
}

function DOCUMENTS_navigationOutputFilter($html)
{
    if (!is_string($html) || $html === '' || strpos($html, 'documents-navigation') !== false) {
        return $html;
    }

    $navigation = DOCUMENTS_renderNavigation();
    if ($navigation === '') {
        return $html;
    }

    $patterns = array(
        '<main class="documents-',
        '<div class="user_menu">'
    );
    foreach ($patterns as $needle) {
        $pos = strpos($html, $needle);
        if ($pos !== false) {
            return substr($html, 0, $pos) . $navigation . substr($html, $pos);
        }
    }

    return $html;
}

function DOCUMENTS_startNavigationBuffer()
{
    if (defined('DOCUMENTS_NAVIGATION_BUFFER_STARTED')) {
        return;
    }

    $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
    $excluded = array('image.php', 'style.php', 'admin-save.php', 'admin-field-save.php', 'document-save.php');
    if (in_array($script, $excluded, true)) {
        return;
    }

    define('DOCUMENTS_NAVIGATION_BUFFER_STARTED', true);
    if (function_exists('DOCUMENTS_loadPublicStyles')) {
        DOCUMENTS_loadPublicStyles();
    }
    ob_start('DOCUMENTS_navigationOutputFilter');
}
