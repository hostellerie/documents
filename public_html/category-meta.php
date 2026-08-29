<?php

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

if (!SEC_hasRights('documents.admin')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');

$cid = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
if ($cid <= 0) {
    echo json_encode(array('metadescription' => ''));
    exit;
}

$value = DB_getItem($_TABLES['documents_cat'], 'metadescription', 'cid=' . $cid);
echo json_encode(array('metadescription' => (string) $value));
