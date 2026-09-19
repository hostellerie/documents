<?php

/* Temporary route diagnostic for Documents 1.2.0 development.
 * Remove before the final release. Compatible with PHP 5.6+. */

function DOCUMENTS_routeDiagnosticMarker($label)
{
    $line = 'DOCUMENTS DEBUG ' . (string) $label;
    echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "<br>\n";
    @ob_flush();
    @flush();
    error_log($line);
}

DOCUMENTS_routeDiagnosticMarker('D0 - script entered, before lib-common');

require_once '../lib-common.php';
DOCUMENTS_routeDiagnosticMarker('D1 - lib-common loaded');

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)) {
    DOCUMENTS_routeDiagnosticMarker('STOP - documents plugin is not active');
    exit;
}
DOCUMENTS_routeDiagnosticMarker('D2 - documents plugin active');

if (!SEC_inGroup('Root')) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Root only';
    exit;
}
DOCUMENTS_routeDiagnosticMarker('D3 - Root access confirmed');

$pluginPath = $_CONF['path'] . 'plugins/documents/';

DOCUMENTS_routeDiagnosticMarker('D4 - before rewrite.php');
require_once $pluginPath . 'rewrite.php';
DOCUMENTS_routeDiagnosticMarker('D5 - rewrite.php loaded');

DOCUMENTS_routeDiagnosticMarker('D6 - before DOCUMENTS_writeHtaccess(false)');
DOCUMENTS_writeHtaccess(false);
DOCUMENTS_routeDiagnosticMarker('D7 - htaccess check complete');

DOCUMENTS_routeDiagnosticMarker('D8 - before runtime.php');
require_once $pluginPath . 'runtime.php';
DOCUMENTS_routeDiagnosticMarker('D9 - runtime.php loaded');

DOCUMENTS_routeDiagnosticMarker('D10 - before DOCUMENTS_ensureImageDirectory');
DOCUMENTS_ensureImageDirectory();
DOCUMENTS_routeDiagnosticMarker('D11 - image directory check complete');

DOCUMENTS_routeDiagnosticMarker('D12 - before include_compat.php');
require_once $pluginPath . 'include_compat.php';
DOCUMENTS_routeDiagnosticMarker('D13 - include_compat.php loaded');

DOCUMENTS_routeDiagnosticMarker('D14 - before integrity.php');
require_once $pluginPath . 'integrity.php';
DOCUMENTS_routeDiagnosticMarker('D15 - integrity.php loaded');

DOCUMENTS_routeDiagnosticMarker('D16 - before request defaults');
DOCUMENTS_initializeRequestDefaults($_REQUEST);
DOCUMENTS_routeDiagnosticMarker('D17 - request defaults initialized');

$categoryInput = isset($_GET['cat']) ? (string) $_GET['cat'] : 'plugins';
DOCUMENTS_routeDiagnosticMarker('D18 - category input = ' . $categoryInput);

DOCUMENTS_routeDiagnosticMarker('D19 - before DOCUMENTS_normalizeRouteSlug');
$categorySlug = DOCUMENTS_normalizeRouteSlug($categoryInput);
DOCUMENTS_routeDiagnosticMarker('D20 - normalized slug = ' . $categorySlug);

if ($categorySlug === '') {
    DOCUMENTS_routeDiagnosticMarker('STOP - normalized category slug is empty');
    exit;
}

DOCUMENTS_routeDiagnosticMarker('D21 - before DB_escapeString(category slug)');
$safeSlug = DB_escapeString($categorySlug);
DOCUMENTS_routeDiagnosticMarker('D22 - DB_escapeString completed = ' . $safeSlug);

DOCUMENTS_routeDiagnosticMarker('D23 - before category DB_query');
$sql = "SELECT cid, cat_name, cat_url, cat_help, metadescription, submitable, css, custom_header, custom_footer, "
    . "owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon "
    . "FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeSlug}' LIMIT 1";
$result = DB_query($sql);
DOCUMENTS_routeDiagnosticMarker('D24 - category DB_query completed');

DOCUMENTS_routeDiagnosticMarker('D25 - before DB_fetchArray');
$category = DB_fetchArray($result);
DOCUMENTS_routeDiagnosticMarker('D26 - category DB_fetchArray completed');

if (!is_array($category) || empty($category['cid'])) {
    DOCUMENTS_routeDiagnosticMarker('STOP - category not found');
    exit;
}
DOCUMENTS_routeDiagnosticMarker('D27 - category found cid=' . (int) $category['cid']);

DOCUMENTS_routeDiagnosticMarker('D28 - before SEC_hasAccess');
$access = SEC_hasAccess(
    (int) $category['owner_id'],
    (int) $category['group_id'],
    (int) $category['perm_owner'],
    (int) $category['perm_group'],
    (int) $category['perm_members'],
    (int) $category['perm_anon']
);
DOCUMENTS_routeDiagnosticMarker('D29 - SEC_hasAccess completed = ' . (int) $access);

DOCUMENTS_routeDiagnosticMarker('D30 - diagnostic completed successfully');
