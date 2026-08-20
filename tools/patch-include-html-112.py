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

# Category save normalization.
replace_once(
    "\t\t\t$_REQUEST['cat_url'] = urlencode($_REQUEST['cat_url']);",
    "\t\t\t$_REQUEST['cat_url'] = urlencode((string) DOCUMENTS_requestValue($_REQUEST, 'cat_url', ''));\n\t\t\t$_REQUEST['cat_name'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'cat_name', ''));\n\t\t\t$_REQUEST['css'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'css', ''));\n\t\t\t$_REQUEST['template'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'template', ''));\n\t\t\t$_REQUEST['cat_help'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'cat_help', ''));\n\t\t\t$_REQUEST['custom_header'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'custom_header', ''));\n\t\t\t$_REQUEST['custom_footer'] = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'custom_footer', ''));",
    'category string normalization'
)
replace_once(
    "\t\t\tif (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {\n\t\t\t\tlist($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']) \n\t\t\t\t= SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);\n\t\t    }",
    "\t\t\tlist($_REQUEST['perm_owner'], $_REQUEST['perm_group'], $_REQUEST['perm_members'], $_REQUEST['perm_anon']) =\n\t\t\t    DOCUMENTS_requestPermissions($_REQUEST, array(3, 3, 2, 2));",
    'category permissions'
)
replace_once(
    "\t\t\tif ( (!empty($_REQUEST['cid'])) && (is_numeric($_REQUEST['cid'])) ) {",
    "\t\t\t$_REQUEST['cid'] = DOCUMENTS_requestInt($_REQUEST, 'cid', 0);\n\t\t\t$_REQUEST['cat_order'] = DOCUMENTS_requestInt($_REQUEST, 'cat_order', 0);\n\t\t\t$_REQUEST['map'] = DOCUMENTS_requestInt($_REQUEST, 'map', 0);\n\t\t\t$_REQUEST['list_index'] = DOCUMENTS_requestInt($_REQUEST, 'list_index', 0);\n\t\t\t$_REQUEST['submitable'] = DOCUMENTS_requestInt($_REQUEST, 'submitable', 0);\n\t\t\t$_REQUEST['owner_id'] = DOCUMENTS_requestInt($_REQUEST, 'owner_id', 0);\n\t\t\t$_REQUEST['group_id'] = DOCUMENTS_requestInt($_REQUEST, 'group_id', 0);\n\n\t\t\tif ($_REQUEST['cid'] > 0) {",
    'category numeric values'
)

# Field save normalization.
replace_once(
    "\t\t\t$new = -1;\n\t\t\tif ($_REQUEST['op'] == 'delete' && !empty($_REQUEST['fid']) && is_numeric($_REQUEST['fid'])) {",
    "\t\t\t$new = -1;\n\t\t\t$saveFieldId = DOCUMENTS_requestInt($_REQUEST, 'fid', 0);\n\t\t\t$saveFieldCatId = DOCUMENTS_requestInt($_REQUEST, 'cat_id', 0);\n\t\t\tif (DOCUMENTS_requestValue($_REQUEST, 'op') === 'delete' && $saveFieldId > 0) {",
    'field ids'
)
replace_once(
    "DB_query (\"DELETE FROM {$_TABLES['documents_fields']} WHERE fid = \". $_REQUEST['fid']);",
    "DB_query (\"DELETE FROM {$_TABLES['documents_fields']} WHERE fid = \" . $saveFieldId);",
    'field delete metadata'
)
replace_once(
    "DB_query (\"DELETE FROM {$_TABLES['documents_values']} WHERE field_id = \". $_REQUEST['fid']);",
    "DB_query (\"DELETE FROM {$_TABLES['documents_values']} WHERE field_id = \" . $saveFieldId);",
    'field delete values'
)
replace_once(
    "\t\t\t\tif (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {\n\t\t\t\t\tlist($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']) \n\t\t\t\t\t= SEC_getPermissionValues($_REQUEST['perm_owner'],$_REQUEST['perm_group'],$_REQUEST['perm_members'],$_REQUEST['perm_anon']);\n\t\t\t\t}",
    "\t\t\t\tlist($_REQUEST['perm_owner'], $_REQUEST['perm_group'], $_REQUEST['perm_members'], $_REQUEST['perm_anon']) =\n\t\t\t\t    DOCUMENTS_requestPermissions($_REQUEST, array(3, 3, 2, 2));",
    'field permissions'
)
replace_once(
    "\t\t\t\tif ( (!empty($_REQUEST['fid'])) && (is_numeric($_REQUEST['fid'])) ) {",
    "\t\t\t\t$_REQUEST['fid'] = $saveFieldId;\n\t\t\t\t$_REQUEST['cat_id'] = $saveFieldCatId;\n\t\t\t\t$_REQUEST['f_order'] = DOCUMENTS_requestInt($_REQUEST, 'f_order', 0);\n\t\t\t\t$_REQUEST['sel_id'] = DOCUMENTS_requestInt($_REQUEST, 'sel_id', 0);\n\t\t\t\t$_REQUEST['f_required'] = DOCUMENTS_requestInt($_REQUEST, 'f_required', 0);\n\t\t\t\t$_REQUEST['f_on_list'] = DOCUMENTS_requestInt($_REQUEST, 'f_on_list', 0);\n\t\t\t\t$_REQUEST['owner_id'] = DOCUMENTS_requestInt($_REQUEST, 'owner_id', 0);\n\t\t\t\t$_REQUEST['group_id'] = DOCUMENTS_requestInt($_REQUEST, 'group_id', 0);\n\n\t\t\t\tif ($saveFieldId > 0) {",
    'field numeric values'
)
replace_once(
    "DOCUMENTS_reorderFields($_REQUEST['cat_id']);",
    "DOCUMENTS_reorderFields($saveFieldCatId);",
    'field reorder category'
)

if text == original:
    raise RuntimeError('No changes applied')
path.write_text(text, encoding='utf-8')
