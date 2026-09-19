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

    $items = array(
        array(
            'url' => $siteUrl . '/',
            'label' => $isFrench ? 'Tous les documents' : 'All documents'
        )
    );

    if (SEC_hasRights('documents.admin')) {
        $items[] = array(
            'url' => rtrim((string) $_CONF['site_admin_url'], '/')
                . '/plugins/documents/index.php',
            'label' => $isFrench ? 'Administration' : 'Admin'
        );
    }

    $links = array();
    foreach ($items as $item) {
        $links[] = '<a href="'
            . htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') . '</a>';
    }

    return '<div class="user_menu documents-navigation" role="navigation" aria-label="Documents">'
        . implode(' | ', $links) . '</div>';
}
