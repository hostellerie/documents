<?php

/* Documents 1.2.0 document visibility tests. */

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

/* Editorial status does not decide read visibility in 1.2.0. Geeklog row
 * permissions are the authority for every recognized workflow state. */
$rows = array(
    documentsVisibilityRow(DOCUMENTS_STATUS_INACTIVE, 42),
    documentsVisibilityRow(DOCUMENTS_STATUS_ACTIVE, 99),
    documentsVisibilityRow(DOCUMENTS_STATUS_DRAFT, 42),
    documentsVisibilityRow(DOCUMENTS_STATUS_DRAFT, 99),
    documentsVisibilityRow(DOCUMENTS_STATUS_SUBMISSION, 42),
    documentsVisibilityRow(DOCUMENTS_STATUS_SUBMISSION, 99)
);

foreach ($rows as $row) {
    $GLOBALS['documents_test_access'] = 2;
    documentsAssert(
        DOCUMENTS_canViewDocument($row, 2),
        'Readable document was rejected because of its editorial status.'
    );

    $GLOBALS['documents_test_access'] = 1;
    documentsAssert(
        !DOCUMENTS_canViewDocument($row, 2),
        'Document ignored Geeklog row permissions.'
    );
}

$GLOBALS['documents_test_access'] = 2;
$GLOBALS['documents_test_admin'] = true;
documentsAssert(
    DOCUMENTS_canViewDocument(documentsVisibilityRow(DOCUMENTS_STATUS_SUBMISSION, 99), 2),
    'Administrator cannot review a permission-readable pending document.'
);

$invalid = documentsVisibilityRow(9, 42);
documentsAssert(!DOCUMENTS_canViewDocument($invalid, 2), 'Unknown document status was accepted.');
documentsAssert(!DOCUMENTS_canViewDocument(array(), 2), 'Incomplete document row was accepted.');

echo "Documents document visibility checks: PASS\n";
