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


# Guard edit identifiers before numeric checks/SQL.
replace_once(
    "\t\t\tif (is_numeric($_REQUEST['cat'])) {\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = {$_REQUEST['cat']}\";",
    "\t\t\t$catId = (int) DOCUMENTS_requestValue($_REQUEST, 'cat', 0);\n\t\t\tif ($catId > 0) {\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = {$catId}\";",
    'edit category id'
)
replace_once(
    "\t\t\tif (is_numeric($_REQUEST['field'])) {\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_fields']} WHERE fid = {$_REQUEST['field']}\";",
    "\t\t\t$fieldId = (int) DOCUMENTS_requestValue($_REQUEST, 'field', 0);\n\t\t\tif ($fieldId > 0) {\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_fields']} WHERE fid = {$fieldId}\";",
    'edit field id'
)
replace_once(
    "\t\t\tif (is_numeric($_REQUEST['group'])) {\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_selects_group']} WHERE gid = {$_REQUEST['group']}\";",
    "\t\t\t$groupId = (int) DOCUMENTS_requestValue($_REQUEST, 'group', 0);\n\t\t\tif ($groupId > 0) {\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_selects_group']} WHERE gid = {$groupId}\";",
    'edit group id'
)
replace_once(
    "\t\t\tif (is_numeric($_REQUEST['select'])) {\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_selects']} WHERE sid = {$_REQUEST['select']}\";",
    "\t\t\t$selectId = (int) DOCUMENTS_requestValue($_REQUEST, 'select', 0);\n\t\t\tif ($selectId > 0) {\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_selects']} WHERE sid = {$selectId}\";",
    'edit select id'
)

# Normalize save category request values before any direct access.
replace_once(
    "\t\t\trequire_once ($_CONF['path']  . 'plugins/documents/include_edit.php');\n\t\t\t\n\t\t\t$missingfields = DOCUMENTS_missingFieldCat();",
    "\t\t\trequire_once ($_CONF['path']  . 'plugins/documents/include_edit.php');\n\n\t\t\t$catDefaults = array(\n\t\t\t\t'cid' => 0, 'cat_name' => '', 'cat_url' => '', 'cat_order' => 0, 'css' => '',\n\t\t\t\t'map' => 0, 'template' => '', 'list_index' => 0, 'submitable' => 0,\n\t\t\t\t'cat_help' => '', 'custom_header' => '', 'custom_footer' => '',\n\t\t\t\t'owner_id' => 0, 'group_id' => 0, 'perm_owner' => '', 'perm_group' => '',\n\t\t\t\t'perm_members' => '', 'perm_anon' => ''\n\t\t\t);\n\t\t\tforeach ($catDefaults as $key => $default) {\n\t\t\t\tif (!isset($_REQUEST[$key])) {\n\t\t\t\t\t$_REQUEST[$key] = $default;\n\t\t\t\t}\n\t\t\t}\n\n\t\t\t$missingfields = DOCUMENTS_missingFieldCat();",
    'save category defaults'
)
replace_once(
    "\t\t\tif (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {",
    "\t\t\tif (is_array($_REQUEST['perm_owner']) || is_array($_REQUEST['perm_group']) || is_array($_REQUEST['perm_members']) || is_array($_REQUEST['perm_anon'])) {",
    'category permission arrays'
)
replace_once(
    "\t\t\tif ( (!empty($_REQUEST['cid'])) && (is_numeric($_REQUEST['cid'])) ) {",
    "\t\t\t$_REQUEST['cid'] = (int) $_REQUEST['cid'];\n\t\t\t$_REQUEST['cat_order'] = (int) $_REQUEST['cat_order'];\n\t\t\t$_REQUEST['map'] = (int) $_REQUEST['map'];\n\t\t\t$_REQUEST['list_index'] = (int) $_REQUEST['list_index'];\n\t\t\t$_REQUEST['submitable'] = (int) $_REQUEST['submitable'];\n\t\t\t$_REQUEST['owner_id'] = (int) $_REQUEST['owner_id'];\n\t\t\t$_REQUEST['group_id'] = (int) $_REQUEST['group_id'];\n\n\t\t\tif ($_REQUEST['cid'] > 0) {",
    'category numeric normalization'
)

# Normalize save field request values.
replace_once(
    "\t\t\t// Delete field\n\t\t\t$new = -1;\n\t\t\tif ($_REQUEST['op'] == 'delete' && !empty($_REQUEST['fid']) && is_numeric($_REQUEST['fid'])) {",
    "\t\t\t$fieldDefaults = array(\n\t\t\t\t'op' => '', 'fid' => 0, 'f_name' => '', 'cat_id' => 0, 'f_order' => 0,\n\t\t\t\t'f_type' => '', 'sel_id' => 0, 'var_name' => '', 'f_help' => '',\n\t\t\t\t'f_required' => 0, 'f_on_list' => 0, 'owner_id' => 0, 'group_id' => 0,\n\t\t\t\t'perm_owner' => '', 'perm_group' => '', 'perm_members' => '', 'perm_anon' => ''\n\t\t\t);\n\t\t\tforeach ($fieldDefaults as $key => $default) {\n\t\t\t\tif (!isset($_REQUEST[$key])) {\n\t\t\t\t\t$_REQUEST[$key] = $default;\n\t\t\t\t}\n\t\t\t}\n\n\t\t\t// Delete field\n\t\t\t$new = -1;\n\t\t\t$_REQUEST['fid'] = (int) $_REQUEST['fid'];\n\t\t\tif ($_REQUEST['op'] === 'delete' && $_REQUEST['fid'] > 0) {",
    'save field defaults'
)
replace_once(
    "\t\t\t    DB_query (\"DELETE FROM {$_TABLES['documents_fields']} WHERE fid = \". $_REQUEST['fid']);",
    "\t\t\t    DB_query(\"DELETE FROM {$_TABLES['documents_fields']} WHERE fid = \" . (int) $_REQUEST['fid']);",
    'delete field id 1'
)
replace_once(
    "\t\t\t    DB_query (\"DELETE FROM {$_TABLES['documents_values']} WHERE field_id = \". $_REQUEST['fid']);",
    "\t\t\t    DB_query(\"DELETE FROM {$_TABLES['documents_values']} WHERE field_id = \" . (int) $_REQUEST['fid']);",
    'delete field id 2'
)
replace_once(
    "\t\t\t\tif (is_array($_REQUEST['perm_owner']) OR is_array($_REQUEST['perm_group']) OR is_array($_REQUEST['perm_members']) OR is_array($_REQUEST['perm_anon'])) {",
    "\t\t\t\tif (is_array($_REQUEST['perm_owner']) || is_array($_REQUEST['perm_group']) || is_array($_REQUEST['perm_members']) || is_array($_REQUEST['perm_anon'])) {",
    'field permission arrays'
)
replace_once(
    "\t\t\t\tif ( (!empty($_REQUEST['fid'])) && (is_numeric($_REQUEST['fid'])) ) {",
    "\t\t\t\t$_REQUEST['cat_id'] = (int) $_REQUEST['cat_id'];\n\t\t\t\t$_REQUEST['f_order'] = (int) $_REQUEST['f_order'];\n\t\t\t\t$_REQUEST['sel_id'] = (int) $_REQUEST['sel_id'];\n\t\t\t\t$_REQUEST['f_required'] = (int) $_REQUEST['f_required'];\n\t\t\t\t$_REQUEST['f_on_list'] = (int) $_REQUEST['f_on_list'];\n\t\t\t\t$_REQUEST['owner_id'] = (int) $_REQUEST['owner_id'];\n\t\t\t\t$_REQUEST['group_id'] = (int) $_REQUEST['group_id'];\n\n\t\t\t\tif ($_REQUEST['fid'] > 0) {",
    'field numeric normalization'
)

# Normalize group save values.
replace_once(
    "\t\t\tif ($_REQUEST['group_name'] == '') {",
    "\t\t\t$groupName = (string) DOCUMENTS_requestValue($_REQUEST, 'group_name');\n\t\t\t$groupHelp = (string) DOCUMENTS_requestValue($_REQUEST, 'group_help');\n\t\t\t$gid = (int) DOCUMENTS_requestValue($_REQUEST, 'gid', 0);\n\n\t\t\tif ($groupName === '') {",
    'save group defaults'
)
replace_once(
    "\t\t\t$_REQUEST['group_name'] = addslashes($_REQUEST['group_name']);\t\n\t\t\t$_REQUEST['group_help'] = addslashes($_REQUEST['group_help']);\t\n\n\t\t\tif ( (!empty($_REQUEST['gid'])) && (is_numeric($_REQUEST['gid'])) ) {",
    "\t\t\t$_REQUEST['group_name'] = addslashes($groupName);\n\t\t\t$_REQUEST['group_help'] = addslashes($groupHelp);\n\t\t\t$_REQUEST['gid'] = $gid;\n\n\t\t\tif ($gid > 0) {",
    'save group normalization'
)

# Normalize select save values.
replace_once(
    "\t\t\tif ($_REQUEST['s_name'] == '') {",
    "\t\t\t$sName = (string) DOCUMENTS_requestValue($_REQUEST, 's_name');\n\t\t\t$sValue = (string) DOCUMENTS_requestValue($_REQUEST, 's_value');\n\t\t\t$sGroup = (int) DOCUMENTS_requestValue($_REQUEST, 's_group', 0);\n\t\t\t$sOrder = (int) DOCUMENTS_requestValue($_REQUEST, 's_order', 0);\n\t\t\t$sid = (int) DOCUMENTS_requestValue($_REQUEST, 'sid', 0);\n\n\t\t\tif ($sName === '') {",
    'save select defaults'
)
replace_once(
    "\t\t\t$_REQUEST['s_name'] = addslashes($_REQUEST['s_name']);\t\n\t\t\t$_REQUEST['s_value'] = addslashes($_REQUEST['s_value']);\t\n\n\t\t\tif ( (!empty($_REQUEST['sid'])) && (is_numeric($_REQUEST['sid'])) ) {",
    "\t\t\t$_REQUEST['s_name'] = addslashes($sName);\n\t\t\t$_REQUEST['s_value'] = addslashes($sValue);\n\t\t\t$_REQUEST['s_group'] = $sGroup;\n\t\t\t$_REQUEST['s_order'] = $sOrder;\n\t\t\t$_REQUEST['sid'] = $sid;\n\n\t\t\tif ($sid > 0) {",
    'save select normalization'
)

# Safe list-selects and new-category entry.
replace_once(
    "\t    $content .= DOCUMENTS_listSelects($_REQUEST['group']);",
    "\t    $content .= DOCUMENTS_listSelects((int) DOCUMENTS_requestValue($_REQUEST, 'group', 0));",
    'list selects group'
)
replace_once(
    "\t\t} else if (isset($_GET['cat']) && $_REQUEST['cat'] !='') {\n\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url = '{$_REQUEST['cat']}'\";\n\t\t\t$res = DB_query($sql);\n\t\t\t$cat = DB_fetchArray($res);\n\t\t\tif ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {",
    "\t\t} else {\n\t\t\t$newCat = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'cat'));\n\t\t\tif ($newCat === '') {\n\t\t\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t\t\texit();\n\t\t\t}\n\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url = '{$newCat}'\";\n\t\t\t$res = DB_query($sql);\n\t\t\t$cat = DB_fetchArray($res);\n\t\t\tif (!is_array($cat) || empty($cat['cid'])) {\n\t\t\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t\t\texit();\n\t\t\t}\n\t\t\tif ((int) $cat['submitable'] === 0 && !SEC_hasRights('documents.admin')) {",
    'new category lookup'
)
replace_once(
    "\t\t} else {\n\t       echo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t   exit();\n\t\t}\n\t\t\n\t    $doc['cid'] = $cat['cid'];",
    "\t\t}\n\t\t\n\t    $doc['cid'] = $cat['cid'];",
    'remove obsolete new else'
)

if text == original:
    raise RuntimeError('No changes applied')

path.write_text(text, encoding='utf-8')
