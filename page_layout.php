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

function DOCUMENTS_adminPageTitle($active)
{
    global $_CONF;

    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
    $titles = array(
        '' => $isFrench ? 'Administration des documents' : 'Documents administration',
        'edit_cat' => $isFrench ? 'Catégorie' : 'Category',
        'list_fields' => $isFrench ? 'Champs' : 'Fields',
        'edit_field' => $isFrench ? 'Modifier un champ' : 'Edit field',
        'list_groups' => $isFrench ? 'Groupes de choix' : 'Selection groups',
        'edit_group' => $isFrench ? 'Modifier un groupe de choix' : 'Edit selection group',
        'list_selects' => $isFrench ? 'Valeurs du groupe' : 'Selection values',
        'edit_select' => $isFrench ? 'Modifier une valeur' : 'Edit selection value',
        'integrity' => $isFrench ? 'Intégrité des données' : 'Data integrity'
    );

    return isset($titles[$active]) ? $titles[$active] : 'Documents';
}

function DOCUMENTS_sectionBlock($title, $content)
{
    if (function_exists('COM_startBlock') && function_exists('COM_endBlock')) {
        return COM_startBlock((string) $title) . (string) $content . COM_endBlock();
    }

    return '<section class="documents-section"><h2>'
        . htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') . '</h2>'
        . (string) $content . '</section>';
}

function DOCUMENTS_wrapPublicFormSection($content)
{
    global $_CONF;

    $open = '<section class="documents-form-card">';
    $start = strpos($content, $open);
    if ($start === false) {
        return $content;
    }

    $endMarker = '</section></main>';
    $end = strrpos($content, $endMarker);
    if ($end === false || $end <= $start) {
        return $content;
    }

    $innerStart = $start + strlen($open);
    $inner = substr($content, $innerStart, $end - $innerStart);
    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
    $block = DOCUMENTS_sectionBlock($isFrench ? 'Formulaire' : 'Form', $inner);

    return substr($content, 0, $start) . $block . substr($content, $end + strlen('</section>'));
}

function DOCUMENTS_wrapBlock($content, $context = 'public')
{
    $context = ($context === 'admin') ? 'admin' : 'public';
    $content = (string) $content;
    if (strpos($content, 'documents-shell--' . $context) !== false) {
        return $content;
    }

    if ($context === 'public') {
        $content = DOCUMENTS_wrapPublicFormSection($content);
    }

    $content = '<div class="documents-shell documents-shell--' . $context . '">'
        . $content . '</div>';

    /* Public pages keep navigation, H1 and introduction outside Geeklog blocks.
     * Only their content sections use COM_startBlock(), yielding semantic H2s. */
    if ($context === 'public') {
        return $content;
    }

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
    $insertPos = strlen($mainOpen);
    $prefix = '';
    if (strpos($fragment, 'documents-admin-navigation') === false
        && function_exists('DOCUMENTS_adminNavigation')) {
        $prefix .= DOCUMENTS_adminNavigation($active);
    }
    if (stripos($fragment, '<h1') === false) {
        $prefix .= '<header class="documents-admin-page__header"><h1>'
            . htmlspecialchars(DOCUMENTS_adminPageTitle($active), ENT_QUOTES, 'UTF-8')
            . '</h1></header>';
    }
    if ($prefix !== '') {
        $fragment = substr($fragment, 0, $insertPos)
            . $prefix . substr($fragment, $insertPos);
    }

    $wrapped = DOCUMENTS_wrapBlock($fragment, 'admin');

    return substr($page, 0, $start) . $wrapped . substr($page, $end);
}
