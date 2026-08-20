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
    "\t\t\t\t\t\t$doc_url = $_POST[$A['var_name']];\n\t\t\t\t\t\t$doc_url = DOCUMENTS_slugify($doc_url);\n\t\t\t\t\t\tdefine(\"DOC_URL\", $unique . '-' . strtolower($doc_url));",
    "\t\t\t\t\t\t$titleValue = DOCUMENTS_requestValue($_REQUEST, $A['var_name'], '');\n\t\t\t\t\t\tif (is_array($titleValue)) {\n\t\t\t\t\t\t\t$titleValue = '';\n\t\t\t\t\t\t}\n\t\t\t\t\t\t$doc_url = DOCUMENTS_slugify((string) $titleValue);\n\t\t\t\t\t\tif ($doc_url === '') {\n\t\t\t\t\t\t\t$doc_url = 'document';\n\t\t\t\t\t\t}\n\t\t\t\t\t\tdefine(\"DOC_URL\", $unique . '-' . strtolower($doc_url));",
    'creation slug source'
)

replace_once(
    "\t\t\t\t\t// Default fields\n\t\t\t\t\tif ($_REQUEST['perm_owner'] == '') {\n\t\t\t\t\t\tSEC_setDefaultPermissions($_REQUEST, $_DOCUMENTS_CONF['default_permissions']);\n\t\t\t\t\t}\n\t\t\t\t\t\n\t\t\t\t\t// Convert array values to numeric permission values\n\t\t\t\t\tif (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {\n\t\t\t\t\t\tlist($perm_owner, $perm_group, $perm_members, $perm_anon) \n\t\t\t\t\t\t= SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);\n\t\t\t\t\t}",
    "\t\t\t\t\t// Default fields and permissions\n\t\t\t\t\tif (DOCUMENTS_requestValue($_REQUEST, 'perm_owner', '') === '') {\n\t\t\t\t\t\tSEC_setDefaultPermissions($_REQUEST, $_DOCUMENTS_CONF['default_permissions']);\n\t\t\t\t\t}\n\t\t\t\t\tlist($perm_owner, $perm_group, $perm_members, $perm_anon) = DOCUMENTS_requestPermissions($_REQUEST, array(3, 3, 2, 2));",
    'creation permissions'
)

replace_once(
    "\t\t\t\t\t\tif($A['f_type'] == 'checkbox') {\n\t\t\t\t\t\t    ($_REQUEST[$A['var_name']] == 1 ) ? $value = 1 : $value = 0;",
    "\t\t\t\t\t\tif($A['f_type'] == 'checkbox') {\n\t\t\t\t\t\t    $value = ((int) DOCUMENTS_requestValue($_REQUEST, $A['var_name'], 0) === 1) ? 1 : 0;",
    'creation checkbox value'
)

replace_once(
    "\t\t\t\t\t\t        $value = addslashes($_REQUEST[$A['var_name']]);",
    "\t\t\t\t\t\t        $createValue = DOCUMENTS_requestValue($_REQUEST, $A['var_name'], '');\n\t\t\t\t\t\t        if (is_array($createValue)) {\n\t\t\t\t\t\t            $createValue = '';\n\t\t\t\t\t\t        }\n\t\t\t\t\t\t        $value = addslashes((string) $createValue);",
    'creation field value'
)

if text == original:
    raise RuntimeError('No changes applied')
path.write_text(text, encoding='utf-8')