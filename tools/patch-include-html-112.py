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
    "\t\t} else if (isset($_GET['cat']) && $_REQUEST['cat'] !='') {\n\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url = '{$_REQUEST['cat']}'\";\n\t\t\t$res = DB_query($sql);\n\t\t\t$cat = DB_fetchArray($res);\n\t\t\tif ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {",
    "\t\t} else {\n\t\t\t$newCatUrl = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'cat', ''));\n\t\t\tif ($newCatUrl === '') {\n\t\t\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t\t\texit();\n\t\t\t}\n\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cat_url = '{$newCatUrl}'\";\n\t\t\t$res = DB_query($sql);\n\t\t\t$cat = DB_fetchArray($res);\n\t\t\tif (!is_array($cat) || empty($cat['cid'])) {\n\t\t\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t\t\texit();\n\t\t\t}\n\t\t\tif ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {",
    'new category lookup'
)
replace_once(
    "\t\t} else {\n\t       echo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t   exit();\n\t\t}\n\t\t\n\t    $doc['cid'] = $cat['cid'];",
    "\t\t}\n\t\t\n\t    $doc = array();\n\t    $doc['cid'] = $cat['cid'];",
    'new document init'
)
replace_once(
    "\t\tif (isset($_GET['doc_url']) &&  $_GET['doc_url']!= '') {\n\t\t    \n\t\t\t//Edit mode\n\t\t\t\n\t\t\tif (!defined(\"DOC_URL\")) {\n                   define(\"DOC_URL\", $_GET['doc_url']);\n\t\t\t}\n\t\t\t\n\t\t\t$sql = \"SELECT v.field_id, f.fid, f.f_type, f.sel_id, v.v_value, d.doc_url, d.active, d.owner_id , d.group_id, d.perm_owner, d.perm_group, d.perm_members, d.perm_anon ",
    "\t\t$editDocUrl = addslashes((string) DOCUMENTS_requestValue($_GET, 'doc_url', ''));\n\t\tif ($editDocUrl !== '') {\n\t\t    \n\t\t\t//Edit mode\n\t\t\t\n\t\t\tif (!defined(\"DOC_URL\")) {\n                   define(\"DOC_URL\", $editDocUrl);\n\t\t\t}\n\t\t\t\n\t\t\t$doc = array();\n\t\t\t$sql = \"SELECT v.field_id, f.fid, f.f_type, f.sel_id, v.v_value, d.doc_url, d.active, d.owner_id , d.group_id, d.perm_owner, d.perm_group, d.perm_members, d.perm_anon ",
    'edit document url init'
)
replace_once(
    "\t\t\t\t\tWHERE v.doc_url = '{$_GET['doc_url']}' ORDER BY f.f_order\";",
    "\t\t\t\t\tWHERE v.doc_url = '{$editDocUrl}' ORDER BY f.f_order\";",
    'edit document sql url'
)
replace_once(
    "\t\t\twhile ($A = DB_fetchArray($res)) {\n\t\t\t\t$doc['field_id'][$A['field_id']] = $A['field_id'];",
    "\t\t\twhile ($A = DB_fetchArray($res)) {\n\t\t\t\t$doc['field_id'][$A['field_id']] = $A['field_id'];",
    'edit document loop anchor'
)
replace_once(
    "\t\t\t}\n\t\t\t\n\t\t\t// check secury access\n\t\t\t$access = SEC_hasAccess($doc['owner_id'], $doc['group_id'],",
    "\t\t\t}\n\t\t\tif (empty($doc) || !isset($doc['owner_id'], $doc['group_id'], $doc['perm_owner'], $doc['perm_group'], $doc['perm_members'], $doc['perm_anon'])) {\n\t\t\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t\t\texit();\n\t\t\t}\n\t\t\t\n\t\t\t// check secury access\n\t\t\t$access = SEC_hasAccess($doc['owner_id'], $doc['group_id'],",
    'edit document existence guard'
)
replace_once(
    "\t\t\tif (isset($_GET['cat']) && $_REQUEST['cat'] !='') {\n\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = '{$_REQUEST['cat']}'\";\n\t\t\t\t$res = DB_query($sql);\n\t\t\t\t$cat = DB_fetchArray($res);\n\t\t\t\tif ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {",
    "\t\t\t$editCatId = DOCUMENTS_requestInt($_REQUEST, 'cat', 0);\n\t\t\tif ($editCatId > 0) {\n\n\t\t\t\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} WHERE cid = {$editCatId}\";\n\t\t\t\t$res = DB_query($sql);\n\t\t\t\t$cat = DB_fetchArray($res);\n\t\t\t\tif (!is_array($cat) || empty($cat['cid'])) {\n\t\t\t\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\t\t\t\texit();\n\t\t\t\t}\n\t\t\t\tif ( $cat['submitable'] == 0 && !SEC_hasRights('documents.admin') ) {",
    'edit category lookup'
)
replace_once(
    "\t\t$doc['cid'] = $_REQUEST['cat'];",
    "\t\t$doc['cid'] = $editCatId;",
    'edit category id assignment'
)

if text == original:
    raise RuntimeError('No changes applied')
path.write_text(text, encoding='utf-8')
