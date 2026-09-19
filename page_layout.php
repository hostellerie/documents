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

function DOCUMENTS_adminSectionTitle($active)
{
    global $_CONF;

    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
    $titles = array(
        '' => $isFrench ? 'Catégories' : 'Categories',
        'dashboard' => $isFrench ? 'Catégories' : 'Categories',
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

function DOCUMENTS_wrapAdminStructure($content, $active)
{
    $content = (string) $content;
    $mainOpen = '<main class="documents-admin-page">';
    $start = strpos($content, $mainOpen);
    $end = strrpos($content, '</main>');

    if ($start === false || $end === false || $end <= $start) {
        return '<div class="documents-shell documents-shell--admin">' . $content . '</div>';
    }

    $innerStart = $start + strlen($mainOpen);
    $inner = substr($content, $innerStart, $end - $innerStart);
    $headerEnd = strpos($inner, '</header>');
    if ($headerEnd === false) {
        return '<div class="documents-shell documents-shell--admin">' . $content . '</div>';
    }

    $headerEnd += strlen('</header>');
    $top = substr($inner, 0, $headerEnd);
    $body = trim(substr($inner, $headerEnd));
    if ($body !== '' && strpos($body, 'block-center') === false) {
        $body = DOCUMENTS_sectionBlock(DOCUMENTS_adminSectionTitle($active), $body);
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

    return DOCUMENTS_wrapAdminStructure($content, $active);
}
