<?php
// +--------------------------------------------------------------------------+
// | Documents Plugin 1.1.2 - Geeklog CMS                                    |
// +--------------------------------------------------------------------------+
// | ajax.php                                                                 |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                        |
// |                                                                          |
// | Authors: Ben - ben AT geeklog DOT fr                                     |
// +--------------------------------------------------------------------------+

require_once '../../../lib-common.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_PLUGINS) || !is_array($_PLUGINS)
    || !in_array('documents', $_PLUGINS, true)
    || !SEC_hasRights('documents.admin')) {
    http_response_code(403);
    echo json_encode(array('error' => 'forbidden'));
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(array('error' => 'method_not_allowed'));
    exit;
}

if (!SEC_checkToken()) {
    http_response_code(403);
    echo json_encode(array('error' => 'invalid_token'));
    exit;
}

$vars = array(
    'cat_id'  => 'number',
    's_group' => 'number',
    'action'  => 'text'
);
DOCUMENTS_filterVars($vars, $_POST);

$_DOCUMENTS_CONF['ajax'] = true;

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';

switch ($action) {
    case 'change_field_cat':
        $cat_id = isset($_POST['cat_id']) ? (int) $_POST['cat_id'] : 0;
        if ($cat_id < 1) {
            http_response_code(400);
            echo json_encode(array('error' => 'invalid_category'));
            exit;
        }

        $max = (int) DB_getItem(
            $_TABLES['documents_fields'],
            'MAX(f_order)',
            'cat_id=' . $cat_id
        ) + 10;

        $fields_order = '';
        $res = DB_query(
            "SELECT f_order, f_name
               FROM {$_TABLES['documents_fields']}
              WHERE cat_id = {$cat_id}
              ORDER BY f_order"
        );

        while ($A = DB_fetchArray($res)) {
            $fields_order .= (int) $A['f_order'] . '. '
                           . htmlspecialchars($A['f_name'], ENT_QUOTES, 'UTF-8')
                           . '<br' . XHTML . '>';
        }

        if ($fields_order === '') {
            $fields_order = '--';
        }

        echo json_encode(array('a' => $max, 'b' => $fields_order));
        break;

    case 'change_select_group':
        $s_group = isset($_POST['s_group']) ? (int) $_POST['s_group'] : 0;
        if ($s_group < 1) {
            http_response_code(400);
            echo json_encode(array('error' => 'invalid_select_group'));
            exit;
        }

        $max = (int) DB_getItem(
            $_TABLES['documents_selects'],
            'MAX(s_order)',
            's_group=' . $s_group
        ) + 10;

        $select_order = '';
        $res = DB_query(
            "SELECT s_order, s_name
               FROM {$_TABLES['documents_selects']}
              WHERE s_group = {$s_group}
              ORDER BY s_order"
        );

        while ($A = DB_fetchArray($res)) {
            $select_order .= (int) $A['s_order'] . '. '
                          . htmlspecialchars($A['s_name'], ENT_QUOTES, 'UTF-8')
                          . '<br' . XHTML . '>';
        }

        if ($select_order === '') {
            $select_order = '--';
        }

        echo json_encode(array('a' => $max, 'b' => $select_order));
        break;

    default:
        http_response_code(400);
        echo json_encode(array('error' => 'invalid_action'));
        break;
}
