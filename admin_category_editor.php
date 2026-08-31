<?php

/* Standalone category editor used by Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower((string) $_SERVER['PHP_SELF']), 'admin_category_editor.php') !== false) {
    die('This file can not be used on its own.');
}

if ((!isset($LANG_DOCUMENTS_1) || !is_array($LANG_DOCUMENTS_1)) && isset($_CONF['path'])) {
    $documentsLanguage = isset($_CONF['language']) ? (string) $_CONF['language'] : 'english';
    $documentsLanguageFile = $_CONF['path'] . 'plugins/documents/language/' . $documentsLanguage . '.php';
    if (!is_file($documentsLanguageFile)) {
        $documentsLanguageFile = $_CONF['path'] . 'plugins/documents/language/english.php';
    }
    if (is_file($documentsLanguageFile)) {
        require_once $documentsLanguageFile;
    }
}

function DOCUMENTS_renderCategoryEditor($categoryId)
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;
    global $LANG_ACCESS, $_USER, $_GROUPS;

    if (!isset($LANG_DOCUMENTS_1) || !is_array($LANG_DOCUMENTS_1)) {
        $LANG_DOCUMENTS_1 = array();
    }

    $lang = function ($key, $fallback) use (&$LANG_DOCUMENTS_1) {
        return isset($LANG_DOCUMENTS_1[$key]) && $LANG_DOCUMENTS_1[$key] !== ''
            ? $LANG_DOCUMENTS_1[$key]
            : $fallback;
    };

    $categoryId = (int) $categoryId;
    $category = array(
        'cid' => '', 'cat_name' => '', 'cat_url' => '', 'css' => '', 'map' => 0,
        'template' => '', 'cat_order' => '', 'list_index' => 1, 'submitable' => 1,
        'cat_help' => '', 'metadescription' => '', 'custom_header' => '',
        'custom_footer' => '', 'owner_id' => '', 'group_id' => '',
        'perm_owner' => '', 'perm_group' => '', 'perm_members' => '', 'perm_anon' => ''
    );

    if ($categoryId > 0) {
        $categoryResult = DB_query(
            "SELECT * FROM {$_TABLES['documents_cat']} WHERE cid={$categoryId} LIMIT 1"
        );
        $row = DB_fetchArray($categoryResult);
        if (!is_array($row) || empty($row['cid'])) {
            $errorLabel = $lang('error', 'Error');
            $errorText = '<p>' . htmlspecialchars($errorLabel, ENT_QUOTES, 'UTF-8') . '</p>';
            return COM_createHTMLDocument($errorText, array('pagetitle' => $errorLabel));
        }
        $category = array_merge($category, $row);
    }

    if ($category['cat_order'] === '') {
        $category['cat_order'] = (int) DB_getItem(
            $_TABLES['documents_cat'],
            'MAX(cat_order)',
            '1=1'
        ) + 10;
    }

    if ($category['perm_owner'] === '') {
        SEC_setDefaultPermissions($category, $_DOCUMENTS_CONF['default_permissions']);
    }
    if ($category['owner_id'] === '') {
        $category['owner_id'] = isset($_USER['uid']) ? (int) $_USER['uid'] : 1;
    }
    if ($category['group_id'] === '') {
        $category['group_id'] = isset($_GROUPS['Documents Admin'])
            ? (int) $_GROUPS['Documents Admin'] : 1;
    }

    $template = COM_newTemplate($_CONF['path'] . 'plugins/documents/templates');
    $template->set_file(array('cat' => 'cat_form.thtml'));
    $template->set_var('doc_url', $_DOCUMENTS_CONF['site_url']);
    $template->set_var('xhtml', XHTML);

    $token = SEC_createToken();
    $template->set_var('gltoken_name', CSRF_TOKEN);
    $template->set_var('gltoken', $token);

    $editCatLabel = $lang('edit_cat', 'Edit a category');
    $newCatLabel = $lang('new_cat', 'Create a new category');
    $template->set_var(
        'cat_informations',
        $categoryId > 0
            ? $editCatLabel . ' ' . htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8')
            : $newCatLabel
    );
    $template->set_var(
        'cid',
        $categoryId > 0
            ? '<input type="hidden" name="cid" value="' . $categoryId . '"' . XHTML . '>'
            : ''
    );

    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

    $help = $isFrench ? array(
        'metadescription_label' => 'Méta description SEO',
        'metadescription_intro' => 'Décrivez ici, en langage naturel, le contenu que le visiteur trouvera dans cette catégorie.',
        'metadescription_placeholder' => 'Ex. : Découvrez les fiches pratiques consacrées aux énergies renouvelables, avec conseils, ressources et retours d’expérience.',
        'metadescription_help' => 'Visez environ 135 à 160 caractères. Utilisez une ou deux phrases uniques, avec le sujet principal naturellement formulé.',
        'category_help_title' => 'Créer et configurer une catégorie de documents',
        'category_help_intro' => 'Une catégorie définit un type de contenu. Commencez par son nom et son URL, puis configurez son affichage, sa publication et ses permissions.',
        'general_legend' => '1. Informations générales',
        'display_legend' => '2. Affichage et intégrations',
        'publication_legend' => '3. Publication',
        'permissions_legend' => '4. Propriétaire et permissions',
        'category_help' => 'Saisissez le nom public du type de contenu.',
        'cat_url_help' => 'Adresse courte utilisée dans l’URL.',
        'cat_help_explanation' => 'Texte d’aide affiché dans le formulaire de création d’un document.',
        'template_help' => 'Option avancée. Laissez vide pour utiliser le rendu standard.',
        'css_help' => 'Option avancée. Laissez vide pour conserver le style standard.',
        'custom_header_help' => 'HTML facultatif affiché au-dessus de la liste des documents.',
        'custom_footer_help' => 'HTML facultatif affiché sous la liste des documents.',
        'cat_order_help' => 'Utilisez de préférence 10, 20, 30…',
        'list_index_help' => 'Affiche cette catégorie sur la page principale de Documents.',
        'submitable_help' => 'Permet aux membres connectés de proposer des documents.',
        'permissions_help' => 'Ces réglages déterminent qui peut voir et administrer la catégorie.',
        'owner_help' => 'Compte propriétaire de la catégorie.',
        'group_help' => 'Groupe Geeklog associé à la catégorie.',
        'permissions_editor_help' => 'Attribuez les droits selon le modèle Geeklog.',
        'action_label' => 'Action',
        'action_help' => 'Sauvegarder ou supprimer la catégorie.',
        'map_help' => 'Associez une carte si nécessaire.'
    ) : array(
        'metadescription_label' => 'SEO meta description',
        'metadescription_intro' => 'Describe what visitors will find in this category.',
        'metadescription_placeholder' => 'Example: Browse practical guides and related resources.',
        'metadescription_help' => 'Aim for about 135–160 characters.',
        'category_help_title' => 'Create and configure a document category',
        'category_help_intro' => 'A category defines a content type.',
        'general_legend' => '1. General information',
        'display_legend' => '2. Display and integrations',
        'publication_legend' => '3. Publication',
        'permissions_legend' => '4. Owner and permissions',
        'category_help' => 'Enter the public content-type name.',
        'cat_url_help' => 'Short address used in the URL.',
        'cat_help_explanation' => 'Help text shown on the document creation form.',
        'template_help' => 'Leave blank to use the standard rendering.',
        'css_help' => 'Leave blank to keep the standard styling.',
        'custom_header_help' => 'Optional HTML above the document list.',
        'custom_footer_help' => 'Optional HTML below the document list.',
        'cat_order_help' => 'Prefer 10, 20, 30…',
        'list_index_help' => 'Show this category on the main Documents page.',
        'submitable_help' => 'Allow logged-in members to submit documents.',
        'permissions_help' => 'Controls who can see and administer the category.',
        'owner_help' => 'Category owner.',
        'group_help' => 'Geeklog group for this category.',
        'permissions_editor_help' => 'Assign rights using the Geeklog model.',
        'action_label' => 'Action',
        'action_help' => 'Save or delete the category.',
        'map_help' => 'Associate a map if required.'
    );

    foreach ($help as $key => $value) {
        $template->set_var($key, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }

    $vars = array(
        'category_label' => $lang('cat_name', 'Category name'),
        'category' => htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8'),
        'cat_url_label' => $lang('cat_url', 'URL name'),
        'cat_url' => htmlspecialchars((string) $category['cat_url'], ENT_QUOTES, 'UTF-8'),
        'metadescription' => htmlspecialchars(stripslashes((string) $category['metadescription']), ENT_QUOTES, 'UTF-8'),
        'cat_help_label' => $lang('cat_help', 'Submission form help'),
        'cat_help' => htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8'),
        'template_label' => $lang('template', 'Template'),
        'template' => htmlspecialchars((string) $category['template'], ENT_QUOTES, 'UTF-8'),
        'css_label' => $lang('css', 'CSS'),
        'css' => htmlspecialchars((string) $category['css'], ENT_QUOTES, 'UTF-8'),
        'custom_header_label' => $lang('custom_header', 'Custom header'),
        'custom_header' => htmlspecialchars(stripslashes((string) $category['custom_header']), ENT_QUOTES, 'UTF-8'),
        'custom_footer_label' => $lang('custom_footer', 'Custom footer'),
        'custom_footer' => htmlspecialchars(stripslashes((string) $category['custom_footer']), ENT_QUOTES, 'UTF-8'),
        'catorder_label' => $lang('cat_order', 'Order'),
        'cat_order' => (int) $category['cat_order'],
        'existing_cat' => $lang('existing_cat', 'Existing categories'),
        'list_index' => $lang('list_index', 'Display this category in the list'),
        'list_index_ckecked' => (int) $category['list_index'] === 1 ? ' checked="checked"' : '',
        'submitable' => $lang('submitable', 'Users can submit documents to this category'),
        'submitable_ckecked' => (int) $category['submitable'] === 1 ? ' checked="checked"' : '',
        'validate_button' => $lang('validate_button', 'OK'),
        'required_field' => $lang('required_field', 'Required field')
    );
    foreach ($vars as $key => $value) {
        $template->set_var($key, $value);
    }

    $categoriesOrder = '';
    $result = DB_query(
        "SELECT cat_order, cat_name, cat_url FROM {$_TABLES['documents_cat']} ORDER BY cat_order ASC, cid ASC"
    );
    while ($row = DB_fetchArray($result)) {
        $categoriesOrder .= (int) $row['cat_order'] . '. '
            . htmlspecialchars(stripslashes((string) $row['cat_name']), ENT_QUOTES, 'UTF-8')
            . ' | ' . htmlspecialchars((string) $row['cat_url'], ENT_QUOTES, 'UTF-8')
            . '<br' . XHTML . '>';
    }
    if ($categoriesOrder === '') {
        $categoriesOrder = htmlspecialchars($lang('none', 'None'), ENT_QUOTES, 'UTF-8');
    }
    $template->set_var('categories_order', '<blockquote>' . $categoriesOrder . '</blockquote>');

    if (DOCUMENTS_hasMaps() && function_exists('MAPS_recurseMaps')) {
        $mapOptions = MAPS_recurseMaps((int) $category['map']);
        $map = '<div><p><label>' . htmlspecialchars($lang('use_map', 'Use map'), ENT_QUOTES, 'UTF-8')
            . '</label> <select id="map" name="map"><option value="0">'
            . htmlspecialchars($lang('no_map', 'No map'), ENT_QUOTES, 'UTF-8')
            . '</option>' . $mapOptions . '</select></p></div>';
        $template->set_var('map', $map);
    } else {
        $template->set_var('map', '');
    }

    $options = '<select name="op"><option value="save" selected="selected">'
        . htmlspecialchars($lang('save_button', 'Save'), ENT_QUOTES, 'UTF-8') . '</option>';
    if ($categoryId > 0) {
        $options .= '<option value="delete">'
            . htmlspecialchars($lang('delete_button', 'Delete'), ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $options .= '</select>';
    $template->set_var('admin_options', $options);

    $ownerName = COM_getDisplayName((int) $category['owner_id']);
    $template->set_var('lang_owner', isset($LANG_ACCESS['owner']) ? $LANG_ACCESS['owner'] : ($isFrench ? 'Propriétaire' : 'Owner'));
    $template->set_var('owner_name', htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'));
    $template->set_var('owner_id', (int) $category['owner_id']);
    $template->set_var('lang_group', isset($LANG_ACCESS['group']) ? $LANG_ACCESS['group'] : ($isFrench ? 'Groupe' : 'Group'));
    $template->set_var('group_dropdown', SEC_getGroupDropdown((int) $category['group_id'], 3));
    $template->set_var(
        'permissions_editor',
        SEC_getPermissionsHTML(
            $category['perm_owner'],
            $category['perm_group'],
            $category['perm_members'],
            $category['perm_anon']
        )
    );
    $template->set_var('lang_perm_key', isset($LANG_ACCESS['permissionskey']) ? $LANG_ACCESS['permissionskey'] : 'Permissions');
    $template->set_var('lang_permissions_msg', isset($LANG_ACCESS['permmsg']) ? $LANG_ACCESS['permmsg'] : '');

    $content = $template->parse('output', 'cat');
    if (function_exists('DOCUMENTS_adminNavigation')) {
        $content = DOCUMENTS_adminNavigation('edit_cat') . $content;
    }

    $pageTitle = $categoryId > 0 ? $editCatLabel : $newCatLabel;
    return COM_createHTMLDocument($content, array('pagetitle' => $pageTitle));
}
