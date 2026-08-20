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
    "\t\t\t$_REQUEST['cat_url'] = urlencode($_REQUEST['cat_url']);",
    "\t\t\t$_REQUEST['cat_url'] = urlencode((string) DOCUMENTS_requestValue($_REQUEST, 'cat_url', ''));\n\t\t\t$_REQUEST['cat_name'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'cat_name', ''));\n\t\t\t$_REQUEST['css'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'css', ''));\n\t\t\t$_REQUEST['template'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'template', ''));\n\t\t\t$_REQUEST['cat_help'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'cat_help', ''));\n\t\t\t$_REQUEST['custom_header'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'custom_header', ''));\n\t\t\t$_REQUEST['custom_footer'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'custom_footer', ''));",
    'category strings'
)
replace_once(
    "\t\t\tif (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {\n\t\t\t\tlist($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']) \n\t\t\t\t= SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);\n\t\t    }",
    "\t\t\tlist($_REQUEST['perm_owner'], $_REQUEST['perm_group'], $_REQUEST['perm_members'], $_REQUEST['perm_anon']) =\n\t\t\t    DOCUMENTS_requestPermissions($_REQUEST, array(3, 3, 2, 2));",
    'category permissions'
)
replace_once(
    "\t\t\tif ( (!empty($_REQUEST['cid'])) && (is_numeric($_REQUEST['cid'])) ) {",
    "\t\t\t$_REQUEST['cid'] = DOCUMENTS_requestInt($_REQUEST, 'cid', 0);\n\t\t\t$_REQUEST['cat_order'] = DOCUMENTS_requestInt($_REQUEST, 'cat_order', 0);\n\t\t\t$_REQUEST['map'] = DOCUMENTS_requestInt($_REQUEST, 'map', 0);\n\t\t\t$_REQUEST['list_index'] = DOCUMENTS_requestInt($_REQUEST, 'list_index', 0);\n\t\t\t$_REQUEST['submitable'] = DOCUMENTS_requestInt($_REQUEST, 'submitable', 0);\n\t\t\t$_REQUEST['owner_id'] = DOCUMENTS_requestInt($_REQUEST, 'owner_id', 0);\n\t\t\t$_REQUEST['group_id'] = DOCUMENTS_requestInt($_REQUEST, 'group_id', 0);\n\n\t\t\tif ($_REQUEST['cid'] > 0) {",
    'category numerics'
)

if text == original:
    raise RuntimeError('No changes applied')
path.write_text(text, encoding='utf-8')
