<?php

/* Modern Documents selection-option editor. PHP 5.6+. */

require_once '../lib-common.php';

if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('documents', $_PLUGINS, true)
    || !SEC_hasRights('documents.admin')) {
    echo COM_refresh($_CONF['site_url'] . '/404.php');
    exit;
}

$pluginPath = $_CONF['path'] . 'plugins/documents/';
require_once $pluginPath . 'runtime.php';
require_once $pluginPath . 'admin_styles.php';
DOCUMENTS_loadAdminStyles();

$isFrench = isset($_CONF['language'])
    && strpos(strtolower((string) $_CONF['language']), 'french') === 0;
$text = $isFrench ? array(
    'title_new' => 'Créer une option de sélection',
    'title_edit' => 'Modifier une option de sélection',
    'lead' => 'Une option appartient à un groupe de choix. Sa valeur interne est enregistrée dans les documents ; son libellé est ce que voit l’utilisateur.',
    'back' => 'Retour aux options',
    'groups' => 'Groupes de choix',
    'group' => 'Groupe de choix',
    'group_help' => 'Choisissez le groupe auquel cette option appartient.',
    'internal' => 'Valeur interne',
    'internal_help' => 'Valeur technique enregistrée dans les documents, par exemple « expert ». Gardez-la courte et stable. Si cette option est déjà utilisée, évitez de la modifier.',
    'label' => 'Libellé affiché',
    'label_help' => 'Texte présenté à l’utilisateur. Vous pouvez le faire évoluer sans changer la logique de la donnée. Laissez vide pour afficher la valeur interne.',
    'order' => 'Ordre',
    'order_help' => 'Les options sont proposées dans cet ordre. Utilisez 10, 20, 30… pour faciliter les insertions futures.',
    'save' => 'Enregistrer',
    'delete' => 'Supprimer',
    'action' => 'Action',
    'danger' => 'La suppression est refusée lorsque cette valeur est encore utilisée par un document.',
    'no_groups' => 'Aucun groupe de choix n’existe. Créez d’abord un groupe.'
) : array(
    'title_new' => 'Create a selection option',
    'title_edit' => 'Edit selection option',
    'lead' => 'An option belongs to a selection group. Its internal value is stored in documents; its label is what users see.',
    'back' => 'Back to options',
    'groups' => 'Selection groups',
    'group' => 'Selection group',
    'group_help' => 'Choose the group this option belongs to.',
    'internal' => 'Internal value',
    'internal_help' => 'Technical value stored in documents, for example “expert”. Keep it short and stable. Avoid changing it once the option is in use.',
    'label' => 'Displayed label',
    'label_help' => 'Text shown to users. You can evolve it without changing the stored meaning. Leave blank to display the internal value.',
    'order' => 'Order',
    'order_help' => 'Options are offered in this order. Use 10, 20, 30… to leave room for future insertions.',
    'save' => 'Save',
    'delete' => 'Delete',
    'action' => 'Action',
    'danger' => 'Deletion is refused while this value is still used by a document.',
    'no_groups' => 'No selection group exists. Create a group first.'
);

$siteUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/');
$sid = isset($_GET['select']) ? max(0, (int) $_GET['select']) : 0;
$option = array('sid' => 0, 's_name' => '', 's_value' => '', 's_group' => 0, 's_order' => 10);
if ($sid > 0) {
    $result = DB_query("SELECT sid, s_name, s_value, s_group, s_order FROM {$_TABLES['documents_selects']} WHERE sid={$sid} LIMIT 1");
    $row = DB_fetchArray($result);
    if (!is_array($row) || empty($row['sid'])) {
        echo COM_refresh($_CONF['site_url'] . '/404.php');
        exit;
    }
    $option = array_merge($option, $row);
} else {
    $requestedGroup = isset($_GET['group']) ? max(0, (int) $_GET['group']) : 0;
    if ($requestedGroup > 0) {
        $option['s_group'] = $requestedGroup;
    }
}

$groups = array();
$nextOrders = array();
$groupResult = DB_query("SELECT gid, g_name FROM {$_TABLES['documents_selects_group']} ORDER BY g_name ASC, gid ASC");
while ($group = DB_fetchArray($groupResult)) {
    if (!is_array($group)) {
        continue;
    }
    $gid = (int) $group['gid'];
    $groups[] = $group;
    $nextOrders[$gid] = (int) DB_getItem($_TABLES['documents_selects'], 'MAX(s_order)', 's_group=' . $gid) + 10;
}
if ($sid === 0 && (int) $option['s_group'] > 0 && isset($nextOrders[(int) $option['s_group']])) {
    $option['s_order'] = $nextOrders[(int) $option['s_group']];
}

$title = $sid > 0 ? $text['title_edit'] : $text['title_new'];
$content = '<main class="documents-admin-page"><header class="documents-admin-page__header"><h1>'
    . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p class="documents-admin-page__lead">'
    . htmlspecialchars($text['lead'], ENT_QUOTES, 'UTF-8') . '</p></header>';
if (!empty($_GET['msg'])) {
    $content .= '<div class="documents-admin-message">' . htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}

$backUrl = $siteUrl . '/index.php?mode=list_selects';
if ((int) $option['s_group'] > 0) {
    $backUrl .= '&group=' . (int) $option['s_group'];
}
$content .= '<div class="documents-admin-toolbar"><a class="documents-admin-button" href="'
    . htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') . '">← ' . htmlspecialchars($text['back'], ENT_QUOTES, 'UTF-8') . '</a>'
    . '<a class="documents-admin-button" href="' . htmlspecialchars($siteUrl . '/index.php?mode=list_groups', ENT_QUOTES, 'UTF-8') . '">'
    . htmlspecialchars($text['groups'], ENT_QUOTES, 'UTF-8') . '</a></div>';

if (empty($groups)) {
    $content .= '<p class="documents-admin-empty">' . htmlspecialchars($text['no_groups'], ENT_QUOTES, 'UTF-8') . '</p></main>';
    $pageOptions = array('pagetitle' => $title);
    COM_output(COM_createHTMLDocument($content, $pageOptions));
    exit;
}

$token = SEC_createToken();
$content .= '<form class="documents-admin-form" action="' . htmlspecialchars($siteUrl . '/index.php', ENT_QUOTES, 'UTF-8') . '" method="post">'
    . '<section class="documents-admin-form__section"><div class="documents-admin-form__grid">'
    . '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-option-group">'
    . htmlspecialchars($text['group'], ENT_QUOTES, 'UTF-8') . ' <span class="documents-required-mark">*</span></label>'
    . '<select class="documents-admin-form__control" id="documents-option-group" name="s_group" required="required">';
foreach ($groups as $group) {
    $selected = ((int) $group['gid'] === (int) $option['s_group']) ? ' selected="selected"' : '';
    $content .= '<option value="' . (int) $group['gid'] . '"' . $selected . '>'
        . htmlspecialchars(stripslashes((string) $group['g_name']), ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select><span class="documents-admin-form__help">' . htmlspecialchars($text['group_help'], ENT_QUOTES, 'UTF-8') . '</span></div>'
    . '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-option-order">'
    . htmlspecialchars($text['order'], ENT_QUOTES, 'UTF-8') . '</label><input class="documents-admin-form__control documents-admin-form__control--short" id="documents-option-order" type="number" min="0" step="10" name="s_order" value="'
    . (int) $option['s_order'] . '"><span class="documents-admin-form__help">' . htmlspecialchars($text['order_help'], ENT_QUOTES, 'UTF-8') . '</span></div></div>';

$content .= '<div class="documents-admin-form__grid"><div class="documents-admin-form__row documents-admin-form__row--important"><label class="documents-admin-form__label" for="documents-option-internal">'
    . htmlspecialchars($text['internal'], ENT_QUOTES, 'UTF-8') . ' <span class="documents-required-mark">*</span></label>'
    . '<input class="documents-admin-form__control documents-admin-code-input" id="documents-option-internal" type="text" name="s_name" maxlength="255" required="required" value="'
    . htmlspecialchars(stripslashes((string) $option['s_name']), ENT_QUOTES, 'UTF-8') . '"><span class="documents-admin-form__help">'
    . htmlspecialchars($text['internal_help'], ENT_QUOTES, 'UTF-8') . '</span></div>'
    . '<div class="documents-admin-form__row"><label class="documents-admin-form__label" for="documents-option-label">'
    . htmlspecialchars($text['label'], ENT_QUOTES, 'UTF-8') . '</label><input class="documents-admin-form__control" id="documents-option-label" type="text" name="s_value" maxlength="255" value="'
    . htmlspecialchars(stripslashes((string) $option['s_value']), ENT_QUOTES, 'UTF-8') . '"><span class="documents-admin-form__help">'
    . htmlspecialchars($text['label_help'], ENT_QUOTES, 'UTF-8') . '</span></div></div></section>';

$content .= '<div class="documents-admin-form__actions"><label for="documents-option-action"><strong>'
    . htmlspecialchars($text['action'], ENT_QUOTES, 'UTF-8') . '</strong></label><select id="documents-option-action" name="op"><option value="save">'
    . htmlspecialchars($text['save'], ENT_QUOTES, 'UTF-8') . '</option>';
if ($sid > 0) {
    $content .= '<option value="delete">' . htmlspecialchars($text['delete'], ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select><input class="documents-admin-submit" type="submit" value="' . htmlspecialchars($text['save'], ENT_QUOTES, 'UTF-8') . '"></div>';
if ($sid > 0) {
    $content .= '<p class="documents-admin-danger-note">' . htmlspecialchars($text['danger'], ENT_QUOTES, 'UTF-8') . '</p>';
}
$content .= '<input type="hidden" name="mode" value="save_select"><input type="hidden" name="sid" value="' . (int) $sid . '">'
    . '<input type="hidden" name="' . htmlspecialchars(CSRF_TOKEN, ENT_QUOTES, 'UTF-8') . '" value="'
    . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '"></form></main>';

$ordersJson = json_encode($nextOrders);
$js = "(function(){var group=document.getElementById('documents-option-group');var order=document.getElementById('documents-option-order');var orders=" . $ordersJson . ";"
    . "if(group&&order){group.addEventListener('change',function(){if(" . ($sid === 0 ? 'true' : 'false') . "&&orders[this.value]){order.value=orders[this.value];}});}})();";
if (isset($_SCRIPTS) && is_object($_SCRIPTS) && method_exists($_SCRIPTS, 'setJavaScript')) {
    $_SCRIPTS->setJavaScript($js, true);
}

$pageOptions = array('pagetitle' => $title);
COM_output(COM_createHTMLDocument($content, $pageOptions));
