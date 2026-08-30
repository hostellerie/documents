<?php

/* Admin-only setup guide for persistent Documents templates and styles. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)
    || !SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'custom_assets.php';

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

$title = $isFrench ? 'Templates et CSS personnalisés de Documents' : 'Documents custom templates and CSS';
$content = '<main class="documents-presentation-help">';
$content .= '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';

if ($isFrench) {
    $content .= '<p>Ces fichiers sont volontairement conservés <strong>hors du répertoire du plugin</strong>. '
        . 'Une mise à jour ou un remplacement de Documents ne doit donc pas les écraser. Ils restent toutefois à inclure dans les sauvegardes du serveur.</p>';
    $content .= '<h2>Template personnalisé</h2>';
    $content .= '<p>Dans le champ <strong>Template</strong>, saisissez uniquement le nom du dossier, par exemple <code>fiche-canyon</code>. '
        . 'Créez ce dossier à l’emplacement suivant :</p>';
    $content .= '<pre>' . htmlspecialchars($templatesRoot . 'fiche-canyon' . DIRECTORY_SEPARATOR, ENT_QUOTES, 'UTF-8') . '</pre>';
    $content .= '<p>Le dossier doit contenir au minimum <code>document.thtml</code> et <code>doccomments.thtml</code>. '
        . '<code>scripts.thtml</code> est facultatif. Le template personnalise actuellement le rendu d’une fiche document de la catégorie.</p>';
    $content .= '<h2>CSS personnalisé</h2>';
    $content .= '<p>Dans le champ <strong>CSS</strong>, saisissez uniquement un nom de fichier se terminant par <code>.css</code>, '
        . 'par exemple <code>canyons.css</code>. Placez ce fichier ici :</p>';
    $content .= '<pre>' . htmlspecialchars($stylesRoot . 'canyons.css', ENT_QUOTES, 'UTF-8') . '</pre>';
    $content .= '<p>Le fichier reste hors de <code>public_html</code>. Documents le sert via un endpoint contrôlé et le charge sur les pages publiques de la catégorie.</p>';
    $content .= '<h2>Éléments détectés</h2>';
} else {
    $content .= '<p>These files are deliberately stored <strong>outside the plugin directory</strong>. '
        . 'Updating or replacing Documents should therefore not overwrite them. They must still be included in your server backups.</p>';
    $content .= '<h2>Custom template</h2>';
    $content .= '<p>In the <strong>Template</strong> field, enter only the folder name, for example <code>canyon-sheet</code>. '
        . 'Create that folder here:</p>';
    $content .= '<pre>' . htmlspecialchars($templatesRoot . 'canyon-sheet' . DIRECTORY_SEPARATOR, ENT_QUOTES, 'UTF-8') . '</pre>';
    $content .= '<p>The folder must contain at least <code>document.thtml</code> and <code>doccomments.thtml</code>. '
        . '<code>scripts.thtml</code> is optional. The custom template currently controls the individual document view for that category.</p>';
    $content .= '<h2>Custom CSS</h2>';
    $content .= '<p>In the <strong>CSS</strong> field, enter only a filename ending in <code>.css</code>, for example <code>canyons.css</code>. '
        . 'Place the file here:</p>';
    $content .= '<pre>' . htmlspecialchars($stylesRoot . 'canyons.css', ENT_QUOTES, 'UTF-8') . '</pre>';
    $content .= '<p>The source file remains outside <code>public_html</code>. Documents serves it through a controlled endpoint and loads it on public category pages.</p>';
    $content .= '<h2>Detected assets</h2>';
}

$content .= '<h3>' . ($isFrench ? 'Templates' : 'Templates') . '</h3><ul>';
if (empty($templateNames)) {
    $content .= '<li><em>' . ($isFrench ? 'Aucun template personnalisé détecté.' : 'No custom template detected.') . '</em></li>';
} else {
    foreach ($templateNames as $name) {
        $ready = DOCUMENTS_customTemplateIsReady($name);
        $content .= '<li><code>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</code> — '
            . ($ready ? ($isFrench ? 'prêt' : 'ready') : ($isFrench ? 'incomplet' : 'incomplete')) . '</li>';
    }
}
$content .= '</ul>';

$content .= '<h3>CSS</h3><ul>';
if (empty($styleNames)) {
    $content .= '<li><em>' . ($isFrench ? 'Aucun fichier CSS personnalisé détecté.' : 'No custom CSS file detected.') . '</em></li>';
} else {
    foreach ($styleNames as $name) {
        $content .= '<li><code>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</code></li>';
    }
}
$content .= '</ul>';

$content .= '<p><a href="' . htmlspecialchars(rtrim($_DOCUMENTS_CONF['site_url'], '/') . '/index.php?mode=edit_cat', ENT_QUOTES, 'UTF-8') . '">'
    . ($isFrench ? 'Retour à la création de catégorie' : 'Back to category creation') . '</a></p>';
$content .= '</main>';

$options = array('pagetitle' => $title);
COM_output(COM_createHTMLDocument($content, $options));
