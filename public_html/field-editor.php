<?php

/* Modern Documents field editor. PHP 5.6+. */

require_once dirname(__DIR__) . '/lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)
    || !SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'admin_styles.php';
require_once $pluginPath . 'admin_mutations.php';
require_once $pluginPath . 'field_mutations.php';
DOCUMENTS_loadAdminStyles();

$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

$text = $isFrench ? array(
    'title_new' => 'Créer un champ',
    'title_edit' => 'Modifier le champ',
    'lead' => 'Un champ définit une information d’un document. Choisissez d’abord sa catégorie et son nom ; la variable technique est générée automatiquement, mais reste modifiable.',
    'back' => 'Retour aux champs',
    'identity' => '1. Identité et catégorie',
    'display' => '2. Type et affichage',
    'advanced' => '3. Options avancées et permissions',
    'name' => 'Nom du champ',
    'name_help' => 'Nom visible dans les formulaires, par exemple « Nom du plugin », « Date de sortie » ou « Description ».',
    'variable' => 'Variable name',
    'variable_help' => 'Générée automatiquement à partir du nom. Vous pouvez la modifier. Elle doit commencer par une lettre et contenir uniquement lettres, chiffres et tirets bas, sur 18 caractères maximum. Elle est utilisée dans les templates sous la forme {variable}.',
    'category' => 'Catégorie',
    'category_help' => 'Le champ appartient à une seule catégorie. Un champ déjà utilisé par des documents ne peut plus être déplacé vers une autre catégorie.',
    'order' => 'Ordre',
    'order_help' => 'L’ordre est automatiquement proposé par pas de 10. Vous pouvez le modifier avant l’enregistrement.',
    'type' => 'Type de champ',
    'type_help' => 'Le type détermine le contrôle de saisie et la manière dont la valeur est stockée. Un champ déjà utilisé ne peut pas changer directement de type.',
    'text_format' => 'Format d’affichage du texte',
    'selection_group' => 'Groupe de choix',
    'selection_group_help' => 'Obligatoire pour un champ de type liste ou boutons radio. Gérez les groupes et leurs options depuis « Groupes de choix ».',
    'help' => 'Aide affichée à l’utilisateur',
    'help_help' => 'Ajoutez une indication courte si le sens du champ ou le format attendu n’est pas évident.',
    'required' => 'Champ obligatoire',
    'on_list' => 'Afficher ce champ dans les listes de documents',
    'permissions_help' => 'Ces permissions suivent le modèle Geeklog. Conservez les valeurs par défaut sauf besoin spécifique.',
    'save' => 'Enregistrer',
    'delete' => 'Supprimer',
    'action' => 'Action',
    'danger' => 'Supprimer un champ supprime également les valeurs de ce champ dans les documents existants. Cette action doit rester exceptionnelle.',
    'groups' => 'Groupes de choix',
    'no_category' => 'Aucune catégorie n’existe encore. Créez d’abord une catégorie.',
    'raw' => 'Tel que saisi',
    'lower' => 'minuscules',
    'upper' => 'MAJUSCULES',
    'sentence' => 'Première lettre en majuscule',
    'title_case' => 'Initiale de chaque mot en majuscule'
) : array(
    'title_new' => 'Create a field',
    'title_edit' => 'Edit field',
    'lead' => 'A field defines one piece of information in a document. Choose its category and name first; the technical variable is generated automatically but remains editable.',
    'back' => 'Back to fields',
    'identity' => '1. Identity and category',
    'display' => '2. Type and display',
    'advanced' => '3. Advanced options and permissions',
    'name' => 'Field name',
    'name_help' => 'Visible label used in forms, for example “Plugin name”, “Release date” or “Description”.',
    'variable' => 'Variable name',
    'variable_help' => 'Generated automatically from the field name. You can edit it. It must begin with a letter and contain only letters, digits and underscores, up to 18 characters. Templates use it as {variable}.',
    'category' => 'Category',
    'category_help' => 'A field belongs to one category. A field already used by documents cannot later be moved to another category.',
    'order' => 'Order',
    'order_help' => 'The next order is proposed automatically in steps of 10. You can edit it before saving.',
    'type' => 'Field type',
    'type_help' => 'The type controls input and storage. A field already used by documents cannot directly change type.',
    'text_format' => 'Text display format',
    'selection_group' => 'Selection group',
    'selection_group_help' => 'Required for selection-list or radio-button fields. Manage groups and their options from “Selection groups”.',
    'help' => 'Help shown to users',
    'help_help' => 'Add short guidance when the meaning or expected format is not obvious.',
    'required' => 'Required field',
    'on_list' => 'Show this field in document lists',
    'permissions_help' => 'These permissions follow Geeklog’s standard model. Keep defaults unless you have a specific need.',
    'save' => 'Save',
    'delete' => 'Delete',
    'action' => 'Action',
    'danger' => 'Deleting a field also deletes that field’s values from existing documents. Use this action only when necessary.',
    'groups' => 'Selection groups',
    'no_category' => 'No category exists yet. Create a category first.',
    'raw' => 'As entered',
    'lower' => 'lowercase',
    'upper' => 'UPPERCASE',
    'sentence' => 'First letter uppercase',
    'title_case' => 'Each Word Capitalized'
);

$siteUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
$fid = isset($_GET['field']) ? max(0, (int) $_GET['field']) : 0;
$field = array(
    'fid' => 0, 'f_name' => '', 'cat_id' => 0, 'f_order' => 10, 'f_type' => 'text',
    'sel_id' => 0, 'var_name' => '', 'f_help' => '', 'f_required' => 0, 'f_on_list' => 0,
    'owner_id' => isset($_USER['uid']) ? (int) $_USER['uid'] : 1,
    'group_id' => 1, 'perm_owner' => '', 'perm_group' => '', 'perm_members' => '', 'perm_anon' => ''
);

if ($fid > 0) {
    $result = DB_query("SELECT * FROM {$_TABLES['documents_fields']} WHERE fid={$fid} LIMIT 1");
    $row = DB_fetchArray($result);
    if (!is_array($row) || empty($row['fid'])) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
    $field = array_merge($field, $row);
} else {
    $requestedCategory = isset($_GET['cat']) ? max(0, (int) $_GET['cat']) : 0;
    if ($requestedCategory > 0) {
        $field['cat_id'] = $requestedCategory;
    }
    SEC_setDefaultPermissions($field, $_DOCUMENTS_CONF['default_permissions']);
    $adminGroup = (int) DB_getItem($_TABLES['groups'], 'grp_id', "grp_name='Documents Admin'");
    $field['group_id'] = $adminGroup > 0 ? $adminGroup : 1;
}

$categories = array();
$nextOrders = array();
$categoryResult = DB_query("SELECT cid, cat_name FROM {$_TABLES['documents_cat']} ORDER BY cat_order ASC, cat_name ASC");
while ($category = DB_fetchArray($categoryResult)) {
    if (!is_array($category)) {
        continue;
    }
    $cid = (int) $category['cid'];
    $categories[] = $category;
    $maxOrder = (int) DB_getItem($_TABLES['documents_fields'], 'MAX(f_order)', 'cat_id=' . $cid);
    $nextOrders[$cid] = $maxOrder + 10;
}
if ($fid === 0 && (int) $field['cat_id'] <= 0 && !empty($categories)) {
    $field['cat_id'] = (int) $categories[0]['cid'];
}
if ($fid === 0 && (int) $field['cat_id'] > 0 && isset($nextOrders[(int) $field['cat_id']])) {
    $field['f_order'] = $nextOrders[(int) $field['cat_id']];
}

$selectionGroups = array();
$groupResult = DB_query("SELECT gid, g_name FROM {$_TABLES['documents_selects_group']} ORDER BY g_name ASC");
while ($selectionGroup = DB_fetchArray($groupResult)) {
    if (is_array($selectionGroup)) {
        $selectionGroups[] = $selectionGroup;
    }
}

$title = $fid > 0 ? $text['title_edit'] : $text['title_new'];
$token = SEC_createToken();
$content = '<main class="documents-admin-page">'
    . DOCUMENTS_adminNavigation('edit_field')
    . '<header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p class="documents-admin-page__lead">'
    . htmlspecialchars($text['lead'], ENT_QUOTES, 'UTF-8') . '</p></header>';

if (!empty($_GET['msg'])) {
    $content .= '<div class="documents-admin-message">'
        . htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}

$content .= '<div class="documents-admin-toolbar"><a class="documents-admin-button" href="'
    . htmlspecialchars($siteUrl . '/index.php?mode=list_fields', ENT_QUOTES, 'UTF-8') . '">← '
    . htmlspecialchars($text['back'], ENT_QUOTES, 'UTF-8') . '</a><a class="documents-admin-button" href="'
    . htmlspecialchars($siteUrl . '/index.php?mode=list_groups', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($text['groups'], ENT_QUOTES, 'UTF-8') . '</a></div>';

if (empty($categories)) {
    $content .= '<p class="documents-admin-empty">' . htmlspecialchars($text['no_category'], ENT_QUOTES, 'UTF-8') . '</p></main>';
    $content = DOCUMENTS_wrapBlock($content, 'admin', 'edit_field');
    $pageOptions = array('pagetitle' => $title);
    COM_output(COM_createHTMLDocument($content, $pageOptions));
    exit;
}

$content .= '<form class="documents-admin-form" action="' . htmlspecialchars($siteUrl . '/index.php', ENT_QUOTES, 'UTF-8') . '" method="post">';
$content .= '<section class="documents-admin-form__section"><h2>' . htmlspecialchars($text['identity'], ENT_QUOTES, 'UTF-8') . '</h2>';
$content .= '<div class="documents-admin-form__grid">';
$content .= '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-field-name">'
    . htmlspecialchars($text['name'], ENT_QUOTES, 'UTF-8') . ' <span class="documents-required-mark">*</span></label>'
    . '<input class="documents-admin-form__control" id="documents-field-name" type="text" name="f_name" maxlength="255" required="required" value="'
    . htmlspecialchars(stripslashes((string) $field['f_name']), ENT_QUOTES, 'UTF-8') . '"><span class="documents-admin-form__help">'
    . htmlspecialchars($text['name_help'], ENT_QUOTES, 'UTF-8') . '</span></div>';
$content .= '<div class="documents-admin-form__row documents-admin-form__row--important"><label class="documents-admin-form__label" for="documents-variable-name">'
    . htmlspecialchars($text['variable'], ENT_QUOTES, 'UTF-8') . ' <span class="documents-required-mark">*</span></label>'
    . '<input class="documents-admin-form__control documents-admin-code-input" id="documents-variable-name" type="text" name="var_name" maxlength="18" pattern="[A-Za-z][A-Za-z0-9_]{0,17}" value="'
    . htmlspecialchars((string) $field['var_name'], ENT_QUOTES, 'UTF-8') . '" data-existing="' . ($fid > 0 ? '1' : '0') . '"><span class="documents-admin-form__help">'
    . htmlspecialchars($text['variable_help'], ENT_QUOTES, 'UTF-8') . '</span><span class="documents-admin-variable-preview">{<strong id="documents-variable-preview">'
    . htmlspecialchars((string) $field['var_name'], ENT_QUOTES, 'UTF-8') . '</strong>}</span></div>';
$content .= '</div>';

$content .= '<div class="documents-admin-form__grid">';
$content .= '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-field-category">'
    . htmlspecialchars($text['category'], ENT_QUOTES, 'UTF-8') . ' <span class="documents-required-mark">*</span></label><select class="documents-admin-form__control" id="documents-field-category" name="cat_id" required="required">';
foreach ($categories as $category) {
    $selected = ((int) $category['cid'] === (int) $field['cat_id']) ? ' selected="selected"' : '';
    $content .= '<option value="' . (int) $category['cid'] . '"' . $selected . '>'
        . htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select><span class="documents-admin-form__help">' . htmlspecialchars($text['category_help'], ENT_QUOTES, 'UTF-8') . '</span></div>';
$content .= '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-field-order">'
    . htmlspecialchars($text['order'], ENT_QUOTES, 'UTF-8') . '</label><input class="documents-admin-form__control documents-admin-form__control--short" id="documents-field-order" type="number" min="0" step="10" name="f_order" value="'
    . (int) $field['f_order'] . '"><span class="documents-admin-form__help">' . htmlspecialchars($text['order_help'], ENT_QUOTES, 'UTF-8') . '</span></div></div></section>';

$typeLabels = array(
    'text' => 'Text', 'textarea' => 'Textarea', 'decimal' => 'Decimal', 'date' => 'Date',
    'image' => 'Image', 'checkbox' => 'Checkbox', 'select' => ($isFrench ? 'Liste de choix' : 'Selection list'),
    'radio' => ($isFrench ? 'Boutons radio' : 'Radio buttons'),
    'category' => ($isFrench ? 'Catégorie' : 'Category'), 'marker' => 'Map marker', 'album' => 'MediaGallery album'
);
$content .= '<section class="documents-admin-form__section"><h2>' . htmlspecialchars($text['display'], ENT_QUOTES, 'UTF-8') . '</h2>';
$content .= '<div class="documents-admin-form__grid">';
$content .= '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-field-type">'
    . htmlspecialchars($text['type'], ENT_QUOTES, 'UTF-8') . '</label><select class="documents-admin-form__control" id="documents-field-type" name="f_type">';
foreach (DOCUMENTS_fieldAllowedTypes() as $type) {
    $selected = ((string) $field['f_type'] === $type) ? ' selected="selected"' : '';
    $label = isset($typeLabels[$type]) ? $typeLabels[$type] : $type;
    $content .= '<option value="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select><span class="documents-admin-form__help">' . htmlspecialchars($text['type_help'], ENT_QUOTES, 'UTF-8') . '</span></div>';

$textSel = ((string) $field['f_type'] === 'text') ? (int) $field['sel_id'] : 0;
$content .= '<div class="documents-admin-form__row" id="documents-text-format-row"><label class="documents-admin-form__label" for="documents-text-format">'
    . htmlspecialchars($text['text_format'], ENT_QUOTES, 'UTF-8') . '</label><select class="documents-admin-form__control" id="documents-text-format" name="sel_id">';
$formats = array(0 => $text['raw'], 1001 => $text['lower'], 1002 => $text['upper'], 1003 => $text['sentence'], 1004 => $text['title_case']);
foreach ($formats as $value => $label) {
    $selected = ($textSel === (int) $value) ? ' selected="selected"' : '';
    $content .= '<option value="' . (int) $value . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select></div>';

$groupSel = in_array((string) $field['f_type'], array('select', 'radio'), true) ? (int) $field['sel_id'] : 0;
$content .= '<div class="documents-admin-form__row" id="documents-selection-group-row"><label class="documents-admin-form__label" for="documents-selection-group">'
    . htmlspecialchars($text['selection_group'], ENT_QUOTES, 'UTF-8') . '</label><select class="documents-admin-form__control" id="documents-selection-group" name="sel_id"><option value="0">—</option>';
foreach ($selectionGroups as $selectionGroup) {
    $selected = ((int) $selectionGroup['gid'] === $groupSel) ? ' selected="selected"' : '';
    $content .= '<option value="' . (int) $selectionGroup['gid'] . '"' . $selected . '>'
        . htmlspecialchars(stripslashes((string) $selectionGroup['g_name']), ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select><span class="documents-admin-form__help">' . htmlspecialchars($text['selection_group_help'], ENT_QUOTES, 'UTF-8') . '</span></div></div>';

$content .= '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-field-help">'
    . htmlspecialchars($text['help'], ENT_QUOTES, 'UTF-8') . '</label><textarea class="documents-admin-form__control" id="documents-field-help" name="f_help" maxlength="255">'
    . htmlspecialchars(stripslashes((string) $field['f_help']), ENT_QUOTES, 'UTF-8') . '</textarea><span class="documents-admin-form__help">'
    . htmlspecialchars($text['help_help'], ENT_QUOTES, 'UTF-8') . '</span></div>';
$content .= '<div class="documents-admin-check-grid"><label><input type="checkbox" name="f_required" value="1"' . (!empty($field['f_required']) ? ' checked="checked"' : '') . '> '
    . htmlspecialchars($text['required'], ENT_QUOTES, 'UTF-8') . '</label><label><input type="checkbox" name="f_on_list" value="1"' . (!empty($field['f_on_list']) ? ' checked="checked"' : '') . '> '
    . htmlspecialchars($text['on_list'], ENT_QUOTES, 'UTF-8') . '</label></div></section>';

$content .= '<details class="documents-admin-form__section documents-admin-advanced"><summary><strong>'
    . htmlspecialchars($text['advanced'], ENT_QUOTES, 'UTF-8') . '</strong></summary><div class="documents-admin-advanced__body"><p class="documents-admin-muted">'
    . htmlspecialchars($text['permissions_help'], ENT_QUOTES, 'UTF-8') . '</p>';
$content .= '<input type="hidden" name="owner_id" value="' . (int) $field['owner_id'] . '">';
$content .= '<div class="documents-admin-form__row"><label class="documents-admin-form__label">Group</label>'
    . SEC_getGroupDropdown((int) $field['group_id'], 3) . '</div>';
$content .= '<div class="documents-admin-permissions">'
    . SEC_getPermissionsHTML(
        $field['perm_owner'], $field['perm_group'], $field['perm_members'], $field['perm_anon']
    ) . '</div></div></details>';

$content .= '<div class="documents-admin-form__actions"><label for="documents-field-action"><strong>'
    . htmlspecialchars($text['action'], ENT_QUOTES, 'UTF-8') . '</strong></label><select id="documents-field-action" name="op"><option value="save">'
    . htmlspecialchars($text['save'], ENT_QUOTES, 'UTF-8') . '</option>';
if ($fid > 0) {
    $content .= '<option value="delete">' . htmlspecialchars($text['delete'], ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select><input class="documents-admin-submit" type="submit" value="' . htmlspecialchars($text['save'], ENT_QUOTES, 'UTF-8') . '"></div>';
if ($fid > 0) {
    $content .= '<p class="documents-admin-danger-note">' . htmlspecialchars($text['danger'], ENT_QUOTES, 'UTF-8') . '</p>';
}
$content .= '<input type="hidden" name="mode" value="save_field"><input type="hidden" name="fid" value="' . (int) $fid . '">'
    . '<input type="hidden" name="' . htmlspecialchars(CSRF_TOKEN, ENT_QUOTES, 'UTF-8') . '" value="'
    . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '"></form></main>';

$ordersJson = json_encode($nextOrders);
$js = "(function(){"
    . "var name=document.getElementById('documents-field-name');"
    . "var variable=document.getElementById('documents-variable-name');"
    . "var preview=document.getElementById('documents-variable-preview');"
    . "var category=document.getElementById('documents-field-category');"
    . "var order=document.getElementById('documents-field-order');"
    . "var type=document.getElementById('documents-field-type');"
    . "var textRow=document.getElementById('documents-text-format-row');"
    . "var textSelect=document.getElementById('documents-text-format');"
    . "var groupRow=document.getElementById('documents-selection-group-row');"
    . "var groupSelect=document.getElementById('documents-selection-group');"
    . "var orders=" . $ordersJson . ";"
    . "var manual=variable.getAttribute('data-existing')==='1'||variable.value!=='';"
    . "function normalize(v){if(v.normalize){v=v.normalize('NFD').replace(/[\\u0300-\\u036f]/g,'');}v=v.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');if(!v){return '';}if(!/^[a-z]/.test(v)){v='field_'+v;}return v.substring(0,18).replace(/_+$/,'');}"
    . "function showPreview(){preview.textContent=variable.value;}"
    . "name.addEventListener('input',function(){if(!manual){variable.value=normalize(name.value);showPreview();}});"
    . "variable.addEventListener('input',function(){manual=true;variable.value=normalize(variable.value);showPreview();});"
    . "category.addEventListener('change',function(){if(variable.getAttribute('data-existing')!=='1'&&orders[this.value]){order.value=orders[this.value];}});"
    . "function typeState(){var isText=type.value==='text';var usesGroup=type.value==='select'||type.value==='radio';textRow.style.display=isText?'':'none';textSelect.disabled=!isText;groupRow.style.display=usesGroup?'':'none';groupSelect.disabled=!usesGroup;}"
    . "type.addEventListener('change',typeState);typeState();showPreview();"
    . "})();";
if (isset($_SCRIPTS) && is_object($_SCRIPTS) && method_exists($_SCRIPTS, 'setJavaScript')) {
    $_SCRIPTS->setJavaScript($js, true);
}

$content = DOCUMENTS_wrapBlock($content, 'admin', 'edit_field');
$pageOptions = array('pagetitle' => $title);
COM_output(COM_createHTMLDocument($content, $pageOptions));
