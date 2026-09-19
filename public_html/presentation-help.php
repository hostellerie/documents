<?php

/* Admin-only setup guide for persistent Documents templates and styles. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)
    || !SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'custom_assets.php';
require_once $pluginPath . 'admin_styles.php';
DOCUMENTS_loadAdminStyles();
DOCUMENTS_ensureCustomAssetDirectories();

$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$templatesRoot = DOCUMENTS_customTemplatesRoot();
$stylesRoot = DOCUMENTS_customStylesRoot();

function DOCUMENTS_assetGuideDirectoryList($root, $directories)
{
    $items = array();
    if ($root === '' || !is_dir($root)) {
        return $items;
    }
    $entries = @scandir($root);
    if (!is_array($entries)) {
        return $items;
    }
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry === '' || $entry[0] === '.') {
            continue;
        }
        $path = $root . $entry;
        if ($directories && is_dir($path)) {
            $items[] = $entry;
        } elseif (!$directories && is_file($path) && substr(strtolower($entry), -4) === '.css') {
            $items[] = $entry;
        }
    }
    sort($items, SORT_NATURAL | SORT_FLAG_CASE);
    return $items;
}

$templateNames = DOCUMENTS_assetGuideDirectoryList($templatesRoot, true);
$styleNames = DOCUMENTS_assetGuideDirectoryList($stylesRoot, false);
$title = $isFrench ? 'Templates et CSS personnalisés' : 'Custom templates and CSS';

$content = '<main class="documents-admin-page">'
    . DOCUMENTS_adminNavigation('edit_cat')
    . '<header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1></header>';

$content .= '<section class="documents-admin-card"><div class="documents-admin-card__body">';
if ($isFrench) {
    $content .= '<p>Ces fichiers sont volontairement conservés <strong>hors du répertoire du plugin</strong>. Une mise à jour de Documents ne doit donc pas les écraser.</p>'
        . '<h2>Template personnalisé</h2><p>Dans le champ <strong>Template</strong>, saisissez uniquement le nom du dossier, par exemple <code>fiche-canyon</code>.</p>'
        . '<pre>' . htmlspecialchars($templatesRoot . 'fiche-canyon' . DIRECTORY_SEPARATOR, ENT_QUOTES, 'UTF-8') . '</pre>'
        . '<p>Le dossier doit contenir au minimum <code>document.thtml</code> et <code>doccomments.thtml</code>. <code>scripts.thtml</code> est facultatif.</p>'
        . '<h2>CSS personnalisé</h2><p>Dans le champ <strong>CSS</strong>, saisissez un nom de fichier se terminant par <code>.css</code>, par exemple <code>canyons.css</code>.</p>'
        . '<pre>' . htmlspecialchars($stylesRoot . 'canyons.css', ENT_QUOTES, 'UTF-8') . '</pre>';
} else {
    $content .= '<p>These files are deliberately stored <strong>outside the plugin directory</strong>, so a Documents update should not overwrite them.</p>'
        . '<h2>Custom template</h2><p>Enter only the template folder name, for example <code>canyon-sheet</code>.</p>'
        . '<pre>' . htmlspecialchars($templatesRoot . 'canyon-sheet' . DIRECTORY_SEPARATOR, ENT_QUOTES, 'UTF-8') . '</pre>'
        . '<p>The folder must contain at least <code>document.thtml</code> and <code>doccomments.thtml</code>. <code>scripts.thtml</code> is optional.</p>'
        . '<h2>Custom CSS</h2><p>Enter a filename ending in <code>.css</code>, for example <code>canyons.css</code>.</p>'
        . '<pre>' . htmlspecialchars($stylesRoot . 'canyons.css', ENT_QUOTES, 'UTF-8') . '</pre>';
}
$content .= '</div></section>';

$content .= '<section class="documents-admin-card"><div class="documents-admin-card__body"><h2>'
    . htmlspecialchars($isFrench ? 'Éléments détectés' : 'Detected assets', ENT_QUOTES, 'UTF-8')
    . '</h2><h3>Templates</h3><ul>';
if (empty($templateNames)) {
    $content .= '<li><em>' . ($isFrench ? 'Aucun template personnalisé détecté.' : 'No custom template detected.') . '</em></li>';
} else {
    foreach ($templateNames as $name) {
        $ready = DOCUMENTS_customTemplateIsReady($name);
        $content .= '<li><code>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</code> — '
            . ($ready ? ($isFrench ? 'prêt' : 'ready') : ($isFrench ? 'incomplet' : 'incomplete')) . '</li>';
    }
}
$content .= '</ul><h3>CSS</h3><ul>';
if (empty($styleNames)) {
    $content .= '<li><em>' . ($isFrench ? 'Aucun fichier CSS personnalisé détecté.' : 'No custom CSS file detected.') . '</em></li>';
} else {
    foreach ($styleNames as $name) {
        $content .= '<li><code>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</code></li>';
    }
}
$content .= '</ul></div></section></main>';
$content = DOCUMENTS_wrapBlock($content, 'admin');
COM_output(COM_createHTMLDocument($content, array('pagetitle' => $title)));
