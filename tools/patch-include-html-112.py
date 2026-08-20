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
    "\t\t\trequire_once ($_CONF['path']  . 'plugins/documents/include_edit.php');\n\t\t\t\n\t\t\t$missingfields = DOCUMENTS_missingFieldCat();",
    "\t\t\trequire_once ($_CONF['path']  . 'plugins/documents/include_edit.php');\n\n\t\t\t$catDefaults = array(\n\t\t\t\t'cid' => 0, 'cat_name' => '', 'cat_url' => '', 'cat_order' => 0, 'css' => '',\n\t\t\t\t'map' => 0, 'template' => '', 'list_index' => 0, 'submitable' => 0,\n\t\t\t\t'cat_help' => '', 'custom_header' => '', 'custom_footer' => '',\n\t\t\t\t'owner_id' => 0, 'group_id' => 0, 'perm_owner' => '', 'perm_group' => '',\n\t\t\t\t'perm_members' => '', 'perm_anon' => ''\n\t\t\t);\n\t\t\tforeach ($catDefaults as $key => $default) {\n\t\t\t\tif (!isset($_REQUEST[$key])) {\n\t\t\t\t\t$_REQUEST[$key] = $default;\n\t\t\t\t}\n\t\t\t}\n\n\t\t\t$missingfields = DOCUMENTS_missingFieldCat();",
    'category defaults'
)
replace_once(
    "\t\t\tif (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {",
    "\t\t\tif (is_array($_REQUEST['perm_owner']) || is_array($_REQUEST['perm_group']) || is_array($_REQUEST['perm_members']) || is_array($_REQUEST['perm_anon'])) {",
    'category permissions'
)
replace_once(
    "\t\t\tif ( (!empty($_REQUEST['cid'])) && (is_numeric($_REQUEST['cid'])) ) {",
    "\t\t\t$_REQUEST['cid'] = (int) $_REQUEST['cid'];\n\t\t\t$_REQUEST['cat_order'] = (int) $_REQUEST['cat_order'];\n\t\t\t$_REQUEST['map'] = (int) $_REQUEST['map'];\n\t\t\t$_REQUEST['list_index'] = (int) $_REQUEST['list_index'];\n\t\t\t$_REQUEST['submitable'] = (int) $_REQUEST['submitable'];\n\t\t\t$_REQUEST['owner_id'] = (int) $_REQUEST['owner_id'];\n\t\t\t$_REQUEST['group_id'] = (int) $_REQUEST['group_id'];\n\n\t\t\tif ($_REQUEST['cid'] > 0) {",
    'category numerics'
)

if text == original:
    raise RuntimeError('No changes applied')
path.write_text(text, encoding='utf-8')
