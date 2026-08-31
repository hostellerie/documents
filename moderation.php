<?php

/* Geeklog moderation bridge for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF'])
    && strpos(strtolower((string) $_SERVER['PHP_SELF']), '/plugins/documents/moderation.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_canModerateSubmissions()
{
    return SEC_hasRights('documents.admin');
}

function plugin_ismoderator_documents()
{
    /* Geeklog 2.1.1 has a core SEC_hasModerationAccess() regression: when
     * PLG_isModerator() is true it resets access to false. Do not trigger that
     * regression; Root/core moderators still see the Documents list through
     * PLG_showModerationList(). Later Geeklog versions use the normal callback. */
    if (defined('VERSION') && version_compare(VERSION, '2.1.2', '<')) {
        return false;
    }

    return DOCUMENTS_canModerateSubmissions();
}

function plugin_submissioncount_documents()
{
    global $_TABLES;

    if (!DOCUMENTS_canModerateSubmissions()) {
        return 0;
    }

    return (int) DB_count($_TABLES['documents_docs'], 'active', DOCUMENTS_STATUS_SUBMISSION);
}

function plugin_itemlist_documents()
{
    global $_TABLES, $_CONF;

    if (!DOCUMENTS_canModerateSubmissions()) {
        return;
    }

    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

    $plugin = new Plugin();
    $plugin->submissionlabel = $isFrench ? 'Documents en attente' : 'Pending documents';
    $plugin->submissionhelpfile = '';
    $plugin->getsubmissionssql =
        "SELECT d.doc_url AS id, "
        . "COALESCE(NULLIF((SELECT v.v_value FROM {$_TABLES['documents_values']} AS v "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "WHERE v.doc_url=d.doc_url ORDER BY f.f_order ASC, v.vid ASC LIMIT 1), ''), d.doc_url) AS title, "
        . "COALESCE((SELECT c.cat_name FROM {$_TABLES['documents_values']} AS cv "
        . "INNER JOIN {$_TABLES['documents_fields']} AS cf ON cf.fid=cv.field_id "
        . "INNER JOIN {$_TABLES['documents_cat']} AS c ON c.cid=cf.cat_id "
        . "WHERE cv.doc_url=d.doc_url ORDER BY cf.f_order ASC, cv.vid ASC LIMIT 1), '') AS category, "
        . "COALESCE(u.username, '') AS submitter "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "LEFT JOIN {$_TABLES['users']} AS u ON u.uid=d.owner_id "
        . "WHERE d.active=" . (int) DOCUMENTS_STATUS_SUBMISSION . " "
        . "ORDER BY d.created ASC, d.did ASC";

    $plugin->addSubmissionHeading($isFrench ? 'Document' : 'Document');
    $plugin->addSubmissionHeading($isFrench ? 'Catégorie' : 'Category');
    $plugin->addSubmissionHeading($isFrench ? 'Auteur' : 'Submitter');

    return $plugin;
}

function plugin_moderationvalues_documents()
{
    global $_TABLES;

    return array(
        'doc_url',
        $_TABLES['documents_docs'],
        'doc_url,active,created,modified,hits,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon',
        $_TABLES['documents_docs']
    );
}

function plugin_moderationapprove_documents($id)
{
    global $_CONF, $_TABLES;

    if (!DOCUMENTS_canModerateSubmissions()) {
        return '';
    }

    $id = trim((string) $id);
    if ($id === '') {
        return '';
    }

    $safeId = DB_escapeString($id);
    $before = (int) DB_getItem($_TABLES['documents_docs'], 'active', "doc_url='{$safeId}'");
    if ($before !== DOCUMENTS_STATUS_SUBMISSION) {
        return '';
    }

    DB_query(
        "UPDATE {$_TABLES['documents_docs']} SET active=" . (int) DOCUMENTS_STATUS_ACTIVE
        . ", modified=NOW() WHERE doc_url='{$safeId}' AND active=" . (int) DOCUMENTS_STATUS_SUBMISSION
    );
    if (DB_error()) {
        return '';
    }

    require_once $_CONF['path'] . 'plugins/documents/indexability.php';
    $isPublic = DOCUMENTS_isPubliclyIndexable($id);
    DOCUMENTS_notifyPublicTransition($id, false, $isPublic);
    COM_rdfUpToDateCheck('documents', '', $id);

    return '';
}

function plugin_moderationdelete_documents($id)
{
    global $_CONF;

    if (!DOCUMENTS_canModerateSubmissions()) {
        return '';
    }

    $pluginPath = $_CONF['path'] . 'plugins/documents/';
    require_once $pluginPath . 'security.php';
    require_once $pluginPath . 'include_compat.php';
    require_once $pluginPath . 'indexability.php';
    require_once $pluginPath . 'document_images.php';
    require_once $pluginPath . 'document_mutations.php';
    require_once $pluginPath . 'maps_adapter.php';
    require_once $pluginPath . 'document_delete.php';

    DOCUMENTS_deleteDocumentSecure($id);
    return '';
}
