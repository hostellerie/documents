<?php

/* Public document controller. Geeklog 2.1.1-2.2.2 / PHP 5.6+. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    echo COM_refresh($_CONF['site_url'] . '/index.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'integrity.php';
require_once $pluginPath . 'presentation.php';
require_once $pluginPath . 'public_document.php';

$categorySlug = isset($_GET['cat'])
    ? DOCUMENTS_normalizeRouteSlug((string) $_GET['cat']) : '';
$documentSlug = isset($_GET['doc'])
    ? DOCUMENTS_normalizeRouteSlug((string) $_GET['doc']) : '';

if ($categorySlug === '' || $documentSlug === '') {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$requestPath = isset($_SERVER['REQUEST_URI'])
    ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)
    : '';
if (is_string($requestPath) && basename($requestPath) === 'document.php') {
    header('Location: ' . DOCUMENTS_interopCanonicalUrl($categorySlug, $documentSlug), true, 301);
    exit;
}

$_REQUEST['mode'] = 'view';
$_REQUEST['cat'] = $categorySlug;
$_REQUEST['doc'] = $documentSlug;

DOCUMENTS_preparePublicPresentation();

$page = DOCUMENTS_renderPublicDocument($categorySlug, $documentSlug);
if ($page === false) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$navigation = DOCUMENTS_renderNavigation();
$body = (string) $page['body'];
if (strpos($body, $navigation) === 0) {
    $body = substr($body, strlen($navigation));
}

$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$content = $navigation
    . '<main class="documents-document-page">'
    . '<header class="documents-page-header"><h1>'
    . htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8')
    . '</h1></header>'
    . DOCUMENTS_sectionBlock($isFrench ? 'Détails du document' : 'Document details', $body)
    . '</main>';

COM_output(DOCUMENTS_createPublicPage($content, $page['title']));
