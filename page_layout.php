<?php

/* Shared Documents page layout helpers. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'page_layout.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_blockTitle()
{
    return 'Documents 1.2.0';
}

function DOCUMENTS_wrapBlock($content, $context = 'public')
{
    $context = ($context === 'admin') ? 'admin' : 'public';
    $content = (string) $content;
    if (strpos($content, 'documents-shell--' . $context) !== false) {
        return $content;
    }

    $content = '<div class="documents-shell documents-shell--' . $context . '">'
        . $content . '</div>';

    if (function_exists('COM_startBlock') && function_exists('COM_endBlock')) {
        return COM_startBlock(DOCUMENTS_blockTitle()) . $content . COM_endBlock();
    }

    return $content;
}

function DOCUMENTS_wrapRenderedAdminPage($page, $active = '')
{
    if (!is_string($page) || $page === '') {
        return $page;
    }
    if (strpos($page, 'documents-shell--admin') !== false) {
        return $page;
    }

    $mainOpen = '<main class="documents-admin-page">';
    $start = strpos($page, $mainOpen);
    if ($start === false) {
        return $page;
    }

    $end = strpos($page, '</main>', $start);
    if ($end === false) {
        return $page;
    }
    $end += strlen('</main>');

    $fragment = substr($page, $start, $end - $start);
    if (strpos($fragment, 'documents-admin-navigation') === false
        && function_exists('DOCUMENTS_adminNavigation')) {
        $insertPos = strlen($mainOpen);
        $fragment = substr($fragment, 0, $insertPos)
            . DOCUMENTS_adminNavigation($active)
            . substr($fragment, $insertPos);
    }

    $wrapped = DOCUMENTS_wrapBlock($fragment, 'admin');

    return substr($page, 0, $start) . $wrapped . substr($page, $end);
}
