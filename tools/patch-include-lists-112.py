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

regex_once(
    r"function plugin_getListField_documents_categories\(\$fieldname, \$fieldvalue, \$A, \$icon_arr\)\n\{\n\n\s*global \$_CONF, \$_DOCUMENTS_CONF, \$LANG_DOCUMENTS_1,\s*\$_TABLES;",
    """function plugin_getListField_documents_categories($fieldname, $fieldvalue, $A, $icon_arr)\n{\n\n    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $_TABLES, $_USER;\n\n    $retval = '';\n    $doc_titles = '';\n    $edit = '';""",
    'category callback globals and initialization'
)

regex_once(
    r"\. \$_DOCUMENTS_CONF\['site_url'\] \. '/image\.php\?src=' \. \$_DOCUMENTS_CONF\['images_url'\]\s*\n\s*\. \$B\['v_value'\] \. '&amp;w=75&amp;h=75&amp;q=90&amp;zc=1\"",
    ". $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode(basename($B['v_value']))\n                    . '&amp;w=75&amp;h=75\"",
    'category preview endpoint'
)

regex_once(
    r"function DOCUMENTS_listDocs \(\$cat\)\n\{\n\s*global \$_CONF, \$_DOCUMENTS_CONF, \$_TABLES, \$LANG_DOCUMENTS_1, \$_SCRIPTS;\n\n\s*require_once \$_CONF\['path_system'\] \. 'lib-admin\.php';\n\n\s*\$retval = '';",
    """function DOCUMENTS_listDocs ($cat)\n{\n    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1, $_SCRIPTS;\n\n    require_once $_CONF['path_system'] . 'lib-admin.php';\n\n    $retval = '';\n    $morefields = '';\n    $leftjoin = '';""",
    'list docs initialization'
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

regex_once(
    r"function plugin_getListField_documents_docs\(\$fieldname, \$fieldvalue, \$A, \$icon_arr\)\n\{\n\n\s*global \$_DOCUMENTS_CONF, \$_CONF, \$LANG_DOCUMENTS_1;",
    """function plugin_getListField_documents_docs($fieldname, $fieldvalue, $A, $icon_arr)\n{\n\n    global $_DOCUMENTS_CONF, $_CONF, $LANG_DOCUMENTS_1;\n\n    $retval = '';\n    $edit = '';\n    $inactive = '';""",
    'document callback initialization'
)

regex_once(
    r"\$image = '<img class=\"document_img\" src=\"' \. \$_DOCUMENTS_CONF\['site_url'\] \. '/image\.php\?src=' \. \$_DOCUMENTS_CONF\['images_url'\] \.\s*\n\s*\$fieldvalue \. '&amp;w=200&amp;h=200\" align=\"top\" alt=\"\" />';",
    "$image = '<img class=\"document_img\" src=\"' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode(basename($fieldvalue)) .\n                        '&amp;w=200&amp;h=200\" align=\"top\" alt=\"\" />';",
    'document preview endpoint'
)

regex_once(
    r"\} else if \(\$fieldvalue == \$A\['marker'\] && \$fieldvalue != ''\) \{\n\s*\$retval = PLG_replaceTags\('<div style=\"width:450px;\">\[marker:' \. \$fieldvalue \. ' width:400px\]</div>'\);",
    """} else if ($fieldvalue == $A['marker'] && $fieldvalue != '') {\n                    if (DOCUMENTS_hasMaps()) {\n                        $retval = PLG_replaceTags('<div style=\"width:450px;\">[marker:' . $fieldvalue . ' width:400px]</div>');\n                    } else {\n                        $retval = '';\n                    }""",
    'document marker output guard'
)

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_lists.php patched successfully')
