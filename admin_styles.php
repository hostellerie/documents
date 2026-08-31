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

    /* Geeklog 2.1.1 scripts::setCSSFile() expects a local path relative to
     * public_html and verifies that the file exists. Geeklog 2.2.x accepts the
     * absolute site URL used by its Resource class. */
    $legacyScripts = strtolower(get_class($_SCRIPTS)) === 'scripts';

    if ($legacyScripts) {
        $legacyLoaded = (bool) $_SCRIPTS->setCSSFile(
            'documents_admin_css',
            '/admin/plugins/documents/documents.css',
            false
        );
        $modernLoaded = (bool) $_SCRIPTS->setCSSFile(
            'documents_modern_admin_css',
            '/admin/plugins/documents/modern-admin.css',
            false
        );
    } else {
        $base = rtrim((string) $_CONF['site_admin_url'], '/') . '/plugins/documents';
        $legacyLoaded = (bool) $_SCRIPTS->setCSSFile(
            'documents_admin_css',
            $base . '/documents.css'
        );
        $modernLoaded = (bool) $_SCRIPTS->setCSSFile(
            'documents_modern_admin_css',
            $base . '/modern-admin.css'
        );
    }

    return $legacyLoaded && $modernLoaded;
}

function DOCUMENTS_adminNavigation($active = '')
{
    global $_CONF;

    $adminUrl = rtrim((string) $_CONF['site_admin_url'], '/') . '/plugins/documents/index.php';
    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

    $items = array(
        '' => 'Administration',
        'edit_cat' => $isFrench ? 'Nouvelle catégorie' : 'New category',
        'list_fields' => $isFrench ? 'Champs' : 'Fields',
        'list_groups' => $isFrench ? 'Groupes de choix' : 'Selection groups',
        'integrity' => $isFrench ? 'Intégrité' : 'Integrity'
    );

    $links = array();
    foreach ($items as $mode => $label) {
        $url = $adminUrl . ($mode === '' ? '' : '?mode=' . rawurlencode($mode));
        $current = ((string) $active === (string) $mode)
            ? ' aria-current="page"' : '';
        $links[] = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
            . $current . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    return '<div class="user_menu documents-admin-navigation" role="navigation" aria-label="Documents">'
        . implode(' | ', $links) . '</div>';
}

function DOCUMENTS_adminConfigurationForm($buttonClass = 'documents-admin-button')
{
    global $_CONF;

    $configUrl = rtrim((string) $_CONF['site_admin_url'], '/') . '/configuration.php';
    $class = trim((string) $buttonClass);
    $classAttribute = $class === '' ? '' : ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';

    return '<form method="post" action="' . htmlspecialchars($configUrl, ENT_QUOTES, 'UTF-8') . '">'
        . '<input type="hidden" name="conf_group" value="documents">'
        . '<button' . $classAttribute . ' type="submit">Configuration</button>'
        . '</form>';
}
