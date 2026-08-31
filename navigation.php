<?php

/* Shared public Documents navigation. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'navigation.php') !== false) {
    die('This file can not be used on its own.');
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
        . htmlspecialchars($isFrench ? 'Accueil des documents' : 'Documents home', ENT_QUOTES, 'UTF-8')
        . '</a></div>';

    if (SEC_hasRights('documents.admin')) {
        $adminUrl = rtrim((string) $_CONF['site_admin_url'], '/')
            . '/plugins/documents/index.php';
        $html .= '<div class="documents-navigation__admin">'
            . '<a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars('Administration', ENT_QUOTES, 'UTF-8')
            . '</a></div>';
    }

    return $html . '</nav>';
}
