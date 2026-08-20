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


replace_once(
    "function plugin_getListField_groups_fields($fieldname, $fieldvalue, $A, $icon_arr)\n{\n\n    global $_DOCUMENTS_CONF;",
    "function plugin_getListField_groups_fields($fieldname, $fieldvalue, $A, $icon_arr)\n{\n\n    global $_DOCUMENTS_CONF;\n\n    $retval = '';",
    'group callback retval initialization'
)

replace_once(
    "function DOCUMENTS_listSelects($group)\n{\n    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;",
    "function DOCUMENTS_listSelects($group)\n{\n    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;\n\n    $group = (int) $group;",
    'select group numeric normalization'
)

replace_once(
    "\tif($group > 0) $sql .= \" AND s_group = '$group'\";",
    "\tif ($group > 0) {\n\t\t$sql .= \" AND s_group = '{$group}'\";\n\t}",
    'select group sql guard'
)

replace_once(
    "function plugin_getListField_selects_fields($fieldname, $fieldvalue, $A, $icon_arr)\n{\n\n    global $_DOCUMENTS_CONF;",
    "function plugin_getListField_selects_fields($fieldname, $fieldvalue, $A, $icon_arr)\n{\n\n    global $_DOCUMENTS_CONF;\n\n    $retval = '';",
    'select callback retval initialization'
)

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_lists.php callback warning cleanup applied successfully')
