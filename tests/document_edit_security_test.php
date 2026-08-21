<?php

/* Documents 1.1.9 document editing and workflow security tests. */

$_SERVER['PHP_SELF'] = 'tests/document_edit_security_test.php';
$_USER = array('uid' => 10);
$DOCUMENTS_TEST_RIGHTS = array();
$DOCUMENTS_TEST_ACCESS = 3;

function SEC_hasRights($right)
{
    global $DOCUMENTS_TEST_RIGHTS;
    return in_array($right, $DOCUMENTS_TEST_RIGHTS, true);
}

function SEC_hasAccess($owner, $group, $permOwner, $permGroup, $permMembers, $permAnon)
{
    global $DOCUMENTS_TEST_ACCESS;
    return $DOCUMENTS_TEST_ACCESS;
}

require_once dirname(__DIR__) . '/include_compat.php';

$failures = array();

function DOCUMENTS_testAssert($condition, $message, &$failures)
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function DOCUMENTS_testRow($status, $owner)
{
    return array(
        'active' => $status,
        'owner_id' => $owner,
        'group_id' => 2,
        'perm_owner' => 3,
        'perm_group' => 3,
        'perm_members' => 3,
        'perm_anon' => 0
    );
}

/* Normal user: private workflow rows must remain owner-only. */
$DOCUMENTS_TEST_RIGHTS = array();
$DOCUMENTS_TEST_ACCESS = 3;
$_USER['uid'] = 10;
DOCUMENTS_testAssert(
    DOCUMENTS_canEditDocument(DOCUMENTS_testRow(DOCUMENTS_STATUS_DRAFT, 20)) === false,
    'A non-owner can edit another user draft when row permissions are writable.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_canEditDocument(DOCUMENTS_testRow(DOCUMENTS_STATUS_SUBMISSION, 20)) === false,
    'A non-owner can edit another user submission when row permissions are writable.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_canEditDocument(DOCUMENTS_testRow(DOCUMENTS_STATUS_DRAFT, 10)) === true,
    'The draft owner cannot edit their own writable draft.',
    $failures
);

/* Creation: normal users can draft or submit, never self-publish. */
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(DOCUMENTS_STATUS_ACTIVE, null) === DOCUMENTS_STATUS_SUBMISSION,
    'A normal user can self-publish a new document.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(DOCUMENTS_STATUS_INACTIVE, null) === DOCUMENTS_STATUS_SUBMISSION,
    'A normal user can create an inactive workflow row instead of a submission.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(DOCUMENTS_STATUS_DRAFT, null) === DOCUMENTS_STATUS_DRAFT,
    'A normal user cannot save a new draft.',
    $failures
);

/* Editing a private workflow row cannot promote it to active without publish rights. */
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(
        DOCUMENTS_STATUS_ACTIVE,
        DOCUMENTS_STATUS_DRAFT
    ) === DOCUMENTS_STATUS_SUBMISSION,
    'A normal user can publish their draft by forging active=1.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(
        DOCUMENTS_STATUS_ACTIVE,
        DOCUMENTS_STATUS_SUBMISSION
    ) === DOCUMENTS_STATUS_SUBMISSION,
    'A normal user can publish their submission by forging active=1.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(
        DOCUMENTS_STATUS_DRAFT,
        DOCUMENTS_STATUS_SUBMISSION
    ) === DOCUMENTS_STATUS_DRAFT,
    'A normal user cannot move their submission back to draft.',
    $failures
);

/* Existing active documents preserve the historical owner edit workflow. */
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(
        DOCUMENTS_STATUS_ACTIVE,
        DOCUMENTS_STATUS_ACTIVE
    ) === DOCUMENTS_STATUS_ACTIVE,
    'A normal user cannot keep an editable active document active.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(
        DOCUMENTS_STATUS_DRAFT,
        DOCUMENTS_STATUS_ACTIVE
    ) === DOCUMENTS_STATUS_DRAFT,
    'A normal user cannot move an editable active document to draft.',
    $failures
);

/* Publishers may publish or draft, but do not bypass private ownership checks. */
$DOCUMENTS_TEST_RIGHTS = array('documents.publish');
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(DOCUMENTS_STATUS_ACTIVE, null) === DOCUMENTS_STATUS_ACTIVE,
    'A publisher cannot publish a new document.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(DOCUMENTS_STATUS_SUBMISSION, null) === DOCUMENTS_STATUS_ACTIVE,
    'Publisher workflow does not normalize non-draft states to active.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_canEditDocument(DOCUMENTS_testRow(DOCUMENTS_STATUS_SUBMISSION, 20)) === false,
    'Publish rights alone allow editing another user private submission.',
    $failures
);

/* Documents administrators retain full workflow control. */
$DOCUMENTS_TEST_RIGHTS = array('documents.admin');
DOCUMENTS_testAssert(
    DOCUMENTS_canEditDocument(DOCUMENTS_testRow(DOCUMENTS_STATUS_SUBMISSION, 20)) === true,
    'Documents administrator cannot edit a submission.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(DOCUMENTS_STATUS_INACTIVE, null) === DOCUMENTS_STATUS_INACTIVE,
    'Documents administrator cannot create an inactive document.',
    $failures
);
DOCUMENTS_testAssert(
    DOCUMENTS_normalizeDocumentStatus(DOCUMENTS_STATUS_SUBMISSION, DOCUMENTS_STATUS_ACTIVE)
        === DOCUMENTS_STATUS_SUBMISSION,
    'Documents administrator cannot explicitly set submission state.',
    $failures
);

/* Standard permissions must still be required for non-admin edits. */
$DOCUMENTS_TEST_RIGHTS = array();
$DOCUMENTS_TEST_ACCESS = 2;
$_USER['uid'] = 10;
DOCUMENTS_testAssert(
    DOCUMENTS_canEditDocument(DOCUMENTS_testRow(DOCUMENTS_STATUS_ACTIVE, 10)) === false,
    'Read-only access is sufficient to edit an active document.',
    $failures
);

if (!empty($failures)) {
    fwrite(STDERR, "Documents edit security tests failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    exit(1);
}

echo "Documents edit security tests: PASS\n";
