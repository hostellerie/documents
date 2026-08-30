<?php

/* Controlled public endpoint for persistent Documents category styles. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'include_compat.php';
require_once $pluginPath . 'custom_assets.php';

$name = isset($_GET['name']) ? DOCUMENTS_customStyleName($_GET['name']) : '';
$path = ($name !== '') ? DOCUMENTS_customStylePath($name) : '';

if ($path === '') {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$mtime = @filemtime($path);
if ($mtime === false) {
    $mtime = time();
}

$etag = '"' . sha1($path . ':' . $mtime . ':' . filesize($path)) . '"';
header('Content-Type: text/css; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600, must-revalidate');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

if (isset($_SERVER['HTTP_IF_NONE_MATCH'])
    && trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    header('HTTP/1.1 304 Not Modified');
    exit;
}

readfile($path);
exit;
