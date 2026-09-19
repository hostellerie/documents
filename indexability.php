<?php

/* Public indexability helpers for Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'indexability.php') !== false) {
    die('This file can not be used on its own.');
}

/**
 * Return whether a document is genuinely public for anonymous visitors.
 *
 * Active state alone is not sufficient: both the document and its category
 * must grant read access to Geeklog anonymous user id 1.
 */
function DOCUMENTS_isPubliclyIndexable($documentId)
{
    global $_TABLES;

    $documentId = trim((string) $documentId);
    if ($documentId === '') {
        return false;
    }

    $safeId = DB_escapeString($documentId);
    $row = DB_fetchArray(DB_query(
        "SELECT d.active, d.owner_id AS d_owner_id, d.group_id AS d_group_id, "
        . "d.perm_owner AS d_perm_owner, d.perm_group AS d_perm_group, "
        . "d.perm_members AS d_perm_members, d.perm_anon AS d_perm_anon, "
        . "c.owner_id AS c_owner_id, c.group_id AS c_group_id, "
        . "c.perm_owner AS c_perm_owner, c.perm_group AS c_perm_group, "
        . "c.perm_members AS c_perm_members, c.perm_anon AS c_perm_anon "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "INNER JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE d.doc_url='{$safeId}' ORDER BY f.f_order ASC, f.fid ASC LIMIT 1"
    ));

    if (!is_array($row) || (int) $row['active'] !== DOCUMENTS_STATUS_ACTIVE) {
        return false;
    }

    $anonymousUid = 1;
    $documentAccess = SEC_hasAccess(
        (int) $row['d_owner_id'],
        (int) $row['d_group_id'],
        (int) $row['d_perm_owner'],
        (int) $row['d_perm_group'],
        (int) $row['d_perm_members'],
        (int) $row['d_perm_anon'],
        $anonymousUid
    );
    if ($documentAccess < 2) {
        return false;
    }

    $categoryAccess = SEC_hasAccess(
        (int) $row['c_owner_id'],
        (int) $row['c_group_id'],
        (int) $row['c_perm_owner'],
        (int) $row['c_perm_group'],
        (int) $row['c_perm_members'],
        (int) $row['c_perm_anon'],
        $anonymousUid
    );

    return $categoryAccess >= 2;
}

/**
 * Emit lifecycle events only for transitions that affect public indexing.
 */
function DOCUMENTS_notifyPublicTransition($documentId, $wasPublic, $isPublic)
{
    $documentId = trim((string) $documentId);
    if ($documentId === '') {
        return;
    }

    $wasPublic = (bool) $wasPublic;
    $isPublic = (bool) $isPublic;

    if ($isPublic) {
        if (function_exists('PLG_itemSaved')) {
            PLG_itemSaved($documentId, 'documents');
        }
        return;
    }

    if ($wasPublic && function_exists('PLG_itemDeleted')) {
        PLG_itemDeleted($documentId, 'documents');
    }
}
