from pathlib import Path

path = Path('include_html.php')
text = path.read_text(encoding='utf-8')
original = text


def replace_once(old, new, label):
    global text
    count = text.count(old)
    if count != 1:
        raise RuntimeError('%s: expected 1 occurrence, found %d' % (label, count))
    text = text.replace(old, new, 1)


replace_once(
    "            if ($creation == 1) {\n\n   \t\t\t// Submission\n\t\t\t\t\t\n\t\t\t\tif (SEC_hasRights('documents.admin') || SEC_hasRights('documents.publish')) {\n\t\t\t\t\t$active = $_REQUEST['active'];",
    "            if ($creation == 1) {\n\n   \t\t\t// Submission\n\t\t\t\t\t\n\t\t\t\t$active = DOCUMENTS_requestInt($_REQUEST, 'active', DOCUMENTS_STATUS_SUBMISSION);\n\t\t\t\tif (SEC_hasRights('documents.admin') || SEC_hasRights('documents.publish')) {",
    'create row active'
)

replace_once(
    "\t\t\t\t\tif (!SEC_hasRights('documents.admin')) {\n\t\t\t\t\t    ($_REQUEST['active'] == 2) ? $active = 2 : $active = 1;",
    "\t\t\t\t\tif (!SEC_hasRights('documents.admin')) {\n\t\t\t\t\t    $active = ($active == DOCUMENTS_STATUS_DRAFT) ? DOCUMENTS_STATUS_DRAFT : DOCUMENTS_STATUS_ACTIVE;",
    'publisher active'
)

replace_once(
    "\t\t\t\t//Get default permissions\n\t\t\t\tif ($_REQUEST['perm_owner'] == '') {\n\t\t\t\t\tSEC_setDefaultPermissions($_REQUEST, $_DOCUMENTS_CONF['default_permissions']);\n\t\t\t\t}\n\t\t\t\t\t\n\t\t\t\t$sql = \"active='$active', \"",
    "\t\t\t\t//Get default permissions\n\t\t\t\tif (DOCUMENTS_requestValue($_REQUEST, 'perm_owner', '') === '') {\n\t\t\t\t\tSEC_setDefaultPermissions($_REQUEST, $_DOCUMENTS_CONF['default_permissions']);\n\t\t\t\t}\n\t\t\t\tlist($docPermOwner, $docPermGroup, $docPermMembers, $docPermAnon) = DOCUMENTS_requestPermissions($_REQUEST, array(3, 3, 2, 2));\n\t\t\t\t\t\n\t\t\t\t$sql = \"active='$active', \"",
    'create row permissions setup'
)

replace_once(
    "\t\t\t\t\t. \"perm_owner = '{$_REQUEST['perm_owner']}', \"\n\t\t\t\t\t. \"perm_group = '{$_REQUEST['perm_group']}', \"\n\t\t\t\t\t. \"perm_members = '{$_REQUEST['perm_members']}', \"\n\t\t\t\t\t. \"perm_anon = '{$_REQUEST['perm_anon']}'",
    "\t\t\t\t\t. \"perm_owner = '{$docPermOwner}', \"\n\t\t\t\t\t. \"perm_group = '{$docPermGroup}', \"\n\t\t\t\t\t. \"perm_members = '{$docPermMembers}', \"\n\t\t\t\t\t. \"perm_anon = '{$docPermAnon}'",
    'create row permission values'
)

replace_once(
    "\t\t\t    //Edition\n\t\t\t\t$active = $_REQUEST['active'];",
    "\t\t\t    //Edition\n\t\t\t\t$active = DOCUMENTS_requestInt($_REQUEST, 'active', DOCUMENTS_STATUS_ACTIVE);",
    'edit row active'
)

replace_once(
    "\t\t\t\t$sql = \"UPDATE {$_TABLES['documents_docs']} SET $sql \"\n\t\t\t\t . \"WHERE doc_url='{$_REQUEST['doc_url']}' \";",
    "\t\t\t\t$saveDocUrl = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'doc_url', ''));\n\t\t\t\tif ($saveDocUrl === '') {\n\t\t\t\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t\t\t\texit();\n\t\t\t\t}\n\t\t\t\t$sql = \"UPDATE {$_TABLES['documents_docs']} SET $sql \"\n\t\t\t\t . \"WHERE doc_url='{$saveDocUrl}' \";",
    'edit row doc url'
)

if text == original:
    raise RuntimeError('No changes applied')
path.write_text(text, encoding='utf-8')