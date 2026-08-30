<?php

/* Shared public Documents navigation. PHP 5.6+. */

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
        if ($access >= 2) {
            $items[] = $row;
        }
    }

    return $items;
}

function DOCUMENTS_renderNavigation()
{
    global $_CONF, $_DOCUMENTS_CONF;

    $siteUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

    $html = '<nav class="documents-navigation" aria-label="Documents">'
        . '<div class="documents-navigation__main">'
        . '<a class="documents-navigation__home" href="'
        . htmlspecialchars($siteUrl . '/', ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($isFrench ? 'Accueil' : 'Home', ENT_QUOTES, 'UTF-8') . '</a>';

    foreach (DOCUMENTS_navigationCategories() as $category) {
        $url = $siteUrl . '/' . rawurlencode((string) $category['cat_url']);
        $html .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8')
            . '</a>';
    }

    $html .= '</div>';

    /* Administration is a separate application surface. Public navigation
     * exposes at most one doorway to it instead of mixing structural actions
     * (categories, fields, groups) with reader navigation. */
    if (SEC_hasRights('documents.admin')) {
        $adminUrl = rtrim((string) $_CONF['site_admin_url'], '/')
            . '/plugins/documents/index.php';
        $html .= '<div class="documents-navigation__admin">'
            . '<a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($isFrench ? 'Administration' : 'Administration', ENT_QUOTES, 'UTF-8')
            . '</a></div>';
    }

    return $html . '</nav>';
}
