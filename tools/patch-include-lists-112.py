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
    "\tif ($cat == '') return $retval;",
    "\tif ($cat == '') return $retval;\n\t$cat = addslashes((string) $cat);",
    'category SQL escaping'
)

replace_once(
    "\t\t\t\tif ($fieldvalue == $A['image'] && $fieldvalue != '') {",
    "\t\t\t\t$imageValue = isset($A['image']) ? $A['image'] : '';\n\t\t\t\t$markerValue = isset($A['marker']) ? $A['marker'] : '';\n\t\t\t\tif ($fieldvalue == $imageValue && $fieldvalue != '') {",
    'optional image result guard'
)

replace_once(
    "\t\t\t\t} else if ($fieldvalue == $A['marker'] && $fieldvalue != '') {",
    "\t\t\t\t} else if ($fieldvalue == $markerValue && $fieldvalue != '') {",
    'optional marker result guard'
)

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_lists.php optional result guards applied successfully')
