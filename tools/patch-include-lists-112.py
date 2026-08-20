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
    "\t\t\t\t\t. $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . $_DOCUMENTS_CONF['images_url'] \n\t\t\t\t\t. $B['v_value'] . '&amp;w=75&amp;h=75&amp;q=90&amp;zc=1\" vspace=\"5\" hspace=\"0\" width=\"75\" height=\"75\"",
    "\t\t\t\t\t. $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode(basename($B['v_value'])) \n\t\t\t\t\t. '&amp;w=75&amp;h=75\" vspace=\"5\" hspace=\"0\" width=\"75\" height=\"75\"",
    'category image preview endpoint'
)

replace_once(
    "$image = '<img class=\"document_img\" src=\"' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . $_DOCUMENTS_CONF['images_url'] .\n\t\t\t\t\t\t$fieldvalue . '&amp;w=200&amp;h=200\" align=\"top\" alt=\"\" />';",
    "$image = '<img class=\"document_img\" src=\"' . $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode(basename($fieldvalue)) .\n\t\t\t\t\t\t'&amp;w=200&amp;h=200\" align=\"top\" alt=\"\" />';",
    'document image preview endpoint'
)

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_lists.php image preview cleanup applied successfully')
