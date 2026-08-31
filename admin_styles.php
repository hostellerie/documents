<?php

/* Documents 1.2.0 admin stylesheet/navigation helpers. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'admin_styles.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_loadAdminStyles()
{
    global $_CONF, $_SCRIPTS;

    if (!isset($_SCRIPTS)
        || !is_object($_SCRIPTS)
        || !method_exists($_SCRIPTS, 'setCSSFile')) {
        return false;
    }

    /* Use absolute site URLs. Geeklog 2.1.1 and 2.2.x both treat these as
     * external CSS resources, avoiding their different local-path checks. */
    $base = rtrim((string) $_CONF['site_admin_url'], '/') . '/plugins/documents';

    $legacyLoaded = (bool) $_SCRIPTS->setCSSFile(
        'documents_admin_css',
        $base . '/documents.css'
    );
    $modernLoaded = (bool) $_SCRIPTS->setCSSFile(
        'documents_modern_admin_css',
        $base . '/modern-admin.css'
    );

    return $legacyLoaded && $modernLoaded;
}

function DOCUMENTS_adminNavigation($active = '')
{
    global $_CONF;

    $adminUrl = rtrim((string) $_CONF['site_admin_url'], '/') . '/plugins/documents/index.php';
    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

    $items = array(
        '' => $isFrench ? 'Administration' : 'Administration',
        'edit_cat' => $isFrench ? 'Nouvelle catégorie' : 'New category',
        'list_fields' => $isFrench ? 'Champs' : 'Fields',
        'list_groups' => $isFrench ? 'Groupes de choix' : 'Selection groups',
        'integrity' => $isFrench ? 'Intégrité' : 'Integrity'
    );

    $html = '<nav class="documents-admin-toolbar documents-admin-navigation" aria-label="Documents">';
    foreach ($items as $mode => $label) {
        $url = $adminUrl . ($mode === '' ? '' : '?mode=' . rawurlencode($mode));
        $class = 'documents-admin-button';
        if ((string) $active === (string) $mode) {
            $class .= ' documents-admin-button--primary';
        }
        $html .= '<a class="' . $class . '" href="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }
    $html .= '</nav>';

    return $html;
}
