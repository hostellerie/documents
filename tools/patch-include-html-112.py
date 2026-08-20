from pathlib import Path
import re

path = Path('include_html.php')
text = path.read_text(encoding='utf-8')
original = text


def replace_once(old, new, label):
    global text
    count = text.count(old)
    if count != 1:
        raise RuntimeError('%s: expected 1 occurrence, found %d' % (label, count))
    text = text.replace(old, new, 1)


def replace_all(old, new, expected, label):
    global text
    count = text.count(old)
    if count != expected:
        raise RuntimeError('%s: expected %d occurrences, found %d' % (label, expected, count))
    text = text.replace(old, new)


def regex_once(pattern, replacement, label, flags=0):
    global text
    text, count = re.subn(pattern, replacement, text, count=1, flags=flags)
    if count != 1:
        raise RuntimeError('%s: expected 1 regex replacement, found %d' % (label, count))


replace_once('| Documents Plugin 1.1.0', '| Documents Plugin 1.1.2', 'header version')
replace_once('Copyright (C) 2012-2014', 'Copyright (C) 2012-2026', 'copyright')

replace_once(
    "\t\tif ($_REQUEST['mode'] ==  ('list_fields' || 'list_groups')) {",
    "\t\t$mode = DOCUMENTS_requestValue($_REQUEST, 'mode');\n\t\tif ($mode === 'list_fields' || $mode === 'list_groups') {",
    'user menu mode comparison'
)

replace_all("\t$fields_array = '';", "\t$fields_array = array();", 2, 'missing field arrays')
replace_once("if ($A['catorder'] != $catOrd)", "if ($A['cat_order'] != $catOrd)", 'category order key')

regex_once(
    r"function DOCUMENTS_reorderSelects\(\)\n\{.*?\n\}\n\nfunction DOCUMENTS_reorderFields",
    """function DOCUMENTS_reorderSelects()\n{\n    global $_TABLES;\n\n    $group = (int) DOCUMENTS_requestValue($_REQUEST, 's_group', 0);\n    if ($group <= 0) {\n        return;\n    }\n\n    $sql = \"SELECT * FROM {$_TABLES['documents_selects']} WHERE s_group={$group} ORDER BY s_order ASC;\";\n    $result = DB_query($sql);\n    $nrows = DB_numRows($result);\n    $sOrd = 10;\n\n    for ($i = 0; $i < $nrows; $i++) {\n        $A = DB_fetchArray($result);\n        if ((int) $A['s_order'] !== $sOrd) {\n            DB_query(\"UPDATE {$_TABLES['documents_selects']} SET s_order = '{$sOrd}' WHERE sid = '\" . (int) $A['sid'] . \"'\");\n        }\n        $sOrd += 10;\n    }\n}\n\nfunction DOCUMENTS_reorderFields""",
    'reorder selects',
    re.S
)

regex_once(
    r"function DOCUMENTS_reorderFields\(\$cat\)\n\{.*?\n\}\n\nfunction DOCUMENTS_displayDocument",
    """function DOCUMENTS_reorderFields($cat)\n{\n    global $_TABLES;\n\n    $cat = (int) $cat;\n    if ($cat <= 0) {\n        return;\n    }\n\n    $sql = \"SELECT * FROM {$_TABLES['documents_fields']} WHERE cat_id={$cat} ORDER BY f_order ASC;\";\n    $result = DB_query($sql);\n    $nrows = DB_numRows($result);\n    $fOrd = 10;\n\n    for ($i = 0; $i < $nrows; $i++) {\n        $A = DB_fetchArray($result);\n        if ((int) $A['f_order'] !== $fOrd) {\n            DB_query(\"UPDATE {$_TABLES['documents_fields']} SET f_order = '{$fOrd}' WHERE fid = '\" . (int) $A['fid'] . \"'\");\n        }\n        $fOrd += 10;\n    }\n}\n\nfunction DOCUMENTS_displayDocument""",
    'reorder fields',
    re.S
)

replace_once(
    "    global $_TABLES, $_CONF, $_SCRIPTS, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $_USER, $_PLUGINS, $_MAPS_CONF;",
    "    global $_TABLES, $_CONF, $_SCRIPTS, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1, $_USER, $_MAPS_CONF;",
    'display globals'
)

regex_once(
    r"\n\s*if \(in_array\('maps', \$_PLUGINS\)\) \{\n\s*\$_SCRIPTS->setJavaScript\('<script type=\"text/javascript\" src=\"http://maps\.googleapis\.com/maps/api/js\?key=' \. \$_MAPS_CONF\['google_api_key'\] \. '&amp;libraries=adsense\"></script>', false, false\);\n\s*\}\n",
    "\n",
    'remove global maps loader'
)

replace_once(
    "\t// Category\n\t\n\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} \n\t\t\tWHERE cat_url = '{$cat_url}'\";",
    "\t// Category\n\t$cat_url = addslashes((string) $cat_url);\n\t$doc_url = addslashes((string) $doc_url);\n\t$doc = array();\n\t$raws = '';\n\t$retval = '';\n\n\t$sql = \"SELECT * FROM {$_TABLES['documents_cat']} \n\t\t\tWHERE cat_url = '{$cat_url}'\";",
    'display initialization'
)

regex_once(
    r"\tif \(\$doc\['template'\] == ''\) \{\n\s*\$template = COM_newTemplate\(\$_CONF\['path'\] \. 'plugins/documents/templates'\);\n\t\} else \{\n\s*\$template = COM_newTemplate\(\$_CONF\['path_data'\] \. 'data_documents/templates/' \. \$doc\['template'\]\);\n\t\t//js and css\n\t\t\$jsfile = \$_CONF\['path_data'\] \. 'data_documents/templates/' \. \$doc\['template'\] \.  '/scripts\.thtml';\n\t\tif \(file_exists\(\$jsfile\)\) \$_SCRIPTS->setJavaScript\(file_get_contents \(\$jsfile\), false\);\n\s*\n\s*\n\t\}",
    """\tif ($doc['template'] == '') {\n        $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');\n    } else {\n        $templateDir = DOCUMENTS_customTemplateReadDir($doc['template']);\n        if ($templateDir === '') {\n            $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');\n            $doc['template'] = '';\n        } else {\n            $template = COM_newTemplate(rtrim($templateDir, '/\\\\'));\n            $jsfile = $templateDir . 'scripts.thtml';\n            if (is_file($jsfile) && is_readable($jsfile)) {\n                $_SCRIPTS->setJavaScript(file_get_contents($jsfile), false);\n            }\n        }\n    }""",
    'custom template path',
    re.S
)

replace_once(
    "\t//Comments\n\trequire_once $_CONF['path_system'] . 'lib-comment.php';",
    "\t// Comments\n\t$comment_page = 1;\n\t$delete_option = false;\n\trequire_once $_CONF['path_system'] . 'lib-comment.php';",
    'comment defaults'
)

replace_once(
    "\t$script = '<meta property=\"og:title\" content=\"' . DOCUMENT_TITLE . '\" />",
    "\t$mainDocImg = defined('MAIN_DOC_IMG') ? MAIN_DOC_IMG : '';\n\t$facebookKey = isset($_CONF['facebook_consumer_key']) ? $_CONF['facebook_consumer_key'] : '';\n\t$script = '<meta property=\"og:title\" content=\"' . htmlspecialchars(DOCUMENT_TITLE, ENT_QUOTES, 'UTF-8') . '\" />",
    'meta defaults'
)
replace_once("' . MAIN_DOC_IMG . '", "' . $mainDocImg . '", 'main image meta')
replace_once("' . $_CONF['facebook_consumer_key'] .'", "' . $facebookKey .'", 'facebook meta')

build_raw = r'''function DOCUMENTS_buildRawDocument ($field, $doc, &$template, $i) {

    global $_CONF, $_DOCUMENTS_CONF, $_MG_CONF, $_TABLES, $_SCRIPTS, $_MAPS_CONF;

    $html = '';
    $content = '';
    $value = isset($doc['v_value'][$i]) ? $doc['v_value'][$i] : '';

    if ($value === '' && $field['f_type'] != 'checkbox') {
        $template->set_var($field['var_name'], '');
        return '';
    }

    switch ($field['f_type']) {
        case 'checkbox':
            $html .= '<td valign="top">&nbsp;</td>' . LB;
            $checked = '<img src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/disabled.png" align="top" alt="" /> ';
            if ((int) $value === 1) {
                $checked = '<img src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/enabled.png" align="top" alt="" /> ';
            }
            $content = $checked . '<label class="document_field_right">' . $field['f_name'] . '</label>';
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;

        case 'radio':
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $options = isset($doc['selects'][$i]) && is_array($doc['selects'][$i]) ? $doc['selects'][$i] : array();
            $names = isset($options['name']) && is_array($options['name']) ? $options['name'] : array();
            $values = isset($options['value']) && is_array($options['value']) ? $options['value'] : array();
            $selected = '';
            $checked = '<img src="' . $_CONF['site_url'] . '/admin/plugins/documents/images/enabled.png" align="top" alt="" /> ';
            $count = count($names);
            for ($it = 0; $it < $count; $it++) {
                $label = isset($values[$it]) ? $values[$it] : '';
                if ($value == $names[$it]) {
                    $selected .= $checked . $label . '&nbsp;&nbsp;&nbsp;';
                } else {
                    $selected .= $label . '&nbsp;&nbsp;&nbsp;';
                }
            }
            $content = $selected;
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;

        case 'album':
            if (!DOCUMENTS_hasMediaGallery() || !is_numeric($value)) {
                $template->set_var($field['var_name'], '');
                return '';
            }
            $albumId = (int) $value;
            $album_name = DB_getItem($_TABLES['mg_albums'], 'album_title', "album_id='{$albumId}'");
            if ($album_name === '') {
                $template->set_var($field['var_name'], '');
                return '';
            }
            $content = '<p><strong><a href="' . $_MG_CONF['site_url'] . '/album.php?aid=' . $albumId . '">' . $album_name . '</a></strong></p>';
            $content .= DOCUMENTS_albumGallery($albumId);
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;

        case 'category':
        case 'file':
            $template->set_var($field['var_name'], '');
            return '';

        case 'marker':
            if (!DOCUMENTS_hasMaps()) {
                $template->set_var($field['var_name'], '');
                return '';
            }
            $mkid = addslashes((string) $value);
            $sql = "SELECT * FROM {$_TABLES['maps_markers']} WHERE mkid = '{$mkid}'";
            $res = DB_query($sql);
            $marker = DB_fetchArray($res);
            if (!is_array($marker) || !isset($marker['lat'], $marker['lng'])) {
                $template->set_var($field['var_name'], '');
                return '';
            }
            if (isset($_MAPS_CONF['google_api_key']) && $_MAPS_CONF['google_api_key'] !== '') {
                $_SCRIPTS->setJavaScript('<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=' . rawurlencode($_MAPS_CONF['google_api_key']) . '"></script>', false, false);
            }
            $mapId = 'map_canvas_' . (int) $i;
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $html .= '<td width="100%" class="document_value"><div id="' . $mapId . '" style="width: 100%; height: 400px"></div></td>';
            $lat = (float) $marker['lat'];
            $lng = (float) $marker['lng'];
            $markerName = isset($marker['name']) ? json_encode($marker['name']) : '""';
            $js = '<script type="text/javascript">'
                . 'function initializeGMap_' . (int) $i . '(){'
                . 'var center=new google.maps.LatLng(' . $lat . ',' . $lng . ');'
                . 'var map=new google.maps.Map(document.getElementById(' . json_encode($mapId) . '),{center:center,zoom:10,mapTypeId:google.maps.MapTypeId.ROADMAP});'
                . 'new google.maps.Marker({map:map,position:center,title:' . $markerName . ',animation:google.maps.Animation.DROP});'
                . '}'
                . 'google.maps.event.addDomListener(window,"load",initializeGMap_' . (int) $i . ');'
                . '</script>';
            $_SCRIPTS->setJavaScript($js, false);
            $content = '<div id="' . $mapId . '" style="width: 100%; height: 400px"></div>';
            break;

        case 'image':
            $image = '';
            $filename = basename((string) $value);
            if ($filename !== '' && is_file($_DOCUMENTS_CONF['path_images'] . $filename)) {
                $previewWidth = ($doc['template'] == '') ? 450 : 700;
                $img_url = $_DOCUMENTS_CONF['site_url'] . '/image.php?src=' . rawurlencode($filename) . '&amp;w=' . $previewWidth;
                $widthAttr = ($doc['template'] == '') ? '' : ' width="100%"';
                $image = '<img class="document_img_big"' . $widthAttr . ' src="' . $img_url . '" align="top" alt="' . htmlspecialchars(DOC_NAME, ENT_QUOTES, 'UTF-8') . '" />';
                if (!defined('MAIN_DOC_IMG')) {
                    define('MAIN_DOC_IMG', $img_url);
                }
            }
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $content = $image;
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;

        case 'select':
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $content = isset($doc['s_name'][$i]) ? $doc['s_name'][$i] : '';
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;

        case 'decimal':
            $decimal = array('decimal' => $value);
            DOCUMENTS_filterVars(array('decimal' => 'number'), $decimal);
            $decimalCount = isset($_CONF['decimal_count']) ? (int) $_CONF['decimal_count'] : 2;
            $decimalSeparator = isset($_CONF['decimal_separator']) ? $_CONF['decimal_separator'] : '.';
            $thousandSeparator = isset($_CONF['thousand_separator']) ? $_CONF['thousand_separator'] : ',';
            $content = number_format((float) $decimal['decimal'], $decimalCount, $decimalSeparator, $thousandSeparator);
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;

        case 'date':
            $content = $value;
            try {
                $date = new DateTime($value);
                $content = $date->format($_DOCUMENTS_CONF['date']);
            } catch (Exception $e) {
                // Keep the stored value when it cannot be parsed.
            }
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;

        case 'text':
        default:
            $html .= '<td valign="top"><label class="document_field">' . $field['f_name'] . '</label></td>' . LB;
            $content = nl2br(stripslashes($value));
            $content = DOCUMENTS_linkifyUrls($content);
            $html .= '<td class="document_value">' . $content . '</td>' . LB;
            break;
    }

    $template->set_var($field['var_name'], PLG_replaceTags($content));
    $html = '<tr>' . LB . $html . LB . '</tr>' . LB;

    return PLG_replaceTags($html);
}
'''

regex_once(
    r"function DOCUMENTS_buildRawDocument \(\$field, \$doc, &\$template, \$i\) \{.*?\n\}\n\nfunction DOCUMENTS_albumGallery",
    build_raw + "\nfunction DOCUMENTS_albumGallery",
    'build raw document',
    re.S
)

album_gallery = r'''function DOCUMENTS_albumGallery($album) {

    global $_TABLES, $_CONF, $_MG_CONF, $_DOCUMENTS_CONF, $_SCRIPTS;

    if (!DOCUMENTS_hasMediaGallery() || !is_numeric($album)) {
        return '';
    }

    $classMedia = $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';
    if (!is_file($classMedia)) {
        return '';
    }
    require_once $classMedia;

    $album = (int) $album;
    $album_gallery = '<div id="mg_album_gallery">';
    $fancybox = '<script type="text/javascript">jQuery(document).ready(function() {' . LB;

    $sql = "SELECT * FROM {$_TABLES['mg_media']} AS m "
        . "LEFT JOIN {$_TABLES['mg_media_albums']} AS ma ON m.media_id=ma.media_id "
        . "WHERE ma.album_id={$album} ORDER BY ma.media_order DESC";
    $result = DB_query($sql, 1);
    $nRows = DB_numRows($result);

    for ($x = 0; $x < $nRows; $x++) {
        $row = DB_fetchArray($result);
        if (!is_array($row) || $row['media_mime_ext'] == '.bmp') {
            continue;
        }
        $media = new Media($row, $row['album_id']);
        $mfn = 'tn/' . $row['media_filename'][0] . '/' . $row['media_filename'];
        $row['media_mime_ext'] = $media->getMediaExt($_MG_CONF['path_mediaobjects'] . $mfn);
        $tn_size = 11;
        $image = $_MG_CONF['mediaobjects_url'] . '/' . $media->getDefaultThumbnail($row, $tn_size);
        $display_image = $_MG_CONF['mediaobjects_url'] . '/disp/' . $row['media_filename'][0] . '/' . $row['media_filename'] . $row['media_mime_ext'];
        $title = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');

        $album_gallery .= '<a class="lightbox_' . $row['media_id'] . '" rel="group' . $album
            . '" href="' . $display_image . '" title="' . $title . '">'
            . '<img class="documents_photo_gallery" width="100" height="100" src="' . $image
            . '" alt="' . $title . '" title="' . $title . '" /></a>';

        $fancybox .= 'jQuery("a.lightbox_' . $row['media_id'] . '").fancybox({hideOnContentClick:true});' . LB;
    }

    $album_gallery .= '</div><div style="clear:both;">&nbsp;</div>';
    $fancybox .= '});</script>' . LB;

    $_SCRIPTS->setJavaScriptLibrary('jquery');
    $_SCRIPTS->setJavaScriptFile('documents_mousewheel', '/admin/plugins/documents/js/fancybox/jquery.mousewheel-3.0.4.pack.js', true);
    $_SCRIPTS->setJavaScriptFile('documents_fancybox', '/admin/plugins/documents/js/fancybox/jquery.fancybox-1.3.4.pack.js', true, 1000);
    $_SCRIPTS->setCSSFile('documents_css_fancybox', '/admin/plugins/documents/js/fancybox/jquery.fancybox-1.3.4.css', false);
    $_SCRIPTS->setJavaScript($fancybox, false);

    return $album_gallery;
}
'''

regex_once(
    r"function DOCUMENTS_albumGallery\(\$album\) \{.*?\n\}\n\n\n/\*\*\n \*  Increment hit counter for ad",
    album_gallery + "\n\n/**\n *  Increment hit counter for ad",
    'album gallery',
    re.S
)

regex_once(
    r"function DOCUMENTS_hit \(\$doc\)\n\{\n    global \$_TABLES;\n    \n    DB_query\(\"UPDATE \{\$_TABLES\['documents_docs'\]\} SET hits = hits \+ 1 WHERE doc_url = '\$doc'\"\);\n\}",
    """function DOCUMENTS_hit ($doc)\n{\n    global $_TABLES;\n\n    $doc = addslashes((string) $doc);\n    DB_query(\"UPDATE {$_TABLES['documents_docs']} SET hits = hits + 1 WHERE doc_url = '{$doc}'\");\n}""",
    'hit counter'
)

replace_once(
    "\t$upload->setPerms('0644');\n\t\n\t$count = count($image_name);",
    "\t$upload->setPerms('0644');\n\n\t$filename = array();\n\t$count = count($image_name);",
    'upload filename initialization'
)
replace_once(
    "\t\t$curfile = $_FILES[$input_name[$z]];",
    "\t\t$curfile = isset($_FILES[$input_name[$z]]) && is_array($_FILES[$input_name[$z]]) ? $_FILES[$input_name[$z]] : array();",
    'upload file request guard'
)
replace_once(
    "\t$upload->setFileNames($filename);\n\t$upload->uploadFiles();",
    "\tif (empty($filename)) {\n\t\treturn true;\n\t}\n\n\t$upload->setFileNames($filename);\n\t$upload->uploadFiles();",
    'skip empty upload batch'
)

replace_once("\tglobal $_TABLES, $_DOCUMENTS_CONF, $_PLUGINS;", "\tglobal $_TABLES, $_DOCUMENTS_CONF;", 'save marker globals')
replace_once("\tif( !in_array('maps', $_PLUGINS) ) {", "\tif (!DOCUMENTS_hasMaps()) {", 'save marker dependency')

replace_once("switch ($_REQUEST['mode']) {", "switch (DOCUMENTS_requestValue($_REQUEST, 'mode')) {", 'safe mode switch')
replace_once("$display = '';\n\n// MAIN", "$display = '';\n$content = '';\n\n// MAIN", 'content initialization')
replace_once("$display .= DOCUMENTS_message($_REQUEST['msg']);", "$display .= DOCUMENTS_message(DOCUMENTS_requestValue($_REQUEST, 'msg'));", 'safe message request')
replace_once("if( in_array('maps', $_PLUGINS) ) {", "if (DOCUMENTS_hasMaps() && DOCUMENTS_requestValue($_REQUEST, 'mkid') !== '') {", 'delete marker dependency')

replace_once(
    "\t\t\t// For each field save value\n\t\t\t\n\t\t\twhile ($A = DB_fetchArray($fields)) {",
    "\t\t\t// For each field save value\n\t\t\t$image_names = array();\n\t\t\t$input_names = array();\n\t\t\t$image_fields = array();\n\t\t\t$creation = isset($_REQUEST['doc_url']) && $_REQUEST['doc_url'] !== '' ? 0 : 1;\n\n\t\t\twhile ($A = DB_fetchArray($fields)) {\n\t\t\t\tif (($A['f_type'] === 'marker' && !DOCUMENTS_hasMaps())\n\t\t\t\t    || ($A['f_type'] === 'album' && !DOCUMENTS_hasMediaGallery())) {\n\t\t\t\t\tcontinue;\n\t\t\t\t}",
    'save field initialization and optional dependency preservation'
)

replace_once(
    "\t\t\t\t\t\t\t   $A['fid'] = DOCUMENTS_saveMarker ($mid, $_REQUEST['mkid'], $_REQUEST['doc_url']);",
    "\t\t\t\t\t\t\t   $value = DOCUMENTS_saveMarker($mid, DOCUMENTS_requestValue($_REQUEST, 'mkid'), $_REQUEST['doc_url']);",
    'marker value vs field id bug'
)

replace_once(
    "\t\t\t\t\t\t\t   $value = DOCUMENTS_saveMarker ($mid, $_REQUEST['mkid'], $_REQUEST['doc_url']);",
    "\t\t\t\t\t\t\t   $value = DOCUMENTS_saveMarker($mid, DOCUMENTS_requestValue($_REQUEST, 'mkid'), $_REQUEST['doc_url']);",
    'marker update request guard'
)
replace_once(
    "\t\t\t\t\t\t\t   $value = DOCUMENTS_saveMarker ($mid, $_REQUEST['mkid'], DOC_URL);",
    "\t\t\t\t\t\t\t   $value = DOCUMENTS_saveMarker($mid, DOCUMENTS_requestValue($_REQUEST, 'mkid'), DOC_URL);",
    'marker create request guard'
)

# Preserve existing map/category integration values when Maps is unavailable.
replace_once(
    "\t\t\t( empty($_REQUEST['cat_order']) ) ? $_REQUEST['cat_order'] = 0 : 0;",
    "\t\t\t( empty($_REQUEST['cat_order']) ) ? $_REQUEST['cat_order'] = 0 : 0;\n\n\t\t\tif (!DOCUMENTS_hasMaps()) {\n\t\t\t\tif (!empty($_REQUEST['cid']) && is_numeric($_REQUEST['cid'])) {\n\t\t\t\t\t$_REQUEST['map'] = DB_getItem($_TABLES['documents_cat'], 'map', 'cid=' . (int) $_REQUEST['cid']);\n\t\t\t\t} else {\n\t\t\t\t\t$_REQUEST['map'] = 0;\n\t\t\t\t}\n\t\t\t}",
    'preserve category map without Maps'
)

# Reject forged optional field types when integrations are unavailable.
replace_once(
    "\t\t\t\t$missingfields = DOCUMENTS_missingField($_REQUEST);",
    "\t\t\t\t$missingfields = DOCUMENTS_missingField($_REQUEST);\n\t\t\t\t$fType = DOCUMENTS_requestValue($_REQUEST, 'f_type');\n\t\t\t\tif (($fType === 'marker' && !DOCUMENTS_hasMaps()) || ($fType === 'album' && !DOCUMENTS_hasMediaGallery())) {\n\t\t\t\t\t$missingfields[] = 'Optional field type is unavailable because its plugin is inactive.';\n\t\t\t\t}",
    'reject unavailable optional field types'
)

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_html.php patched successfully')
