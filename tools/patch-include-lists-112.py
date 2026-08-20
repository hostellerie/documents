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

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_lists.php atomic category cleanup applied successfully')
