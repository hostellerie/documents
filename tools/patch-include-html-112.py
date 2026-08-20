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
    "\t\t\tif ($_REQUEST['group_name'] == '') {",
    "\t\t\t$groupName = trim((string) DOCUMENTS_requestValue($_REQUEST, 'group_name', ''));\n\t\t\t$groupHelp = (string) DOCUMENTS_requestValue($_REQUEST, 'group_help', '');\n\t\t\t$saveGroupId = DOCUMENTS_requestInt($_REQUEST, 'gid', 0);\n\n\t\t\tif ($groupName === '') {",
    'group input normalization'
)
replace_once(
    "\t\t\t$_REQUEST['group_name'] = addslashes($_REQUEST['group_name']);\t\n\t\t\t$_REQUEST['group_help'] = addslashes($_REQUEST['group_help']);\t\n\n\t\t\tif ( (!empty($_REQUEST['gid'])) && (is_numeric($_REQUEST['gid'])) ) {",
    "\t\t\t$_REQUEST['group_name'] = addslashes($groupName);\n\t\t\t$_REQUEST['group_help'] = addslashes($groupHelp);\n\n\t\t\tif ($saveGroupId > 0) {",
    'group escaped values'
)
replace_once(
    "\t\t\t\t\t . \"WHERE gid = {$_REQUEST['gid']}\";",
    "\t\t\t\t\t . \"WHERE gid = {$saveGroupId}\";",
    'group id sql'
)

replace_once(
    "\t\t\tif ($_REQUEST['s_name'] == '') {",
    "\t\t\t$selectName = trim((string) DOCUMENTS_requestValue($_REQUEST, 's_name', ''));\n\t\t\t$selectValue = (string) DOCUMENTS_requestValue($_REQUEST, 's_value', '');\n\t\t\t$selectGroupId = DOCUMENTS_requestInt($_REQUEST, 's_group', 0);\n\t\t\t$selectOrder = DOCUMENTS_requestInt($_REQUEST, 's_order', 0);\n\t\t\t$saveSelectId = DOCUMENTS_requestInt($_REQUEST, 'sid', 0);\n\n\t\t\tif ($selectName === '') {",
    'select input normalization'
)
replace_once(
    "\t\t\t$_REQUEST['s_name'] = addslashes($_REQUEST['s_name']);\t\n\t\t\t$_REQUEST['s_value'] = addslashes($_REQUEST['s_value']);\t\n\n\t\t\tif ( (!empty($_REQUEST['sid'])) && (is_numeric($_REQUEST['sid'])) ) {",
    "\t\t\t$_REQUEST['s_name'] = addslashes($selectName);\n\t\t\t$_REQUEST['s_value'] = addslashes($selectValue);\n\t\t\t$_REQUEST['s_group'] = $selectGroupId;\n\t\t\t$_REQUEST['s_order'] = $selectOrder;\n\n\t\t\tif ($saveSelectId > 0) {",
    'select escaped values'
)
replace_once(
    "\t\t\t\t\t . \"WHERE sid = {$_REQUEST['sid']}\";",
    "\t\t\t\t\t . \"WHERE sid = {$saveSelectId}\";",
    'select id sql'
)

if text == original:
    raise RuntimeError('No changes applied')
path.write_text(text, encoding='utf-8')