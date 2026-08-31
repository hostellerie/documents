<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.9                                                    |
// +---------------------------------------------------------------------------+
// | include_compat.php                                                        |
// |                                                                           |
// | Compatibility helpers shared by the 1.1.x stabilization line.            |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2026 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |          Documents plugin contributors                                    |
// +---------------------------------------------------------------------------+

if (strpos(strtolower(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''), 'include_compat.php') !== false) {
    die('This file can not be used on its own.');
}

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

function DOCUMENTS_requestValue($source, $key, $default = '')
{
    return (is_array($source) && isset($source[$key])) ? $source[$key] : $default;
}

function DOCUMENTS_requestInt($source, $key, $default = 0)
{
    return (int) DOCUMENTS_requestValue($source, $key, $default);
}

function DOCUMENTS_initializeRequestDefaults(&$request)
{
    if (!is_array($request)) {
        $request = array();
    }

    $defaults = array(
        'mode' => '', 'op' => '', 'cat' => '', 'doc' => '', 'cid' => 0,
        'cat_name' => '', 'cat_url' => '', 'cat_order' => 0, 'css' => '',
        'map' => 0, 'template' => '', 'list_index' => 0, 'submitable' => 0,
        'cat_help' => '', 'custom_header' => '', 'custom_footer' => '',
        'owner_id' => 0, 'group_id' => 0, 'perm_owner' => '', 'perm_group' => '',
        'perm_members' => '', 'perm_anon' => '', 'field' => 0, 'fid' => 0,
        'cat_id' => 0, 'f_name' => '', 'f_order' => 0, 'f_type' => '',
        'sel_id' => 0, 'var_name' => '', 'f_help' => '', 'f_required' => 0,
        'f_on_list' => 0, 'doc_url' => '', 'group' => 0, 'select' => 0,
        'group_name' => '', 'group_help' => '', 'gid' => 0, 'sid' => 0,
        's_name' => '', 's_value' => '', 's_order' => 0, 's_group' => 0,
        'active' => 0, 'address' => '', 'lat' => '', 'lng' => '', 'mkid' => ''
    );

    foreach ($defaults as $key => $default) {
        if (!isset($request[$key])) {
            $request[$key] = $default;
        }
    }
}

function DOCUMENTS_requestPermissions($source, $defaults = array())
{
    $fallback = array(
        'perm_owner' => 3,
        'perm_group' => 3,
        'perm_members' => 2,
        'perm_anon' => 2
    );

    if (is_array($defaults)) {
        foreach ($fallback as $key => $value) {
            if (isset($defaults[$key])) {
                $fallback[$key] = (int) $defaults[$key];
            }
        }
    }

    $owner = DOCUMENTS_requestValue($source, 'perm_owner', $fallback['perm_owner']);
    $group = DOCUMENTS_requestValue($source, 'perm_group', $fallback['perm_group']);
    $members = DOCUMENTS_requestValue($source, 'perm_members', $fallback['perm_members']);
    $anon = DOCUMENTS_requestValue($source, 'perm_anon', $fallback['perm_anon']);

    if (is_array($owner) || is_array($group) || is_array($members) || is_array($anon)) {
        list($owner, $group, $members, $anon) = SEC_getPermissionValues(
            $owner,
            $group,
            $members,
            $anon
        );
    }

    return array((int) $owner, (int) $group, (int) $members, (int) $anon);
}

function DOCUMENTS_canViewDocument($document, $minimumAccess = 2)
{
    if (!is_array($document)
        || !isset($document['active'])
        || !isset($document['owner_id'])
        || !isset($document['group_id'])
        || !isset($document['perm_owner'])
        || !isset($document['perm_group'])
        || !isset($document['perm_members'])
        || !isset($document['perm_anon'])) {
        return false;
    }

    $status = (int) $document['active'];
    if ($status < DOCUMENTS_STATUS_INACTIVE || $status > DOCUMENTS_STATUS_SUBMISSION) {
        return false;
    }

    return SEC_hasAccess(
        (int) $document['owner_id'],
        (int) $document['group_id'],
        (int) $document['perm_owner'],
        (int) $document['perm_group'],
        (int) $document['perm_members'],
        (int) $document['perm_anon']
    ) >= (int) $minimumAccess;
}

function DOCUMENTS_canEditDocument($document)
{
    global $_USER;

    if (!is_array($document)
        || !isset($document['active'])
        || !isset($document['owner_id'])
        || !isset($document['group_id'])
        || !isset($document['perm_owner'])
        || !isset($document['perm_group'])
        || !isset($document['perm_members'])
        || !isset($document['perm_anon'])) {
        return false;
    }

    if (SEC_hasRights('documents.admin')) {
        return true;
    }

    $status = (int) $document['active'];
    if ($status < DOCUMENTS_STATUS_INACTIVE || $status > DOCUMENTS_STATUS_SUBMISSION) {
        return false;
    }
    if ($status === DOCUMENTS_STATUS_SUBMISSION) {
        return false;
    }
    if ($status === DOCUMENTS_STATUS_DRAFT) {
        $userId = isset($_USER['uid']) ? (int) $_USER['uid'] : 1;
        if ((int) $document['owner_id'] !== $userId) {
            return false;
        }
    }

    return SEC_hasAccess(
        (int) $document['owner_id'],
        (int) $document['group_id'],
        (int) $document['perm_owner'],
        (int) $document['perm_group'],
        (int) $document['perm_members'],
        (int) $document['perm_anon']
    ) >= 3;
}

function DOCUMENTS_normalizeDocumentStatus($requestedStatus, $currentStatus = null)
{
    $requestedStatus = (int) $requestedStatus;
    $isAdmin = SEC_hasRights('documents.admin');
    $isPublisher = SEC_hasRights('documents.publish');

    if ($isAdmin) {
        if ($requestedStatus < DOCUMENTS_STATUS_INACTIVE
            || $requestedStatus > DOCUMENTS_STATUS_SUBMISSION) {
            return DOCUMENTS_STATUS_ACTIVE;
        }
        return $requestedStatus;
    }

    if ($isPublisher) {
        return ($requestedStatus === DOCUMENTS_STATUS_DRAFT)
            ? DOCUMENTS_STATUS_DRAFT
            : DOCUMENTS_STATUS_ACTIVE;
    }

    if ($currentStatus === null) {
        return ($requestedStatus === DOCUMENTS_STATUS_DRAFT)
            ? DOCUMENTS_STATUS_DRAFT
            : DOCUMENTS_STATUS_SUBMISSION;
    }

    $currentStatus = (int) $currentStatus;
    if ($currentStatus === DOCUMENTS_STATUS_DRAFT) {
        return DOCUMENTS_STATUS_DRAFT;
    }
    if ($currentStatus === DOCUMENTS_STATUS_SUBMISSION) {
        return DOCUMENTS_STATUS_SUBMISSION;
    }
    if ($currentStatus === DOCUMENTS_STATUS_ACTIVE) {
        return ($requestedStatus === DOCUMENTS_STATUS_DRAFT)
            ? DOCUMENTS_STATUS_DRAFT
            : DOCUMENTS_STATUS_ACTIVE;
    }

    return $currentStatus;
}

function DOCUMENTS_linkifyUrls($content)
{
    return preg_replace_callback(
        '~https?://[^\s<)]+~i',
        'DOCUMENTS_linkifyUrlCallback',
        $content
    );
}

function DOCUMENTS_linkifyUrlCallback($matches)
{
    $url = isset($matches[0]) ? $matches[0] : '';
    if ($url === '') {
        return '';
    }

    $label = (strlen($url) >= 50) ? substr($url, 0, 50) . '...' : $url;
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    return '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" title="'
        . $safeUrl . '">' . $safeLabel . '</a>';
}

function DOCUMENTS_templateName($template)
{
    $template = trim((string) $template);
    if ($template === '' || basename($template) !== $template || strpos($template, '..') !== false) {
        return '';
    }
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $template)) {
        return '';
    }
    return $template;
}

function DOCUMENTS_customTemplateDir($template)
{
    $template = DOCUMENTS_templateName($template);
    if ($template === '') {
        return '';
    }
    $base = function_exists('DOCUMENTS_dataDir') ? DOCUMENTS_dataDir() : '';
    if ($base === '') {
        return '';
    }
    return rtrim($base, "/\\") . DIRECTORY_SEPARATOR
        . 'templates' . DIRECTORY_SEPARATOR
        . $template . DIRECTORY_SEPARATOR;
}

function DOCUMENTS_customTemplateReadDir($template)
{
    $template = DOCUMENTS_templateName($template);
    if ($template === '') {
        return '';
    }
    $newDir = DOCUMENTS_customTemplateDir($template);
    if ($newDir !== '' && is_dir($newDir)) {
        return $newDir;
    }
    $legacyBase = function_exists('DOCUMENTS_legacyDataDir') ? DOCUMENTS_legacyDataDir() : '';
    if ($legacyBase !== '') {
        $legacyDir = rtrim($legacyBase, "/\\") . DIRECTORY_SEPARATOR
            . 'templates' . DIRECTORY_SEPARATOR
            . $template . DIRECTORY_SEPARATOR;
        if (is_dir($legacyDir)) {
            return $legacyDir;
        }
    }
    return '';
}
