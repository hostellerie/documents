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

function DOCUMENTS_adminSectionTitle($active)
{
    global $_CONF;

    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
    $titles = array(
        '' => $isFrench ? 'Catégories' : 'Categories',
        'edit_cat' => $isFrench ? 'Formulaire de catégorie' : 'Category form',
        'list_fields' => $isFrench ? 'Liste des champs' : 'Fields list',
        'edit_field' => $isFrench ? 'Formulaire du champ' : 'Field form',
        'list_groups' => $isFrench ? 'Liste des groupes de choix' : 'Selection groups list',
        'edit_group' => $isFrench ? 'Formulaire du groupe' : 'Selection group form',
        'list_selects' => $isFrench ? 'Valeurs du groupe' : 'Selection values',
        'edit_select' => $isFrench ? 'Formulaire de la valeur' : 'Selection value form',
        'integrity' => $isFrench ? 'Contrôles d’intégrité' : 'Integrity checks'
    );

    return isset($titles[$active]) ? $titles[$active] : ($isFrench ? 'Gestion' : 'Management');
}

function DOCUMENTS_inferAdminSectionTitle($body, $active = '')
{
    global $_CONF;

    if ($active !== '') {
        return DOCUMENTS_adminSectionTitle($active);
    }

    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

    if (stripos($body, '<form') !== false) {
        return $isFrench ? 'Formulaire' : 'Form';
    }
    if (strpos($body, 'documents-admin-table') !== false) {
        return $isFrench ? 'Catégories' : 'Categories';
    }
    if (stripos($body, '<ul') !== false) {
        return $isFrench ? 'Contrôles d’intégrité' : 'Integrity checks';
    }

    return DOCUMENTS_adminSectionTitle($active);
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

function DOCUMENTS_wrapAdminStructure($content, $active = '')
{
    $content = (string) $content;
    $mainOpen = '<main class="documents-admin-page">';
    $start = strpos($content, $mainOpen);
    if ($start === false) {
        return '<div class="documents-shell documents-shell--admin">' . $content . '</div>';
    }

    $end = strrpos($content, '</main>');
    if ($end === false || $end <= $start) {
        return '<div class="documents-shell documents-shell--admin">' . $content . '</div>';
    }

    $innerStart = $start + strlen($mainOpen);
    $inner = substr($content, $innerStart, $end - $innerStart);

    $navEnd = strpos($inner, '</nav>');
    $headerEnd = strpos($inner, '</header>');
    if ($navEnd === false || $headerEnd === false || $headerEnd < $navEnd) {
        return '<div class="documents-shell documents-shell--admin">' . $content . '</div>';
    }

    $headerEnd += strlen('</header>');
    $top = substr($inner, 0, $headerEnd);
    $body = trim(substr($inner, $headerEnd));
    if ($body !== '' && strpos($body, 'block-center') === false) {
        $body = DOCUMENTS_sectionBlock(
            DOCUMENTS_inferAdminSectionTitle($body, $active),
            $body
        );
    }

    $rebuilt = $mainOpen . $top . $body . '</main>';

    return '<div class="documents-shell documents-shell--admin">'
        . substr($content, 0, $start) . $rebuilt . substr($content, $end + strlen('</main>'))
        . '</div>';
}

function DOCUMENTS_wrapBlock($content, $context = 'public', $active = '')
{
    $context = ($context === 'admin') ? 'admin' : 'public';
    $content = (string) $content;
    if (strpos($content, 'documents-shell--' . $context) !== false) {
        return $content;
    }

    if ($context === 'public') {
        $content = DOCUMENTS_wrapPublicFormSection($content);
        return '<div class="documents-shell documents-shell--public">'
            . $content . '</div>';
    }

    /* Admin pages follow the same semantic order as public pages:
     * plugin navigation, H1/introduction, then Geeklog H2 content blocks. */
    return DOCUMENTS_wrapAdminStructure($content, $active);
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

    $wrapped = DOCUMENTS_wrapBlock($fragment, 'admin', $active);

    return substr($page, 0, $start) . $wrapped . substr($page, $end);
}
