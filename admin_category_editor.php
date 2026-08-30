<?php

/* Standalone category editor used by Documents 1.2.0. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'admin_category_editor.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_renderCategoryEditor($categoryId)
{
    global $_CONF, $_DOCUMENTS_CONF, $_TABLES, $LANG_DOCUMENTS_1;
    global $LANG_ACCESS, $_USER, $_GROUPS;

    $categoryId = (int) $categoryId;
    $category = array(
        'cid' => '',
        'cat_name' => '',
        'cat_url' => '',
        'css' => '',
        'map' => 0,
        'template' => '',
        'cat_order' => '',
        'list_index' => 1,
        'submitable' => 1,
        'cat_help' => '',
        'metadescription' => '',
        'custom_header' => '',
        'custom_footer' => '',
        'owner_id' => '',
        'group_id' => '',
        'perm_owner' => '',
        'perm_group' => '',
        'perm_members' => '',
        'perm_anon' => ''
    );

    if ($categoryId > 0) {
        /* Geeklog 2.1.1 / PHP 5.6: DB_fetchArray expects a variable by
         * reference, so never pass DB_query() directly. */
        $categoryResult = DB_query(
            "SELECT * FROM {$_TABLES['documents_cat']} WHERE cid={$categoryId} LIMIT 1"
        );
        $row = DB_fetchArray($categoryResult);
        if (!is_array($row) || empty($row['cid'])) {
            $errorText = '<p>'
                . htmlspecialchars(
                    isset($LANG_DOCUMENTS_1['error']) ? $LANG_DOCUMENTS_1['error'] : 'Error',
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '</p>';
            $errorOptions = array(
                'pagetitle' => isset($LANG_DOCUMENTS_1['error'])
                    ? $LANG_DOCUMENTS_1['error']
                    : 'Error'
            );
            return COM_createHTMLDocument($errorText, $errorOptions);
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

    $template->set_var(
        'cat_informations',
        $categoryId > 0
            ? $LANG_DOCUMENTS_1['edit_cat'] . ' ' . htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8')
            : $LANG_DOCUMENTS_1['new_cat']
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
        'metadescription_label' => 'Méta description (SEO)',
        'metadescription_help' => 'Saisissez en une ou deux phrases ce que l’utilisateur trouvera dans cette catégorie. Visez environ 135 à 160 caractères, avec les mots importants du sujet, sans liste de mots-clés ni copier le titre.',
        'category_help_title' => 'À quoi sert une catégorie ?',
        'category_help_intro' => 'Une catégorie définit un type de document. Après l’avoir créée, vous devez lui ajouter des champs (titre, texte, image, album, carte, etc.) avant de pouvoir créer un document.',
        'general_legend' => 'Informations générales',
        'display_legend' => 'Affichage et intégrations',
        'publication_legend' => 'Publication',
        'permissions_legend' => 'Propriétaire et permissions',
        'category_help' => 'Saisissez le nom public du type de contenu, par exemple « Canyons », « Livres » ou « Fiches pratiques ». Ce nom est affiché aux visiteurs.',
        'cat_url_help' => 'Adresse courte utilisée dans l’URL. À la création, elle est générée automatiquement depuis le nom de la catégorie (minuscules, sans accents, mots séparés par des tirets). Vous pouvez ensuite la modifier manuellement.',
        'cat_help_explanation' => 'Saisissez une courte consigne destinée à la personne qui crée un document : ce qu’elle doit renseigner, le niveau de détail attendu ou une règle particulière. Peut contenir un autotag Geeklog.',
        'template_help' => 'Option avancée : indiquez uniquement le nom d’un template Documents personnalisé existant. Laissez vide pour utiliser le template standard.',
        'css_help' => 'Option avancée : indiquez uniquement le nom du style ou fichier CSS prévu pour cette catégorie. Laissez vide pour conserver le style standard.',
        'custom_header_help' => 'HTML facultatif affiché au-dessus de la liste des documents. Utilisez-le pour une introduction, un avertissement ou un contenu éditorial propre à cette catégorie.',
        'custom_footer_help' => 'HTML facultatif affiché sous la liste des documents. Utilisez-le pour des liens complémentaires, une note ou du contenu éditorial de fin de page.',
        'cat_order_help' => 'Nombre déterminant la position de la catégorie dans les listes. Utilisez de préférence 10, 20, 30… ; le plus petit nombre apparaît en premier.',
        'list_index_help' => 'Cochez pour afficher cette catégorie sur la page principale de Documents. Décochez pour la garder accessible uniquement par son URL ou des liens directs.',
        'submitable_help' => 'Cochez pour permettre aux membres connectés de proposer des documents. Décochez si seuls les administrateurs doivent pouvoir en créer.',
        'permissions_help' => 'Définissez qui peut voir cette catégorie. Les permissions de lecture sont essentielles : pour une catégorie publique, accordez la lecture aux anonymes ; pour une catégorie réservée, limitez-la aux membres ou au groupe choisi.',
        'owner_help' => 'Compte propriétaire de la catégorie. Il est défini à la création et sert de référence pour les permissions propriétaire.',
        'group_help' => 'Choisissez le groupe Geeklog qui doit bénéficier des permissions « Groupe » définies ci-dessous.',
        'permissions_editor_help' => 'Attribuez les droits de lecture/écriture au propriétaire, au groupe, aux membres et aux anonymes. Évitez d’accorder l’écriture aux anonymes.',
        'action_label' => 'Action',
        'action_help' => 'Choisissez « Sauvegarder » pour enregistrer les paramètres. La suppression n’est proposée que pour une catégorie existante.',
        'map_help' => 'Associe cette catégorie à une carte du plugin Maps. Choisissez une carte seulement si chaque document doit être lié à un marqueur géographique.'
    ) : array(
        'metadescription_label' => 'Meta description (SEO)',
        'metadescription_help' => 'Write one or two sentences describing what users will find in this category. Aim for about 135–160 characters, include the main topic naturally, and do not use a keyword list or simply repeat the title.',
        'category_help_title' => 'What is a category?',
        'category_help_intro' => 'A category defines a document type. After creating it, add fields (title, text, image, album, map, etc.) before creating the first document.',
        'general_legend' => 'General information',
        'display_legend' => 'Display and integrations',
        'publication_legend' => 'Publication',
        'permissions_legend' => 'Owner and permissions',
        'category_help' => 'Enter the public content-type name, for example “Canyons”, “Books” or “How-to guides”. Visitors will see this name.',
        'cat_url_help' => 'Short address used in the URL. When creating a category it is generated automatically from the category name (lowercase, no accents, words separated by hyphens). You can edit it manually afterward.',
        'cat_help_explanation' => 'Enter a short instruction for people creating a document: what to provide, expected detail, or a special rule. It may contain a Geeklog autotag.',
        'template_help' => 'Advanced option: enter only the name of an existing custom Documents template. Leave blank to use the standard template.',
        'css_help' => 'Advanced option: enter only the style or CSS filename intended for this category. Leave blank to keep the standard styling.',
        'custom_header_help' => 'Optional HTML displayed above the document list. Use it for an introduction, warning, or category-specific editorial content.',
        'custom_footer_help' => 'Optional HTML displayed below the document list. Use it for related links, notes, or end-of-page editorial content.',
        'cat_order_help' => 'Number controlling the category position in lists. Prefer 10, 20, 30…; the lowest number appears first.',
        'list_index_help' => 'Check to show this category on the main Documents page. Uncheck to keep it reachable only through its URL or direct links.',
        'submitable_help' => 'Check to let logged-in members submit documents. Uncheck if only administrators should create them.',
        'permissions_help' => 'Define who can view this category. Read permissions are essential: grant anonymous read access for a public category, or restrict it to members/the selected group.',
        'owner_help' => 'Account that owns the category. It is set when the category is created and is used by owner permissions.',
        'group_help' => 'Choose the Geeklog group that receives the “Group” permissions configured below.',
        'permissions_editor_help' => 'Assign read/write rights to owner, group, members and anonymous users. Avoid granting write permission to anonymous users.',
        'action_label' => 'Action',
        'action_help' => 'Choose “Save” to store the settings. Delete is available only for an existing category.',
        'map_help' => 'Associates this category with a Maps plugin map. Select a map only when every document should be linked to a geographic marker.'
    );

    foreach ($help as $key => $value) {
        $template->set_var($key, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }

    $vars = array(
        'category_label' => $LANG_DOCUMENTS_1['cat_name'],
        'category' => htmlspecialchars(stripslashes((string) $category['cat_name']), ENT_QUOTES, 'UTF-8'),
        'cat_url_label' => $LANG_DOCUMENTS_1['cat_url'],
        'cat_url' => htmlspecialchars((string) $category['cat_url'], ENT_QUOTES, 'UTF-8'),
        'metadescription' => htmlspecialchars(stripslashes((string) $category['metadescription']), ENT_QUOTES, 'UTF-8'),
        'cat_help_label' => $LANG_DOCUMENTS_1['cat_help'],
        'cat_help' => htmlspecialchars(stripslashes((string) $category['cat_help']), ENT_QUOTES, 'UTF-8'),
        'template_label' => $LANG_DOCUMENTS_1['template'],
        'template' => htmlspecialchars((string) $category['template'], ENT_QUOTES, 'UTF-8'),
        'css_label' => $LANG_DOCUMENTS_1['css'],
        'css' => htmlspecialchars((string) $category['css'], ENT_QUOTES, 'UTF-8'),
        'custom_header_label' => $LANG_DOCUMENTS_1['custom_header'],
        'custom_header' => htmlspecialchars(stripslashes((string) $category['custom_header']), ENT_QUOTES, 'UTF-8'),
        'custom_footer_label' => $LANG_DOCUMENTS_1['custom_footer'],
        'custom_footer' => htmlspecialchars(stripslashes((string) $category['custom_footer']), ENT_QUOTES, 'UTF-8'),
        'catorder_label' => $LANG_DOCUMENTS_1['cat_order'],
        'cat_order' => (int) $category['cat_order'],
        'existing_cat' => $LANG_DOCUMENTS_1['existing_cat'],
        'list_index' => $LANG_DOCUMENTS_1['list_index'],
        'list_index_ckecked' => (int) $category['list_index'] === 1 ? ' checked="checked"' : '',
        'submitable' => $LANG_DOCUMENTS_1['submitable'],
        'submitable_ckecked' => (int) $category['submitable'] === 1 ? ' checked="checked"' : '',
        'validate_button' => $LANG_DOCUMENTS_1['validate_button'],
        'required_field' => $LANG_DOCUMENTS_1['required_field']
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
        $categoriesOrder = htmlspecialchars($LANG_DOCUMENTS_1['none'], ENT_QUOTES, 'UTF-8');
    }
    $template->set_var('categories_order', '<blockquote>' . $categoriesOrder . '</blockquote>');

    if (DOCUMENTS_hasMaps() && function_exists('MAPS_recurseMaps')) {
        $map = '<div><p><label>' . htmlspecialchars($LANG_DOCUMENTS_1['use_map'], ENT_QUOTES, 'UTF-8') . '</label> '
            . '<select id="map" name="map"><option value="0">'
            . htmlspecialchars($LANG_DOCUMENTS_1['no_map'], ENT_QUOTES, 'UTF-8') . '</option>'
            . MAPS_recurseMaps((int) $category['map']) . '</select><br' . XHTML . '>'
            . '<small>' . htmlspecialchars($help['map_help'], ENT_QUOTES, 'UTF-8') . '</small></p></div>';
        $template->set_var('map', $map);
    } else {
        $template->set_var('map', '');
    }

    $options = '<select name="op" id="documents-category-action"><option value="save" selected="selected">'
        . htmlspecialchars($LANG_DOCUMENTS_1['save_button'], ENT_QUOTES, 'UTF-8') . '</option>';
    if ($categoryId > 0) {
        $options .= '<option value="delete">'
            . htmlspecialchars($LANG_DOCUMENTS_1['delete_button'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $options .= '</select>';
    $template->set_var('admin_options', $options);

    $ownerName = COM_getDisplayName((int) $category['owner_id']);
    $template->set_var('lang_owner', $LANG_ACCESS['owner']);
    $template->set_var('owner_name', htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'));
    $template->set_var('owner_id', (int) $category['owner_id']);
    $template->set_var('lang_group', $LANG_ACCESS['group']);
    $groupId = (int) $category['group_id'];
    $groupDropdown = SEC_getGroupDropdown($groupId, 3);
    $template->set_var('group_dropdown', $groupDropdown);
    $permOwner = $category['perm_owner'];
    $permGroup = $category['perm_group'];
    $permMembers = $category['perm_members'];
    $permAnon = $category['perm_anon'];
    $permissionsEditor = SEC_getPermissionsHTML(
        $permOwner,
        $permGroup,
        $permMembers,
        $permAnon
    );
    $template->set_var('permissions_editor', $permissionsEditor);
    $template->set_var('lang_perm_key', $LANG_ACCESS['permissionskey']);
    $template->set_var('lang_permissions_msg', $LANG_ACCESS['permmsg']);

    $content = $template->parse('output', 'cat');
    $pageTitle = $categoryId > 0 ? $LANG_DOCUMENTS_1['edit_cat'] : $LANG_DOCUMENTS_1['new_cat'];
    $pageOptions = array('pagetitle' => $pageTitle);

    return COM_createHTMLDocument($content, $pageOptions);
}
