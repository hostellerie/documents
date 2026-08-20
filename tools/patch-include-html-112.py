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


def regex_once(pattern, replacement, label, flags=0):
    global text
    text, count = re.subn(pattern, replacement, text, count=1, flags=flags)
    if count != 1:
        raise RuntimeError('%s: expected 1 regex replacement, found %d' % (label, count))


# Validate the category before reading fields or defining constants.
replace_once(
    "\t$res = DB_query($sql);\n\t$cat = DB_fetchArray($res);\n\t\n\tif (!defined(\"CAT_NAME\")) {\n\t\tdefine(\"CAT_NAME\",$cat['cat_name']);\n\t}\n\t\n\t//Check if cat exists\n\t\n\tif($cat['cat_url'] != '' && $cat['cat_url']== $cat_url) {",
    "\t$res = DB_query($sql);\n\t$cat = DB_fetchArray($res);\n\n\t// Check if category exists before reading any of its values.\n\tif (!is_array($cat) || empty($cat['cat_url']) || $cat['cat_url'] != $cat_url) {\n\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\texit;\n\t}\n\n\tif (!defined('CAT_NAME')) {\n\t\tdefine('CAT_NAME', $cat['cat_name']);\n\t}\n\tif (!defined('CAT_URL')) {\n\t\tdefine('CAT_URL', $cat['cat_url']);\n\t}\n\n\t{",
    'category validation'
)

# Remove the now-unreachable duplicate constant definitions and else/404 branch.
replace_once(
    "\t\tdefine(\"CAT_NAME\", $cat['cat_name']);\n\t\tdefine(\"CAT_URL\", $cat['cat_url']);\n\t\t\n\t} else {   \n\t    echo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\texit;\n\t}",
    "\t}",
    'duplicate category constants'
)

# A document with no values must not continue with undefined status/permissions.
replace_once(
    "\t}\n\t\n\t//Check active rights (0=non-active, 1=active, 2=draft, 3=submission)",
    "\t}\n\n\tif (empty($doc['doc_url'])) {\n\t\techo COM_refresh($_CONF['site_url'] . '/404.php');\n\t\texit;\n\t}\n\n\t//Check active rights (0=non-active, 1=active, 2=draft, 3=submission)",
    'document existence guard'
)

# Keep OpenGraph output safe and avoid undefined site_name.
replace_once(
    "\t<meta property=\"og:description\" content=\"' . DOCUMENT_TITLE . '\" />",
    "\t<meta property=\"og:description\" content=\"' . htmlspecialchars(DOCUMENT_TITLE, ENT_QUOTES, 'UTF-8') . '\" />",
    'og description escaping'
)
replace_once(
    "\t<meta property=\"og:site_name\" content=\"' . $_CONF['site_name'] .'\" />",
    "\t<meta property=\"og:site_name\" content=\"' . htmlspecialchars(isset($_CONF['site_name']) ? $_CONF['site_name'] : '', ENT_QUOTES, 'UTF-8') .'\" />",
    'og site name guard'
)

# Replace upload handling so filenames, input names and field metadata remain in sync.
upload_function = r'''function DOCUMENTS_uploadImage ($image_name=array(), $input_name=array(), $fields=array(), $creation) {

    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG24, $_USER;

    $count = min(count($image_name), count($input_name), count($fields));
    if ($count <= 0) {
        return true;
    }

    require_once $_CONF['path_system'] . 'classes/upload.class.php';
    $upload = new upload();

    if (isset($_CONF['debug_image_upload']) && $_CONF['debug_image_upload']) {
        $upload->setLogFile($_CONF['path'] . 'logs/error.log');
        $upload->setDebug(true);
    }

    $upload->setMaxFileUploads(20);
    if (!empty($_CONF['image_lib'])) {
        if ($_CONF['image_lib'] == 'imagemagick') {
            $upload->setMogrifyPath($_CONF['path_to_mogrify']);
        } elseif ($_CONF['image_lib'] == 'netpbm') {
            $upload->setNetPBM($_CONF['path_to_netpbm']);
        } elseif ($_CONF['image_lib'] == 'gdlib') {
            $upload->setGDLib();
        }
        $upload->setAutomaticResize(true);
        $upload->keepOriginalImage(false);
        if (isset($_CONF['jpeg_quality'])) {
            $upload->setJpegQuality($_CONF['jpeg_quality']);
        }
    }

    $upload->setAllowedMimeTypes(array(
        'image/gif'   => '.gif',
        'image/jpeg'  => '.jpg,.jpeg',
        'image/pjpeg' => '.jpg,.jpeg',
        'image/x-png' => '.png',
        'image/png'   => '.png'
    ));

    if (!$upload->setPath($_DOCUMENTS_CONF['path_images'])) {
        $output = COM_siteHeader('menu', $LANG24[30]);
        $output .= COM_startBlock($LANG24[30], '', COM_getBlockTemplate('_msg_block', 'header'));
        $output .= $upload->printErrors(false);
        $output .= COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
        $output .= COM_siteFooter();
        COM_output($output);
        exit;
    }

    $upload->setMaxDimensions($_DOCUMENTS_CONF['max_image_width'], $_DOCUMENTS_CONF['max_image_height']);
    $upload->setMaxFileSize($_DOCUMENTS_CONF['max_image_size']);
    $upload->setPerms('0644');

    $filenames = array();
    $uploadedFields = array();

    for ($z = 0; $z < $count; $z++) {
        $input = $input_name[$z];
        if (!isset($_FILES[$input]) || !is_array($_FILES[$input])) {
            continue;
        }

        $curfile = $_FILES[$input];
        if (empty($curfile['name']) || (isset($curfile['error']) && (int) $curfile['error'] === UPLOAD_ERR_NO_FILE)) {
            continue;
        }

        $extension = strtolower(pathinfo($curfile['name'], PATHINFO_EXTENSION));
        if ($extension === '') {
            continue;
        }

        $baseName = preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $image_name[$z]);
        $baseName = trim($baseName, '.-');
        if ($baseName === '') {
            continue;
        }

        $filenames[] = $baseName . '.' . $extension;
        $uploadedFields[] = $fields[$z];
    }

    if (empty($filenames)) {
        return true;
    }

    $upload->setFileNames($filenames);
    $upload->uploadFiles();

    if ($upload->areErrors()) {
        $retval = COM_siteHeader('menu', $LANG24[30]);
        $retval .= COM_startBlock($LANG24[30], '', COM_getBlockTemplate('_msg_block', 'header'));
        $retval .= $upload->printErrors(false);
        $retval .= COM_endBlock(COM_getBlockTemplate('_msg_block', 'footer'));
        $retval .= COM_siteFooter();
        COM_output($retval);
        exit;
    }

    $group_id = DB_getItem($_TABLES['groups'], 'grp_id', "grp_name='Documents Admin'");
    $docUrl = defined('DOC_URL') ? addslashes(DOC_URL) : '';

    $permOwner = DOCUMENTS_requestValue($_REQUEST, 'perm_owner', '');
    $permGroup = DOCUMENTS_requestValue($_REQUEST, 'perm_group', '');
    $permMembers = DOCUMENTS_requestValue($_REQUEST, 'perm_members', '');
    $permAnon = DOCUMENTS_requestValue($_REQUEST, 'perm_anon', '');

    if (is_array($permOwner) || is_array($permGroup) || is_array($permMembers) || is_array($permAnon)) {
        list($permOwner, $permGroup, $permMembers, $permAnon) = SEC_getPermissionValues(
            $permOwner,
            $permGroup,
            $permMembers,
            $permAnon
        );
    }

    if ($permOwner === '') {
        $defaults = array();
        SEC_setDefaultPermissions($defaults, $_DOCUMENTS_CONF['default_permissions']);
        $permOwner = isset($defaults['perm_owner']) ? $defaults['perm_owner'] : 3;
        $permGroup = isset($defaults['perm_group']) ? $defaults['perm_group'] : 3;
        $permMembers = isset($defaults['perm_members']) ? $defaults['perm_members'] : 2;
        $permAnon = isset($defaults['perm_anon']) ? $defaults['perm_anon'] : 2;
    }

    $fileCount = count($filenames);
    for ($z = 0; $z < $fileCount; $z++) {
        $filename = addslashes($filenames[$z]);
        $fieldId = isset($uploadedFields[$z]['fid']) ? (int) $uploadedFields[$z]['fid'] : 0;
        if ($fieldId <= 0 || $docUrl === '') {
            continue;
        }

        if ((int) $creation === 0) {
            DB_query("UPDATE {$_TABLES['documents_values']} SET v_value='{$filename}' "
                . "WHERE field_id='{$fieldId}' AND doc_url='{$docUrl}'");
        } else {
            DB_query("INSERT INTO {$_TABLES['documents_values']} SET "
                . "v_value='{$filename}', field_id='{$fieldId}', doc_url='{$docUrl}', "
                . "owner_id='" . (int) $_USER['uid'] . "', group_id='" . (int) $group_id . "', "
                . "perm_owner='" . (int) $permOwner . "', perm_group='" . (int) $permGroup . "', "
                . "perm_members='" . (int) $permMembers . "', perm_anon='" . (int) $permAnon . "'");
        }
    }

    return true;
}
'''

regex_once(
    r"function DOCUMENTS_uploadImage \(\$image_name=array\(\), \$input_name=array\(\), \$fields=array\(\), \$creation\) \{.*?\n\}\n\nfunction DOCUMENTS_saveMarker",
    upload_function + "\nfunction DOCUMENTS_saveMarker",
    'upload function rewrite',
    re.S
)

# Marker IDs are string SIDs, not numbers.
replace_once("\t\t\t  'mkid'           => 'number',", "\t\t\t  'mkid'           => 'alpha',", 'mkid filter type')

# Make marker persistence resilient to missing request keys and quote string marker IDs.
marker_function = r'''function DOCUMENTS_saveMarker ($mid, $mkid, $doc_url) {

    global $_TABLES, $_DOCUMENTS_CONF;

    if (!DOCUMENTS_hasMaps()) {
        return '';
    }

    $doc_url = addslashes((string) $doc_url);
    $mkid = addslashes((string) $mkid);
    $mid = (int) $mid;

    $sql = "SELECT v.field_id, f.f_type, f.sel_id, v.v_value, s.s_value, d.hits, d.doc_url, "
        . "d.active, d.owner_id, d.group_id, d.perm_owner, d.perm_group, d.perm_members, d.perm_anon, c.cat_url "
        . "FROM {$_TABLES['documents_values']} AS v "
        . "LEFT JOIN {$_TABLES['documents_fields']} AS f ON f.fid = v.field_id "
        . "LEFT JOIN {$_TABLES['documents_selects']} AS s ON s.s_name = v.v_value "
        . "LEFT JOIN {$_TABLES['documents_docs']} AS d ON d.doc_url = v.doc_url "
        . "LEFT JOIN {$_TABLES['documents_cat']} AS c ON c.cid = f.cat_id "
        . "WHERE v.doc_url = '{$doc_url}' ORDER BY f.f_order LIMIT 1";

    $res = DB_query($sql);
    $A = DB_fetchArray($res);
    if (!is_array($A)) {
        $A = array('v_value' => '', 'cat_url' => '');
    }

    $name = addslashes(isset($A['v_value']) ? $A['v_value'] : '');
    $created = date('YmdHis');
    $modified = date('YmdHis');
    $web = $_DOCUMENTS_CONF['documents_folder'] . '/'
        . (isset($A['cat_url']) ? $A['cat_url'] : '') . '/' . $doc_url;

    $address = addslashes((string) DOCUMENTS_requestValue($_REQUEST, 'address'));
    $lat = (float) DOCUMENTS_requestValue($_REQUEST, 'lat', 0);
    $lng = (float) DOCUMENTS_requestValue($_REQUEST, 'lng', 0);
    $ownerId = (int) DOCUMENTS_requestValue($_REQUEST, 'owner_id', 0);
    $groupId = (int) DOCUMENTS_requestValue($_REQUEST, 'group_id', 0);
    $permOwner = DOCUMENTS_requestValue($_REQUEST, 'perm_owner', 3);
    $permGroup = DOCUMENTS_requestValue($_REQUEST, 'perm_group', 3);
    $permMembers = DOCUMENTS_requestValue($_REQUEST, 'perm_members', 2);
    $permAnon = DOCUMENTS_requestValue($_REQUEST, 'perm_anon', 2);

    if (is_array($permOwner) || is_array($permGroup) || is_array($permMembers) || is_array($permAnon)) {
        list($permOwner, $permGroup, $permMembers, $permAnon) = SEC_getPermissionValues(
            $permOwner,
            $permGroup,
            $permMembers,
            $permAnon
        );
    }

    $markerExists = ($mkid !== '')
        ? DB_getItem($_TABLES['maps_markers'], 'mid', "mkid='{$mkid}'")
        : '';

    if ($mkid !== '' && $markerExists !== '') {
        $sql = "UPDATE {$_TABLES['maps_markers']} SET "
            . "name='{$name}', modified='{$modified}', address='{$address}', "
            . "lat='{$lat}', lng='{$lng}', mid='{$mid}', url='" . addslashes($web) . "', "
            . "type='documents', owner_id='{$ownerId}', group_id='{$groupId}', "
            . "perm_owner='" . (int) $permOwner . "', perm_group='" . (int) $permGroup . "', "
            . "perm_members='" . (int) $permMembers . "', perm_anon='" . (int) $permAnon . "' "
            . "WHERE mkid='{$mkid}'";
    } else {
        $mkid = COM_makeSid();
        $mkidSql = addslashes($mkid);
        $sql = "INSERT INTO {$_TABLES['maps_markers']} SET "
            . "mkid='{$mkidSql}', name='{$name}', created='{$created}', modified='{$modified}', "
            . "address='{$address}', lat='{$lat}', lng='{$lng}', mid='{$mid}', "
            . "url='" . addslashes($web) . "', type='documents', owner_id='{$ownerId}', group_id='{$groupId}', "
            . "perm_owner='" . (int) $permOwner . "', perm_group='" . (int) $permGroup . "', "
            . "perm_members='" . (int) $permMembers . "', perm_anon='" . (int) $permAnon . "'";
    }

    DB_query($sql);
    if (function_exists('updateMap')) {
        updateMap($mid);
    }

    return $mkid;
}
'''

regex_once(
    r"function DOCUMENTS_saveMarker \(\$mid, \$mkid, \$doc_url\) \{.*?\n\}\n\n//Filter vars",
    marker_function + "\n//Filter vars",
    'marker function rewrite',
    re.S
)

# Guard the most common route parameters after filtering.
replace_once(
    "\tcase 'view':\n\t\n\t    if ($_REQUEST['cat'] != '') {\n\n\t        if ($_REQUEST['doc'] != '') {",
    "\tcase 'view':\n\t\t$viewCat = DOCUMENTS_requestValue($_REQUEST, 'cat');\n\t\t$viewDoc = DOCUMENTS_requestValue($_REQUEST, 'doc');\n\n\t    if ($viewCat != '') {\n\n\t        if ($viewDoc != '') {",
    'view route request guards'
)
replace_once(
    "\t\t\t\t$content = DOCUMENTS_displayDocument( $_REQUEST['cat'], $_REQUEST['doc']);",
    "\t\t\t\t$content = DOCUMENTS_displayDocument($viewCat, $viewDoc);",
    'view document route values'
)
replace_once(
    "\t\t\t\t$content = DOCUMENTS_listDocs($_REQUEST['cat']);",
    "\t\t\t\t$content = DOCUMENTS_listDocs($viewCat);",
    'view list route values'
)

if text == original:
    raise RuntimeError('No changes were produced')

path.write_text(text, encoding='utf-8')
print('include_html.php second cleanup pass applied successfully')
