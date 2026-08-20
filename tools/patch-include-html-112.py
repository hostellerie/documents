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
    "\t\tif (isset($_REQUEST['cid']) &&  $_REQUEST['cid']> 0) {\n\t\t    \n\t\t\t// Get category\n\t\t\t\n\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = {$_REQUEST['cid']}\";\n\t\t\t$res = DB_query($sql);\n\t\t\t$cat = DB_fetchArray($res);\n\t\t\tif ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {",
    "\t\t$saveCid = DOCUMENTS_requestInt($_REQUEST, 'cid', 0);\n\t\tif ($saveCid > 0) {\n\t\t    \n\t\t\t// Get category\n\t\t\t\n\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = {$saveCid}\";\n\t\t\t$res = DB_query($sql);\n\t\t\t$cat = DB_fetchArray($res);\n\t\t\tif (!is_array($cat) || empty($cat['cid'])) {\n\t\t\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t\t\texit();\n\t\t\t}\n\t\t\tif ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {",
    'save category guard'
)

replace_once(
    "\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id = {$_REQUEST['cid']} ORDER BY f_order ASC\";",
    "\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id = {$saveCid} ORDER BY f_order ASC\";",
    'save fields category id'
)

replace_once(
    "\t\t\t\t\t$value = addslashes($_REQUEST[$A['var_name']]);",
    "\t\t\t\t\t$fieldValue = DOCUMENTS_requestValue($_REQUEST, $A['var_name'], '');\n\t\t\t\t\tif (is_array($fieldValue)) {\n\t\t\t\t\t\t$fieldValue = '';\n\t\t\t\t\t}\n\t\t\t\t\t$value = addslashes((string) $fieldValue);",
    'dynamic field value'
)

replace_once(
    "\t\t\t\t\t\tif(is_uploaded_file($_FILES[$name]['tmp_name'])) {",
    "\t\t\t\t\t\tif (isset($_FILES[$name]) && is_array($_FILES[$name])\n\t\t\t\t\t\t    && !empty($_FILES[$name]['tmp_name'])\n\t\t\t\t\t\t    && is_uploaded_file($_FILES[$name]['tmp_name'])) {",
    'image upload input guard'
)

if text == original:
    raise RuntimeError('No changes applied')
path.write_text(text, encoding='utf-8')