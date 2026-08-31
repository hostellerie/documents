<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin                                                         |
// +---------------------------------------------------------------------------+
// | interoperability.php                                                      |
// |                                                                           |
// | Structured content, URL and lifecycle interoperability helpers.           |
// +---------------------------------------------------------------------------+

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'interoperability.php') !== false) {
    die('This file can not be used on its own.');
}

/* Keep the lifecycle layer independent from include_compat.php load order. */
if (!defined('DOCUMENTS_STATUS_INACTIVE')) {
    define('DOCUMENTS_STATUS_INACTIVE', 0);
}
if (!defined('DOCUMENTS_STATUS_ACTIVE')) {
    define('DOCUMENTS_STATUS_ACTIVE', 1);
}
if (!defined('DOCUMENTS_STATUS_DRAFT')) {
    define('DOCUMENTS_STATUS_DRAFT', 2);
}
if (!defined('DOCUMENTS_STATUS_SUBMISSION')) {
    define('DOCUMENTS_STATUS_SUBMISSION', 3);
}

function DOCUMENTS_interopCanonicalUrl($categorySlug, $documentSlug = '')
{
    global $_CONF, $_DOCUMENTS_CONF;

    $base = isset($_DOCUMENTS_CONF['site_url']) && $_DOCUMENTS_CONF['site_url'] !== ''
        ? rtrim((string) $_DOCUMENTS_CONF['site_url'], '/')
        : rtrim((string) $_CONF['site_url'], '/') . '/documents';

    $url = $base;
    if ($categorySlug !== '') {
        $url .= '/' . rawurlencode((string) $categorySlug);
    }
    if ($documentSlug !== '') {
        $url .= '/' . rawurlencode((string) $documentSlug);
    }

    return $url;
}

function DOCUMENTS_interopRememberUrl($id, $url)
{
    $id = trim((string) $id);
    $url = trim((string) $url);
    if ($id === '' || $url === '') {
        return;
    }

    if (!isset($GLOBALS['DOCUMENTS_INTEROP_URL_CACHE'])
        || !is_array($GLOBALS['DOCUMENTS_INTEROP_URL_CACHE'])) {
        $GLOBALS['DOCUMENTS_INTEROP_URL_CACHE'] = array();
    }

    $GLOBALS['DOCUMENTS_INTEROP_URL_CACHE'][$id] = $url;
}

function DOCUMENTS_interopRememberedUrl($id)
{
    $id = trim((string) $id);
    if ($id === '' || !isset($GLOBALS['DOCUMENTS_INTEROP_URL_CACHE'])
        || !is_array($GLOBALS['DOCUMENTS_INTEROP_URL_CACHE'])
        || !isset($GLOBALS['DOCUMENTS_INTEROP_URL_CACHE'][$id])) {
        return '';
    }

    return (string) $GLOBALS['DOCUMENTS_INTEROP_URL_CACHE'][$id];
}

function DOCUMENTS_interopRequestedFields($what)
{
    if (is_array($what)) {
        return array_values(array_map('trim', $what));
    }

    $what = trim((string) $what);
    if ($what === '' || $what === '*') {
        return array();
    }

    return array_map('trim', explode(',', $what));
}

function DOCUMENTS_interopSelectFields($item, $what)
{
    $fields = DOCUMENTS_interopRequestedFields($what);
    if (empty($fields)) {
        return $item;
    }

    $result = array();
    foreach ($fields as $field) {
        if (array_key_exists($field, $item) && $item[$field] !== '') {
            $result[$field] = $item[$field];
        }
    }

    return $result;
}

function DOCUMENTS_interopSelectSingle($item, $what)
{
    $fields = DOCUMENTS_interopRequestedFields($what);
    if (empty($fields)) {
        return $item;
    }

    $values = array();
    foreach ($fields as $field) {
        $values[] = array_key_exists($field, $item) ? $item[$field] : '';
    }

    return count($values) === 1 ? $values[0] : $values;
}

function DOCUMENTS_interopTimestamp($value)
{
    if ($value === null || $value === '') {
        return 0;
    }
    if (is_numeric($value)) {
        return (int) $value;
    }

    $timestamp = strtotime((string) $value);
    return ($timestamp === false) ? 0 : $timestamp;
}

function DOCUMENTS_interopExcerpt($value, $length = 240)
{
    $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', trim($value));
    if ($value === '') {
        return '';
    }

    if (function_exists('MBYTE_strlen') && function_exists('MBYTE_substr')) {
        return MBYTE_strlen($value) > $length
            ? rtrim(MBYTE_substr($value, 0, $length - 1)) . '…'
            : $value;
    }

    return strlen($value) > $length
        ? rtrim(substr($value, 0, $length - 3)) . '...'
        : $value;
}

function DOCUMENTS_interopItem($documentId, $uid = 0)
{
    global $_TABLES, $_DOCUMENTS_CONF;

    $documentId = trim((string) $documentId);
    if ($documentId === '' || $documentId === '*') {
        return array();
    }

    $safeId = DB_escapeString($documentId);
    $uid = (int) $uid;

    $sql = "SELECT d.doc_url, d.created, d.modified, d.owner_id, d.hits, "
        . "c.cid AS category_id, c.cat_name AS category, c.cat_url AS category_slug, "
        . "title_value.v_value AS title, description_value.v_value AS description, "
        . "image_value.v_value AS image "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS first_value "
        . "ON first_value.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS first_field "
        . "ON first_field.fid=first_value.field_id "
        . "INNER JOIN {$_TABLES['documents_cat']} AS c ON c.cid=first_field.cat_id "
        . "LEFT JOIN {$_TABLES['documents_values']} AS title_value "
        . "ON title_value.doc_url=d.doc_url AND title_value.field_id=("
        . "SELECT ft.fid FROM {$_TABLES['documents_fields']} AS ft "
        . "WHERE ft.cat_id=c.cid ORDER BY ft.f_order ASC, ft.fid ASC LIMIT 1) "
        . "LEFT JOIN {$_TABLES['documents_values']} AS description_value "
        . "ON description_value.doc_url=d.doc_url AND description_value.field_id=("
        . "SELECT fd.fid FROM {$_TABLES['documents_fields']} AS fd "
        . "WHERE fd.cat_id=c.cid AND fd.f_type IN ('text','textarea') "
        . "AND fd.fid<>(SELECT ftitle.fid FROM {$_TABLES['documents_fields']} AS ftitle "
        . "WHERE ftitle.cat_id=c.cid ORDER BY ftitle.f_order ASC, ftitle.fid ASC LIMIT 1) "
        . "ORDER BY fd.f_order ASC, fd.fid ASC LIMIT 1) "
        . "LEFT JOIN {$_TABLES['documents_values']} AS image_value "
        . "ON image_value.doc_url=d.doc_url AND image_value.field_id=("
        . "SELECT fi.fid FROM {$_TABLES['documents_fields']} AS fi "
        . "WHERE fi.cat_id=c.cid AND fi.f_type='image' ORDER BY fi.f_order ASC, fi.fid ASC LIMIT 1) "
        . "WHERE d.doc_url='{$safeId}' "
        . "AND first_field.f_order=(SELECT MIN(fmin.f_order) FROM {$_TABLES['documents_fields']} AS fmin "
        . "WHERE fmin.cat_id=c.cid)"
        . COM_getPermSQL('AND', $uid, 2, 'd')
        . COM_getPermSQL('AND', $uid, 2, 'c')
        . " LIMIT 1";

    $row = DB_fetchArray(DB_query($sql));
    if (!is_array($row) || empty($row['doc_url']) || empty($row['category_slug'])) {
        return array();
    }

    $title = trim(stripslashes(isset($row['title']) ? $row['title'] : ''));
    if ($title === '') {
        $title = $row['doc_url'];
    }

    $description = isset($row['description']) ? trim(stripslashes($row['description'])) : '';
    $image = isset($row['image']) ? trim((string) $row['image']) : '';
    if ($image !== '' && isset($_DOCUMENTS_CONF['images_url'])) {
        $image = rtrim((string) $_DOCUMENTS_CONF['images_url'], '/') . '/' . rawurlencode(basename($image));
    }

    $created = DOCUMENTS_interopTimestamp(isset($row['created']) ? $row['created'] : '');
    $modified = DOCUMENTS_interopTimestamp(isset($row['modified']) ? $row['modified'] : '');
    if ($modified <= 0) {
        $modified = $created;
    }

    $url = DOCUMENTS_interopCanonicalUrl($row['category_slug'], $row['doc_url']);
    DOCUMENTS_interopRememberUrl($row['doc_url'], $url);

    return array(
        'id' => (string) $row['doc_url'],
        'type' => 'documents',
        'subtype' => 'document',
        'title' => $title,
        'url' => $url,
        'description' => $description,
        'excerpt' => DOCUMENTS_interopExcerpt($description),
        'date-created' => $created,
        'date-modified' => $modified,
        'uid' => isset($row['owner_id']) ? (int) $row['owner_id'] : 0,
        'author' => isset($row['owner_id']) ? COM_getDisplayName((int) $row['owner_id']) : '',
        'image' => $image,
        'category' => isset($row['category']) ? stripslashes($row['category']) : '',
        'category-id' => isset($row['category_id']) ? (int) $row['category_id'] : 0,
        'category-slug' => (string) $row['category_slug'],
        'hits' => isset($row['hits']) ? (int) $row['hits'] : 0
    );
}

function DOCUMENTS_interopResolveStoredUrl($documentId)
{
    global $_TABLES;

    $remembered = DOCUMENTS_interopRememberedUrl($documentId);
    if ($remembered !== '') {
        return $remembered;
    }

    $safeId = DB_escapeString((string) $documentId);
    $sql = "SELECT c.cat_url FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "INNER JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE v.doc_url='{$safeId}' ORDER BY f.f_order ASC, f.fid ASC LIMIT 1";
    $row = DB_fetchArray(DB_query($sql));
    if (!is_array($row) || empty($row['cat_url'])) {
        return '';
    }

    $url = DOCUMENTS_interopCanonicalUrl($row['cat_url'], $documentId);
    DOCUMENTS_interopRememberUrl($documentId, $url);
    return $url;
}

function DOCUMENTS_interopItems($what, $uid, $options)
{
    global $_TABLES;

    $uid = (int) $uid;
    $options = is_array($options) ? $options : array();
    $limit = array_key_exists('limit', $options) ? (int) $options['limit'] : 20;
    if ($limit < 0) {
        $limit = 20;
    } elseif ($limit > 1000) {
        $limit = 1000;
    }

    $since = isset($options['since']) ? DOCUMENTS_interopTimestamp($options['since']) : 0;
    $createdSince = 0;
    if (isset($options['filter']) && is_array($options['filter'])
        && isset($options['filter']['date-created'])) {
        $createdSince = DOCUMENTS_interopTimestamp($options['filter']['date-created']);
    }

    $order = isset($options['order']) ? strtolower(trim((string) $options['order'])) : 'modified-desc';

    $sql = "SELECT DISTINCT d.doc_url FROM {$_TABLES['documents_docs']} AS d "
        . "WHERE d.active=1"
        . COM_getPermSQL('AND', $uid, 2, 'd');

    if ($since > 0) {
        $sql .= " AND UNIX_TIMESTAMP(COALESCE(d.modified,d.created)) >= " . (int) $since;
    }
    if ($createdSince > 0) {
        $sql .= " AND UNIX_TIMESTAMP(d.created) >= " . (int) $createdSince;
    }

    if ($order === 'created-desc') {
        $sql .= " ORDER BY d.created DESC, d.did DESC";
    } elseif ($order === 'created-asc') {
        $sql .= " ORDER BY d.created ASC, d.did ASC";
    } elseif ($order === 'modified-asc') {
        $sql .= " ORDER BY COALESCE(d.modified,d.created) ASC, d.did ASC";
    } else {
        $sql .= " ORDER BY COALESCE(d.modified,d.created) DESC, d.did DESC";
    }

    if ($limit > 0) {
        $sql .= " LIMIT " . $limit;
    }

    $result = DB_query($sql);
    $items = array();
    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['doc_url'])) {
            continue;
        }
        $item = DOCUMENTS_interopItem($row['doc_url'], $uid);
        if (!empty($item)) {
            $items[] = DOCUMENTS_interopSelectFields($item, $what);
        }
    }

    return $items;
}

function plugin_getiteminfo_documents($id, $what = '', $uid = 0, $options = array())
{
    if ((string) $id === '*') {
        return DOCUMENTS_interopItems($what, $uid, $options);
    }

    $item = DOCUMENTS_interopItem($id, $uid);
    if (empty($item)) {
        return false;
    }

    return DOCUMENTS_interopSelectSingle($item, $what);
}

function plugin_idtourl_documents($sub_type, $item_id)
{
    $url = DOCUMENTS_interopRememberedUrl($item_id);
    if ($url !== '') {
        return $url;
    }

    $item = DOCUMENTS_interopItem($item_id, 0);
    if (isset($item['url']) && $item['url'] !== '') {
        return $item['url'];
    }

    return DOCUMENTS_interopResolveStoredUrl($item_id);
}

function plugin_urltoid_documents($url)
{
    global $_DOCUMENTS_CONF;

    $base = isset($_DOCUMENTS_CONF['site_url']) ? rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') : '';
    $path = parse_url((string) $url, PHP_URL_PATH);
    $basePath = ($base !== '') ? parse_url($base, PHP_URL_PATH) : '';

    if (!is_string($path) || !is_string($basePath) || strpos($path, rtrim($basePath, '/') . '/') !== 0) {
        return array();
    }

    $relative = trim(substr($path, strlen(rtrim($basePath, '/'))), '/');
    $parts = array_values(array_filter(explode('/', $relative), 'strlen'));
    if (count($parts) !== 2) {
        return array();
    }

    $category = rawurldecode($parts[0]);
    $id = rawurldecode($parts[1]);
    $storedUrl = DOCUMENTS_interopResolveStoredUrl($id);
    if ($storedUrl === '' || DOCUMENTS_interopCanonicalUrl($category, $id) !== $storedUrl) {
        return array();
    }

    return array('type' => 'documents', 'id' => $id, 'subtype' => 'document');
}

function DOCUMENTS_interopNotifySaved($id, $previousStatus, $newStatus)
{
    $previousStatus = (int) $previousStatus;
    $newStatus = (int) $newStatus;

    if ($newStatus === DOCUMENTS_STATUS_ACTIVE && function_exists('PLG_itemSaved')) {
        PLG_itemSaved((string) $id, 'documents');
    } elseif ($previousStatus === DOCUMENTS_STATUS_ACTIVE
        && $newStatus !== DOCUMENTS_STATUS_ACTIVE
        && function_exists('PLG_itemDeleted')) {
        PLG_itemDeleted((string) $id, 'documents');
    }
}

function DOCUMENTS_interopNotifyDeleted($id)
{
    if (function_exists('PLG_itemDeleted')) {
        PLG_itemDeleted((string) $id, 'documents');
    }
}

function plugin_collectSitemapItems_documents($uid, $limit)
{
    $items = DOCUMENTS_interopItems('url,date-modified', (int) $uid, array(
        'limit' => (int) $limit,
        'order' => 'modified-desc'
    ));

    $result = array();
    foreach ($items as $item) {
        if (!empty($item['url'])) {
            $result[] = array(
                'url' => $item['url'],
                'date-modified' => isset($item['date-modified']) ? (int) $item['date-modified'] : 0
            );
        }
    }

    return $result;
}

function DOCUMENTS_interopCapabilities()
{
    return array(
        'content_info' => true,
        'collections' => true,
        'item_saved' => true,
        'item_deleted' => true,
        'id_to_url' => true,
        'url_to_id' => true,
        'sitemap_collection' => true,
        'autotags' => true,
        'php_blocks' => true,
        'audience_metrics' => false,
        'search_metrics' => false,
        'query_metrics' => false,
        'indexing_status' => false,
        'submission_status' => false
    );
}

$documentsEmbedsFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'embeds.php';
if (is_file($documentsEmbedsFile)) {
    require_once $documentsEmbedsFile;
}
