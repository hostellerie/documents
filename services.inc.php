<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.2.0                                                    |
// +---------------------------------------------------------------------------+
// | services.inc.php                                                          |
// |                                                                           |
// | Read-only shared services for dashboards and structured consumers.        |
// +---------------------------------------------------------------------------+

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'services.inc.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_serviceRejectWeb($args, &$svc_msg)
{
    if (is_array($args) && !empty($args['gl_svc'])) {
        $svc_msg['error_desc'] = 'Documents interoperability services are available only to trusted internal plugin calls.';
        return true;
    }

    return false;
}

function DOCUMENTS_serviceCurrentUid()
{
    global $_USER;

    return isset($_USER['uid']) ? (int) $_USER['uid'] : 1;
}

function DOCUMENTS_serviceDocumentContext($id)
{
    global $_CONF, $_TABLES;

    if (!function_exists('DOCUMENTS_canViewDocument')) {
        require_once $_CONF['path'] . 'plugins/documents/include_compat.php';
    }

    $id = trim((string) $id);
    if ($id === '') {
        return false;
    }

    $safeId = DB_escapeString($id);
    $sql = "SELECT d.*, c.cid AS category_id, c.cat_name AS category_name, "
        . "c.cat_url AS category_slug, c.owner_id AS cat_owner_id, c.group_id AS cat_group_id, "
        . "c.perm_owner AS cat_perm_owner, c.perm_group AS cat_perm_group, "
        . "c.perm_members AS cat_perm_members, c.perm_anon AS cat_perm_anon "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "INNER JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE d.doc_url='{$safeId}' ORDER BY f.f_order ASC, f.fid ASC LIMIT 1";

    $row = DB_fetchArray(DB_query($sql));
    if (!is_array($row) || empty($row['doc_url'])) {
        return false;
    }

    if (!DOCUMENTS_canViewDocument($row, 2)) {
        return false;
    }

    $categoryAccess = SEC_hasAccess(
        (int) $row['cat_owner_id'],
        (int) $row['cat_group_id'],
        (int) $row['cat_perm_owner'],
        (int) $row['cat_perm_group'],
        (int) $row['cat_perm_members'],
        (int) $row['cat_perm_anon']
    );
    if ($categoryAccess < 2) {
        return false;
    }

    return $row;
}

function service_dashboard_summary_documents($args, &$output, &$svc_msg)
{
    global $_CONF, $_TABLES, $LANG_DOCUMENTS_1;

    $output = array();
    $svc_msg = array();

    if (DOCUMENTS_serviceRejectWeb($args, $svc_msg)) {
        return PLG_RET_AUTH_FAILED;
    }
    if (!SEC_hasRights('documents.admin')) {
        $svc_msg['error_desc'] = 'Documents administration permission is required.';
        return PLG_RET_AUTH_FAILED;
    }

    $documents = (int) DB_count($_TABLES['documents_docs']);
    $active = (int) DB_count($_TABLES['documents_docs'], 'active', DOCUMENTS_STATUS_ACTIVE);
    $drafts = (int) DB_count($_TABLES['documents_docs'], 'active', DOCUMENTS_STATUS_DRAFT);
    $pending = (int) DB_count($_TABLES['documents_docs'], 'active', DOCUMENTS_STATUS_SUBMISSION);
    $categories = (int) DB_count($_TABLES['documents_cat']);

    $output = array(
        'schema' => 1,
        'status' => 'ok',
        'metrics' => array(
            array('id' => 'documents', 'label' => 'Documents', 'value' => $documents),
            array('id' => 'published', 'label' => 'Published', 'value' => $active),
            array('id' => 'drafts', 'label' => 'Drafts', 'value' => $drafts),
            array('id' => 'pending', 'label' => 'Pending submissions', 'value' => $pending),
            array('id' => 'categories', 'label' => 'Categories', 'value' => $categories)
        ),
        'alerts' => array(),
        'links' => array(
            array(
                'label' => isset($LANG_DOCUMENTS_1['plugin_name']) ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents',
                'url' => rtrim($_CONF['site_admin_url'], '/') . '/plugins/documents/'
            )
        ),
        'updated' => time()
    );

    return PLG_RET_OK;
}

function service_fields_describe_documents($args, &$output, &$svc_msg)
{
    global $_TABLES;

    $output = array();
    $svc_msg = array();

    if (DOCUMENTS_serviceRejectWeb($args, $svc_msg)) {
        return PLG_RET_AUTH_FAILED;
    }

    $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : 0;
    $documentId = isset($args['id']) ? trim((string) $args['id']) : '';

    if ($documentId !== '') {
        $context = DOCUMENTS_serviceDocumentContext($documentId);
        if ($context === false) {
            $svc_msg['error_desc'] = 'Document not found or not accessible.';
            return PLG_RET_ERROR;
        }
        $categoryId = (int) $context['category_id'];
    }

    if ($categoryId <= 0) {
        $svc_msg['error_desc'] = 'A valid category_id or document id is required.';
        return PLG_RET_ERROR;
    }

    $sql = "SELECT fid,f_name,f_order,f_type,sel_id,var_name,f_help,f_required,f_on_list,display_empty "
        . "FROM {$_TABLES['documents_fields']} WHERE cat_id=" . $categoryId
        . " ORDER BY f_order ASC,fid ASC";
    $result = DB_query($sql);

    $fields = array();
    while ($row = DB_fetchArray($result)) {
        $fields[] = array(
            'id' => (int) $row['fid'],
            'name' => (string) $row['var_name'],
            'label' => stripslashes((string) $row['f_name']),
            'type' => (string) $row['f_type'],
            'required' => !empty($row['f_required']),
            'list' => !empty($row['f_on_list']),
            'display_empty' => !empty($row['display_empty']),
            'selection_group' => (int) $row['sel_id'],
            'help' => stripslashes((string) $row['f_help']),
            'order' => (int) $row['f_order']
        );
    }

    $output = array(
        'schema' => 1,
        'provider' => 'documents',
        'category_id' => $categoryId,
        'fields' => $fields
    );

    return PLG_RET_OK;
}

function service_source_fields_get_documents($args, &$output, &$svc_msg)
{
    global $_TABLES;

    $output = array();
    $svc_msg = array();

    if (DOCUMENTS_serviceRejectWeb($args, $svc_msg)) {
        return PLG_RET_AUTH_FAILED;
    }

    $documentId = isset($args['id']) ? trim((string) $args['id']) : '';
    $context = DOCUMENTS_serviceDocumentContext($documentId);
    if ($context === false) {
        $svc_msg['error_desc'] = 'Document not found or not accessible.';
        return PLG_RET_ERROR;
    }

    $requested = array();
    if (isset($args['fields']) && is_array($args['fields'])) {
        foreach ($args['fields'] as $field) {
            $field = trim((string) $field);
            if ($field !== '') {
                $requested[$field] = true;
            }
        }
    }

    $sql = "SELECT f.fid,f.f_name,f.f_type,f.var_name,v.v_value "
        . "FROM {$_TABLES['documents_fields']} AS f "
        . "LEFT JOIN {$_TABLES['documents_values']} AS v "
        . "ON v.field_id=f.fid AND v.doc_url='" . DB_escapeString($documentId) . "' "
        . "WHERE f.cat_id=" . (int) $context['category_id']
        . " ORDER BY f.f_order ASC,f.fid ASC";
    $result = DB_query($sql);

    $fields = array();
    while ($row = DB_fetchArray($result)) {
        $name = trim((string) $row['var_name']);
        if (!empty($requested) && !isset($requested[$name])) {
            continue;
        }

        $format = ((string) $row['f_type'] === 'textarea') ? 'text/html' : 'text/plain';
        $fields[] = array(
            'name' => $name,
            'label' => stripslashes((string) $row['f_name']),
            'type' => (string) $row['f_type'],
            'format' => $format,
            'value' => isset($row['v_value']) ? stripslashes((string) $row['v_value']) : '',
            'writable' => false
        );
    }

    $output = array(
        'schema' => 1,
        'provider' => 'documents',
        'type' => 'documents',
        'subtype' => 'document',
        'id' => $documentId,
        'category_id' => (int) $context['category_id'],
        'fields' => $fields
    );

    return PLG_RET_OK;
}

function plugin_wsEnabled_documents()
{
    return true;
}
