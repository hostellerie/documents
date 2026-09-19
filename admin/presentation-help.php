<?php

/* Documents 1.2.0 template and CSS presentation guide. PHP 5.6+. */

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'custom_assets.php';
require_once $pluginPath . 'admin_styles.php';
require_once $pluginPath . 'page_layout.php';

DOCUMENTS_loadAdminStyles();

if (!SEC_hasRights('documents.admin')) {
    $username = isset($_USER['username']) ? $_USER['username'] : 'unknown';
    COM_accessLog('User ' . $username . ' tried to access Documents presentation guide.');
    COM_output(COM_createHTMLDocument(
        COM_showMessageText($MESSAGE[29], $MESSAGE[30]),
        array('pagetitle' => $MESSAGE[30])
    ));
    exit;
}

$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$adminUrl = rtrim((string) $_CONF['site_admin_url'], '/') . '/plugins/documents';
$templatesRoot = function_exists('DOCUMENTS_customTemplatesRoot')
    ? DOCUMENTS_customTemplatesRoot() : '';
$stylesRoot = function_exists('DOCUMENTS_customStylesRoot')
    ? DOCUMENTS_customStylesRoot() : '';

if ($isFrench) {
    $pageTitle = 'Guide des templates et CSS';
    $intro = 'Les templates et feuilles de style personnalisés permettent d’adapter le rendu public d’une catégorie sans modifier les fichiers du plugin. Les personnalisations sont stockées hors du dossier du plugin afin de résister aux mises à jour.';
    $templateTitle = 'Template personnalisé';
    $templateText = 'Dans le champ Template de la catégorie, saisissez uniquement le nom du dossier du template, sans chemin. Le dossier doit contenir au minimum document.thtml et doccomments.thtml. scripts.thtml est facultatif.';
    $cssTitle = 'Feuille de style CSS personnalisée';
    $cssText = 'Dans le champ CSS de la catégorie, saisissez uniquement le nom du fichier .css. Le fichier est servi par Documents depuis son stockage privé ; aucun chemin complet ni URL n’est accepté.';
    $pathsTitle = 'Emplacements de stockage';
    $securityTitle = 'Règles de sécurité';
    $securityItems = array(
        'N’utilisez jamais .. ni de chemin absolu.',
        'Le nom du template accepte uniquement des caractères sûrs.',
        'Le CSS doit être un nom de fichier se terminant par .css.',
        'Laissez les champs Template et CSS vides pour conserver le rendu standard.',
        'Après modification d’un fichier, videz le cache de thème Geeklog si le changement n’apparaît pas immédiatement.'
    );
    $exampleTitle = 'Exemple';
    $backLabel = 'Retour à la catégorie';
} else {
    $pageTitle = 'Template and CSS guide';
    $intro = 'Custom templates and stylesheets let administrators adapt a category public presentation without editing plugin files. Custom assets are stored outside the plugin directory so upgrades do not overwrite them.';
    $templateTitle = 'Custom template';
    $templateText = 'In the category Template field, enter only the template directory name, without a path. The directory must contain at least document.thtml and doccomments.thtml. scripts.thtml is optional.';
    $cssTitle = 'Custom CSS stylesheet';
    $cssText = 'In the category CSS field, enter only the .css filename. Documents serves the file from private persistent storage; full paths and URLs are not accepted.';
    $pathsTitle = 'Storage locations';
    $securityTitle = 'Security rules';
    $securityItems = array(
        'Never use .. or an absolute path.',
        'Template names accept only safe filename characters.',
        'CSS must be a filename ending in .css.',
        'Leave Template and CSS empty to use the standard presentation.',
        'Clear the Geeklog theme cache if a file change does not appear immediately.'
    );
    $exampleTitle = 'Example';
    $backLabel = 'Back to category';
}

function DOCUMENTS_presentationHelpCode($value)
{
    return '<code>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</code>';
}

$list = '<ul>';
foreach ($securityItems as $item) {
    $list .= '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
}
$list .= '</ul>';

$content = '<main class="documents-admin-page">'
    . DOCUMENTS_adminNavigation('edit_cat')
    . '<header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8')
    . '</h1><p class="documents-admin-page__lead">'
    . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8')
    . '</p></header>'
    . '<div class="documents-admin-primary-content">'
    . '<section class="documents-admin-card"><div class="documents-admin-card__body">'
    . '<h2>' . htmlspecialchars($templateTitle, ENT_QUOTES, 'UTF-8') . '</h2><p>'
    . htmlspecialchars($templateText, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p>' . DOCUMENTS_presentationHelpCode('document.thtml') . ' · '
    . DOCUMENTS_presentationHelpCode('doccomments.thtml') . ' · '
    . DOCUMENTS_presentationHelpCode('scripts.thtml') . '</p>'
    . '</div></section>'
    . '<section class="documents-admin-card"><div class="documents-admin-card__body">'
    . '<h2>' . htmlspecialchars($cssTitle, ENT_QUOTES, 'UTF-8') . '</h2><p>'
    . htmlspecialchars($cssText, ENT_QUOTES, 'UTF-8') . '</p></div></section>'
    . '<section class="documents-admin-card"><div class="documents-admin-card__body">'
    . '<h2>' . htmlspecialchars($pathsTitle, ENT_QUOTES, 'UTF-8') . '</h2>'
    . '<p><strong>Templates:</strong> ' . DOCUMENTS_presentationHelpCode($templatesRoot) . '</p>'
    . '<p><strong>CSS:</strong> ' . DOCUMENTS_presentationHelpCode($stylesRoot) . '</p>'
    . '</div></section>'
    . '<section class="documents-admin-card"><div class="documents-admin-card__body">'
    . '<h2>' . htmlspecialchars($securityTitle, ENT_QUOTES, 'UTF-8') . '</h2>' . $list
    . '</div></section>'
    . '<section class="documents-admin-card"><div class="documents-admin-card__body">'
    . '<h2>' . htmlspecialchars($exampleTitle, ENT_QUOTES, 'UTF-8') . '</h2>'
    . '<p>Template: ' . DOCUMENTS_presentationHelpCode('fiche') . '</p>'
    . '<p>CSS: ' . DOCUMENTS_presentationHelpCode('fiche.css') . '</p>'
    . '<p>' . DOCUMENTS_presentationHelpCode(rtrim($templatesRoot, "/\\") . DIRECTORY_SEPARATOR . 'fiche' . DIRECTORY_SEPARATOR . 'document.thtml') . '</p>'
    . '<p>' . DOCUMENTS_presentationHelpCode(rtrim($stylesRoot, "/\\") . DIRECTORY_SEPARATOR . 'fiche.css') . '</p>'
    . '</div></section>'
    . '<p><a class="documents-admin-button" href="'
    . htmlspecialchars($adminUrl . '/index.php?mode=edit_cat', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8') . '</a></p>'
    . '</div></main>';

$content = DOCUMENTS_wrapBlock($content, 'admin', 'edit_cat');
COM_output(COM_createHTMLDocument($content, array('pagetitle' => $pageTitle)));
