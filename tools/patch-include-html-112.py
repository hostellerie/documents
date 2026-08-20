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

if text == original:
    raise RuntimeError('No changes applied')

path.write_text(text, encoding='utf-8')
