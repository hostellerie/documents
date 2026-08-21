<?php

/* Documents 1.1.9 document visibility tests. */

$_SERVER['PHP_SELF'] = 'tests/document_visibility_test.php';
$_USER = array('uid' => 42);

$GLOBALS['documents_test_admin'] = false;
$GLOBALS['documents_test_access'] = 2;

function SEC_hasRights($right)
{
    return $right === 'documents.admin' && !empty($GLOBALS['documents_test_admin']);
}

function SEC_hasAccess($owner, $group, $ownerPerm, $groupPerm, $memberPerm, $anonPerm)
{
    return (int) $GLOBALS['documents_test_access'];
}

function SEC_getPermissionValues($owner, $group, $members, $anon)
{
    return array($owner, $group, $members, $anon);
}

require dirname(__DIR__) . '/include_compat.php';

function documentsVisibilityRow($status, $owner)
{
    return array(
        'active' => $status,
        'owner_id' => $owner,
        'group_id' => 2,
        'perm_owner' => 3,
        'perm_group' => 2,
        'perm_members' => 2,
        'perm_anon' => 2
    );
}

function documentsAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$active = documentsVisibilityRow(DOCUMENTS_STATUS_ACTIVE, 99);
documentsAssert(DOCUMENTS_canViewDocument($active, 2), 'Readable active document was rejected.');

$GLOBALS['documents_test_access'] = 1;
documentsAssert(!DOCUMENTS_canViewDocument($active, 2), 'Active document ignored Geeklog row permissions.');
$GLOBALS['documents_test_access'] = 2;

$inactive = documentsVisibilityRow(DOCUMENTS_STATUS_INACTIVE, 42);
documentsAssert(!DOCUMENTS_canViewDocument($inactive, 2), 'Non-admin user can view an inactive document.');

$draftOwn = documentsVisibilityRow(DOCUMENTS_STATUS_DRAFT, 42);
documentsAssert(DOCUMENTS_canViewDocument($draftOwn, 2), 'Draft owner cannot reach own draft.');

$draftOther = documentsVisibilityRow(DOCUMENTS_STATUS_DRAFT, 99);
documentsAssert(!DOCUMENTS_canViewDocument($draftOther, 2), 'User can view another user\'s draft.');

$submissionOwn = documentsVisibilityRow(DOCUMENTS_STATUS_SUBMISSION, 42);
documentsAssert(DOCUMENTS_canViewDocument($submissionOwn, 2), 'Submission owner cannot reach own submission route.');

$submissionOther = documentsVisibilityRow(DOCUMENTS_STATUS_SUBMISSION, 99);
documentsAssert(!DOCUMENTS_canViewDocument($submissionOther, 2), 'User can reach another user\'s submission.');

$GLOBALS['documents_test_admin'] = true;
documentsAssert(DOCUMENTS_canViewDocument($inactive, 2), 'Administrator cannot view inactive document.');
documentsAssert(DOCUMENTS_canViewDocument($draftOther, 2), 'Administrator cannot review another user\'s draft.');
documentsAssert(DOCUMENTS_canViewDocument($submissionOther, 2), 'Administrator cannot review another user\'s submission.');

$invalid = documentsVisibilityRow(9, 42);
documentsAssert(!DOCUMENTS_canViewDocument($invalid, 2), 'Unknown document status was accepted.');
documentsAssert(!DOCUMENTS_canViewDocument(array(), 2), 'Incomplete document row was accepted.');

echo "Documents document visibility checks: PASS\n";
