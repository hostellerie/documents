from pathlib import Path

path = Path('include_lists.php')
text = path.read_text(encoding='utf-8')
original = text


def replace_once(old, new, label):
    global text
    count = text.count(old)
    if count != 1:
        raise RuntimeError('%s: expected 1 occurrence, found %d' % (label, count))
    text = text.replace(old, new, 1)


replace_once('| Documents Plugin 1.0', '| Documents Plugin 1.1.2', 'header version')
replace_once('Copyright (C) 2012 by the following authors:', 'Copyright (C) 2012-2026 by the following authors:', 'copyright')

replace_once(
    '    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1,$_TABLES;',
    "    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $_TABLES, $_USER;\n\n    $retval = '';\n    $doc_titles = '';\n    $edit = '';",
    'category callback globals and initialization'
)

replace_once(
    "    $retval = '';\n\n\tif ($cat == '') return $retval;",
    "    $retval = '';\n    $morefields = '';\n    $leftjoin = '';\n\n\tif ($cat == '') return $retval;",
    'list docs accumulator initialization'
)

replace_once(
    '\tdefine("CAT_URL", $cat);',
    "\tif (!defined('CAT_URL')) {\n\t\tdefine('CAT_URL', $cat);\n\t}",
    'category url constant guard'
)

replace_once(
    "\t$category = DB_fetchArray(DB_query($sql));\n\t\n\t//is cat submitable",
    "\t$category = DB_fetchArray(DB_query($sql));\n\tif (!is_array($category) || empty($category['cid'])) {\n\t\treturn $retval;\n\t}\n\n\t//is cat submitable",
    'category result guard'
)

replace_once(
    "\tif ($category['map'] != '' && $category['map'] > 0) $retval .= PLG_replaceTags(\"[maps:{$category['map']}]\");",
    "\tif (DOCUMENTS_hasMaps() && $category['map'] != '' && $category['map'] > 0) {\n\t\t$retval .= PLG_replaceTags(\"[maps:{$category['map']}]\");\n\t}",
    'category maps output guard'
)

replace_once(
    "    global $_DOCUMENTS_CONF, $_CONF, $LANG_DOCUMENTS_1;\n\n\tswitch($fieldname) {",
    "    global $_DOCUMENTS_CONF, $_CONF, $LANG_DOCUMENTS_1;\n\n    $retval = '';\n    $edit = '';\n    $inactive = '';\n\n\tswitch($fieldname) {",
    'document callback initialization'
)

replace_once(
    "\t\t\t\t} else if ($fieldvalue == $A['marker'] && $fieldvalue != '') {\n\t\t\t\t\t$retval = PLG_replaceTags('<div style=\"width:450px;\">[marker:' . $fieldvalue . ' width:400px]</div>');",
    "\t\t\t\t} else if ($fieldvalue == $A['marker'] && $fieldvalue != '') {\n\t\t\t\t\tif (DOCUMENTS_hasMaps()) {\n\t\t\t\t\t\t$retval = PLG_replaceTags('<div style=\"width:450px;\">[marker:' . $fieldvalue . ' width:400px]</div>');\n\t\t\t\t\t} else {\n\t\t\t\t\t\t$retval = '';\n\t\t\t\t\t}",
    'document marker output guard'
)

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_lists.php core cleanup applied successfully')
