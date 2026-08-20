from pathlib import Path
import re

path = Path('include_lists.php')
text = path.read_text(encoding='utf-8')
original = text


def replace_once(old, new, label):
    global text
    count = text.count(old)
    if count != 1:
        raise RuntimeError('%s: expected 1 occurrence, found %d' % (label, count))
    text = text.replace(old, new, 1)


def regex_once(pattern, replacement, label, flags=0):
    global text
    text, count = re.subn(pattern, replacement, text, count=1, flags=flags)
    if count != 1:
        raise RuntimeError('%s: expected 1 regex replacement, found %d' % (label, count))


replace_once('| Documents Plugin 1.0', '| Documents Plugin 1.1.2', 'header version')
replace_once('Copyright (C) 2012 by the following authors:', 'Copyright (C) 2012-2026 by the following authors:', 'copyright')

replace_once(
    '    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1,$_TABLES;',
    '    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $_TABLES, $_USER;',
    'category callback globals'
)
replace_once(
    "\t\nswitch($fieldname) {",
    "\n    $retval = '';\n    $doc_titles = '';\n    $edit = '';\n\n\tswitch($fieldname) {",
    'category callback initialization'
)

# Use the restricted local image endpoint with a filename, not a concatenated full URL.
replace_once(
    ". $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . $_DOCUMENTS_CONF['images_url'] \n\t\t\t\t\t. $B['v_value'] . '&amp;w=75&amp;h=75&amp;q=90&amp;zc=1\"",
    ". $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode(basename($B['v_value'])) \n\t\t\t\t\t. '&amp;w=75&amp;h=75\"",
    'category preview endpoint'
)

# Initialize list SQL fragments and validate category results before reading keys.
regex_once(
    r"function DOCUMENTS_listDocs \(\$cat\)\n\{\n    global \$_CONF, \$_DOCUMENTS_CONF, \$_TABLES, \$LANG_DOCUMENTS_1, \$_SCRIPTS;\n\n    require_once \$_CONF\['path_system'\] \. 'lib-admin\.php';\n\n    \$retval = '';",
    """function DOCUMENTS_listDocs ($cat)\n{\n    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1, $_SCRIPTS;\n\n    require_once $_CONF['path_system'] . 'lib-admin.php';\n\n    $retval = '';\n    $morefields = '';\n    $leftjoin = '';""",
    'list docs initialization'
)
replace_once(
    "\t$category = DB_fetchArray(DB_query($sql));\n\t\n\t//is cat submitable",
    "\t$category = DB_fetchArray(DB_query($sql));\n\tif (!is_array($category) || empty($category['cid'])) {\n\t\treturn $retval;\n\t}\n\n\t//is cat submitable",
    'category result guard'
)
replace_once(
    '\tdefine("CAT_URL", $cat);',
    "\tif (!defined('CAT_URL')) {\n\t\tdefine('CAT_URL', $cat);\n\t}",
    'category url constant guard'
)

# Do not emit Maps autotags when Maps is inactive.
replace_once(
    "\tif ($category['map'] != '' && $category['map'] > 0) $retval .= PLG_replaceTags(\"[maps:{$category['map']}]\");",
    "\tif (DOCUMENTS_hasMaps() && $category['map'] != '' && $category['map'] > 0) {\n\t\t$retval .= PLG_replaceTags(\"[maps:{$category['map']}]\");\n\t}",
    'category maps output guard'
)

# Initialize document-list callback output variables.
replace_once(
    "    global $_DOCUMENTS_CONF, $_CONF, $LANG_DOCUMENTS_1;\n\n\tswitch($fieldname) {",
    "    global $_DOCUMENTS_CONF, $_CONF, $LANG_DOCUMENTS_1;\n\n    $retval = '';\n    $edit = '';\n    $inactive = '';\n\n\tswitch($fieldname) {",
    'document callback initialization'
)

replace_once(
    "$image = '<img class=\"document_img\" src=\"' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . $_DOCUMENTS_CONF['images_url'] .\n\t\t\t\t\t\t$fieldvalue . '&amp;w=200&amp;h=200\" align=\"top\" alt=\"\" />';",
    "$image = '<img class=\"document_img\" src=\"' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode(basename($fieldvalue)) .\n\t\t\t\t\t\t'&amp;w=200&amp;h=200\" align=\"top\" alt=\"\" />';",
    'document preview endpoint'
)

replace_once(
    "\t\t\t\t} else if ($fieldvalue == $A['marker'] && $fieldvalue != '') {\n\t\t\t\t\t$retval = PLG_replaceTags('<div style=\"width:450px;\">[marker:' . $fieldvalue . ' width:400px]</div>');",
    "\t\t\t\t} else if ($fieldvalue == $A['marker'] && $fieldvalue != '') {\n\t\t\t\t\tif (DOCUMENTS_hasMaps()) {\n\t\t\t\t\t\t$retval = PLG_replaceTags('<div style=\"width:450px;\">[marker:' . $fieldvalue . ' width:400px]</div>');\n\t\t\t\t\t} else {\n\t\t\t\t\t\t$retval = '';\n\t\t\t\t\t}",
    'document marker output guard'
)

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_lists.php patched successfully')
