<?php

/* Documents public SEO helpers. Compatible with PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'seo.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_seoEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function DOCUMENTS_seoCategory($slug)
{
    global $_TABLES;

    $slug = trim((string) $slug);
    if ($slug === '') {
        return array();
    }

    $safeSlug = DB_escapeString($slug);
    $row = DB_fetchArray(DB_query(
        "SELECT cid, cat_name, cat_url, metadescription, owner_id, group_id, "
        . "perm_owner, perm_group, perm_members, perm_anon "
        . "FROM {$_TABLES['documents_cat']} WHERE cat_url='{$safeSlug}' LIMIT 1"
    ));

    if (!is_array($row) || empty($row['cat_url'])) {
        return array();
    }

    $access = SEC_hasAccess(
        (int) $row['owner_id'],
        (int) $row['group_id'],
        (int) $row['perm_owner'],
        (int) $row['perm_group'],
        (int) $row['perm_members'],
        (int) $row['perm_anon']
    );
    if ($access < 2) {
        return array();
    }

    return $row;
}

function DOCUMENTS_seoFallbackDescription($title, $category = '')
{
    global $_CONF;

    $parts = array(trim((string) $title));
    if ($category !== '' && $category !== $title) {
        $parts[] = trim((string) $category);
    }
    if (!empty($_CONF['site_name'])) {
        $parts[] = trim((string) $_CONF['site_name']);
    }

    return DOCUMENTS_interopExcerpt(implode(' - ', array_filter($parts, 'strlen')), 160);
}

function DOCUMENTS_seoSchemaType($value)
{
    $value = trim((string) $value);
    $allowed = array(
        'CreativeWork', 'Article', 'NewsArticle', 'BlogPosting', 'TechArticle',
        'HowTo', 'Recipe', 'Dataset', 'Report', 'DigitalDocument'
    );

    foreach ($allowed as $type) {
        if (strcasecmp($type, $value) === 0) {
            return $type;
        }
    }

    return 'CreativeWork';
}

function DOCUMENTS_seoDocument($categorySlug, $documentId)
{
    global $_TABLES, $_DOCUMENTS_CONF;

    $categorySlug = trim((string) $categorySlug);
    $documentId = trim((string) $documentId);
    if ($categorySlug === '' || $documentId === '') {
        return array();
    }

    $safeCategory = DB_escapeString($categorySlug);
    $safeDocument = DB_escapeString($documentId);
    $row = DB_fetchArray(DB_query(
        "SELECT d.doc_url, d.active, d.created, d.modified, d.owner_id, "
        . "d.group_id, d.perm_owner, d.perm_group, d.perm_members, d.perm_anon, "
        . "c.cid, c.cat_name, c.cat_url, c.owner_id AS cat_owner_id, "
        . "c.group_id AS cat_group_id, c.perm_owner AS cat_perm_owner, "
        . "c.perm_group AS cat_perm_group, c.perm_members AS cat_perm_members, "
        . "c.perm_anon AS cat_perm_anon "
        . "FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "INNER JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE d.doc_url='{$safeDocument}' AND c.cat_url='{$safeCategory}' LIMIT 1"
    ));

    if (!is_array($row) || empty($row['doc_url']) || empty($row['cid'])) {
        return array();
    }

    if (SEC_hasAccess(
        (int) $row['cat_owner_id'],
        (int) $row['cat_group_id'],
        (int) $row['cat_perm_owner'],
        (int) $row['cat_perm_group'],
        (int) $row['cat_perm_members'],
        (int) $row['cat_perm_anon']
    ) < 2) {
        return array();
    }

    if (SEC_hasAccess(
        (int) $row['owner_id'],
        (int) $row['group_id'],
        (int) $row['perm_owner'],
        (int) $row['perm_group'],
        (int) $row['perm_members'],
        (int) $row['perm_anon']
    ) < 2) {
        return array();
    }

    $fieldsResult = DB_query(
        "SELECT f.fid, f.f_order, f.f_type, f.var_name, v.v_value "
        . "FROM {$_TABLES['documents_fields']} AS f "
        . "LEFT JOIN {$_TABLES['documents_values']} AS v "
        . "ON v.field_id=f.fid AND v.doc_url='{$safeDocument}' "
        . "WHERE f.cat_id=" . (int) $row['cid'] . " ORDER BY f.f_order ASC, f.fid ASC"
    );

    $title = '';
    $titleFieldId = 0;
    $metaDescription = '';
    $schemaType = 'CreativeWork';
    $descriptionParts = array();
    $image = '';

    while ($field = DB_fetchArray($fieldsResult)) {
        if (!is_array($field)) {
            continue;
        }

        $value = isset($field['v_value']) ? trim(stripslashes((string) $field['v_value'])) : '';
        $variable = isset($field['var_name']) ? strtolower(trim((string) $field['var_name'])) : '';
        $type = isset($field['f_type']) ? strtolower((string) $field['f_type']) : '';

        if ($variable === 'metadescription') {
            if ($value !== '') {
                $metaDescription = DOCUMENTS_interopExcerpt($value, 160);
            }
            continue;
        }

        if ($variable === 'schema_type') {
            if ($value !== '') {
                $schemaType = DOCUMENTS_seoSchemaType($value);
            }
            continue;
        }

        if ($title === '' && $value !== '' && ($type === 'text' || $type === 'textarea')) {
            $title = DOCUMENTS_interopExcerpt($value, 120);
            $titleFieldId = isset($field['fid']) ? (int) $field['fid'] : 0;
            continue;
        }

        if ($image === '' && $type === 'image' && $value !== '') {
            $filename = basename($value);
            if ($filename !== '' && isset($_DOCUMENTS_CONF['images_url'])) {
                $image = rtrim((string) $_DOCUMENTS_CONF['images_url'], '/')
                    . '/' . rawurlencode($filename);
            }
            continue;
        }

        if ($value !== ''
            && ($type === 'text' || $type === 'textarea')
            && (int) $field['fid'] !== $titleFieldId) {
            $descriptionParts[] = $value;
        }
    }

    if ($title === '') {
        $title = (string) $row['doc_url'];
    }

    $description = $metaDescription;
    if ($description === '' && !empty($descriptionParts)) {
        $description = DOCUMENTS_interopExcerpt(implode(' ', $descriptionParts), 160);
    }
    if ($description === '') {
        $description = DOCUMENTS_seoFallbackDescription(
            $title,
            stripslashes((string) $row['cat_name'])
        );
    }

    $created = DOCUMENTS_interopTimestamp(isset($row['created']) ? $row['created'] : '');
    $modified = DOCUMENTS_interopTimestamp(isset($row['modified']) ? $row['modified'] : '');
    if ($modified <= 0) {
        $modified = $created;
    }

    $status = (int) $row['active'];
    $robots = 'index,follow';
    if ($status === DOCUMENTS_STATUS_INACTIVE) {
        $robots = 'noindex,follow';
    } elseif ($status === DOCUMENTS_STATUS_DRAFT
        || $status === DOCUMENTS_STATUS_SUBMISSION) {
        $robots = 'noindex,nofollow';
    }

    return array(
        'title' => $title,
        'description' => $description,
        'canonical' => DOCUMENTS_interopCanonicalUrl($row['cat_url'], $row['doc_url']),
        'image' => $image,
        'type' => 'article',
        'schema_type' => $schemaType,
        'created' => $created,
        'modified' => $modified,
        'author' => COM_getDisplayName((int) $row['owner_id']),
        'category' => stripslashes((string) $row['cat_name']),
        'category_slug' => (string) $row['cat_url'],
        'robots' => $robots,
        'page' => 1
    );
}

function DOCUMENTS_seoPageNumber()
{
    if (!isset($_GET['page'])) {
        return 1;
    }

    return max(1, (int) $_GET['page']);
}

function DOCUMENTS_seoContext()
{
    global $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1;

    $mode = isset($_REQUEST['mode']) ? trim((string) $_REQUEST['mode']) : '';
    $categorySlug = isset($_REQUEST['cat']) ? trim((string) $_REQUEST['cat']) : '';
    $documentId = isset($_REQUEST['doc']) ? trim((string) $_REQUEST['doc']) : '';

    if ($mode === 'view' && $documentId !== '' && $categorySlug !== '') {
        $document = DOCUMENTS_seoDocument($categorySlug, $documentId);
        if (!empty($document)) {
            return $document;
        }
    }

    if ($mode === 'view' && $categorySlug !== '') {
        $category = DOCUMENTS_seoCategory($categorySlug);
        if (!empty($category)) {
            $title = stripslashes((string) $category['cat_name']);
            $description = trim(stripslashes((string) $category['metadescription']));
            if ($description === '') {
                $description = DOCUMENTS_seoFallbackDescription($title);
            }

            $page = DOCUMENTS_seoPageNumber();
            $canonical = DOCUMENTS_interopCanonicalUrl($category['cat_url']);
            if ($page > 1) {
                $canonical .= '?page=' . $page;
                $title .= ' - Page ' . $page;
            }

            return array(
                'title' => $title,
                'description' => DOCUMENTS_interopExcerpt($description, 160),
                'canonical' => $canonical,
                'image' => '',
                'type' => 'website',
                'schema_type' => 'CollectionPage',
                'created' => 0,
                'modified' => 0,
                'author' => '',
                'category' => '',
                'category_slug' => '',
                'robots' => 'index,follow',
                'page' => $page
            );
        }
    }

    $title = isset($LANG_DOCUMENTS_1['plugin_name']) ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
    $description = DOCUMENTS_seoFallbackDescription($title);

    return array(
        'title' => $title,
        'description' => $description,
        'canonical' => rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/',
        'image' => '',
        'type' => 'website',
        'schema_type' => 'CollectionPage',
        'created' => 0,
        'modified' => 0,
        'author' => '',
        'category' => '',
        'category_slug' => '',
        'robots' => 'index,follow',
        'page' => 1
    );
}

function DOCUMENTS_seoCreativeWorkJsonLd($context)
{
    global $_CONF;

    $data = array(
        '@type' => isset($context['schema_type']) ? $context['schema_type'] : 'CreativeWork',
        'name' => isset($context['title']) ? $context['title'] : '',
        'description' => isset($context['description']) ? $context['description'] : '',
        'url' => isset($context['canonical']) ? $context['canonical'] : ''
    );

    if (!empty($context['image'])) {
        $data['image'] = $context['image'];
    }
    if (!empty($context['created'])) {
        $data['dateCreated'] = gmdate('c', (int) $context['created']);
    }
    if (!empty($context['modified'])) {
        $data['dateModified'] = gmdate('c', (int) $context['modified']);
    }
    if (!empty($context['author'])) {
        $data['author'] = array('@type' => 'Person', 'name' => $context['author']);
    }
    if (!empty($_CONF['site_name'])) {
        $data['isPartOf'] = array(
            '@type' => 'WebSite',
            'name' => $_CONF['site_name'],
            'url' => $_CONF['site_url']
        );
    }

    return $data;
}

function DOCUMENTS_seoBreadcrumbJsonLd($context)
{
    global $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1;

    if (empty($context['category']) || empty($context['category_slug']) || empty($context['title'])) {
        return array();
    }

    $documentsName = isset($LANG_DOCUMENTS_1['plugin_name'])
        ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
    $documentsUrl = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/';
    $categoryUrl = DOCUMENTS_interopCanonicalUrl($context['category_slug']);

    return array(
        '@type' => 'BreadcrumbList',
        'itemListElement' => array(
            array(
                '@type' => 'ListItem',
                'position' => 1,
                'name' => $documentsName,
                'item' => $documentsUrl
            ),
            array(
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $context['category'],
                'item' => $categoryUrl
            ),
            array(
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $context['title'],
                'item' => $context['canonical']
            )
        )
    );
}

function DOCUMENTS_seoJsonLd($context)
{
    $graph = array(DOCUMENTS_seoCreativeWorkJsonLd($context));
    $breadcrumb = DOCUMENTS_seoBreadcrumbJsonLd($context);
    if (!empty($breadcrumb)) {
        $graph[] = $breadcrumb;
    }

    return json_encode(
        array('@context' => 'https://schema.org', '@graph' => $graph),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}

function DOCUMENTS_seoHeaderCode()
{
    global $_CONF;

    $context = DOCUMENTS_seoContext();
    if (empty($context) || empty($context['canonical'])) {
        return '';
    }

    $title = isset($context['title']) ? $context['title'] : '';
    $description = isset($context['description']) ? $context['description'] : '';
    $canonical = $context['canonical'];
    $image = isset($context['image']) ? $context['image'] : '';
    $robots = isset($context['robots']) ? $context['robots'] : 'index,follow';
    $siteName = isset($_CONF['site_name']) ? $_CONF['site_name'] : '';

    $header = '<link rel="canonical" href="' . DOCUMENTS_seoEscape($canonical) . '"' . XHTML . '>' . LB;
    $header .= '<meta name="robots" content="' . DOCUMENTS_seoEscape($robots) . '"' . XHTML . '>' . LB;
    $header .= '<meta name="description" content="' . DOCUMENTS_seoEscape($description) . '"' . XHTML . '>' . LB;
    $header .= '<meta property="og:title" content="' . DOCUMENTS_seoEscape($title) . '"' . XHTML . '>' . LB;
    $header .= '<meta property="og:description" content="' . DOCUMENTS_seoEscape($description) . '"' . XHTML . '>' . LB;
    $header .= '<meta property="og:type" content="' . DOCUMENTS_seoEscape($context['type']) . '"' . XHTML . '>' . LB;
    $header .= '<meta property="og:url" content="' . DOCUMENTS_seoEscape($canonical) . '"' . XHTML . '>' . LB;
    if ($siteName !== '') {
        $header .= '<meta property="og:site_name" content="' . DOCUMENTS_seoEscape($siteName) . '"' . XHTML . '>' . LB;
    }
    if ($image !== '') {
        $header .= '<meta property="og:image" content="' . DOCUMENTS_seoEscape($image) . '"' . XHTML . '>' . LB;
        $header .= '<meta name="twitter:card" content="summary_large_image"' . XHTML . '>' . LB;
        $header .= '<meta name="twitter:image" content="' . DOCUMENTS_seoEscape($image) . '"' . XHTML . '>' . LB;
    } else {
        $header .= '<meta name="twitter:card" content="summary"' . XHTML . '>' . LB;
    }
    $header .= '<meta name="twitter:title" content="' . DOCUMENTS_seoEscape($title) . '"' . XHTML . '>' . LB;
    $header .= '<meta name="twitter:description" content="' . DOCUMENTS_seoEscape($description) . '"' . XHTML . '>' . LB;

    $json = DOCUMENTS_seoJsonLd($context);
    if ($json !== false && $json !== '') {
        $header .= '<script type="application/ld+json">' . $json . '</script>' . LB;
    }

    return $header;
}

function DOCUMENTS_seoRemoveManagedTags($html)
{
    $patterns = array(
        '/<link\b[^>]*\brel=["\']canonical["\'][^>]*>\s*/i',
        '/<link\b[^>]*\bhref=["\'][^"\']+["\'][^>]*\brel=["\']canonical["\'][^>]*>\s*/i',
        '/<meta\s+name=["\']description["\'][^>]*>\s*/i',
        '/<meta\s+name=["\']robots["\'][^>]*>\s*/i',
        '/<meta\s+name=["\']twitter:[^"\']+["\'][^>]*>\s*/i',
        '/<meta\s+property=["\'](?:og:[^"\']+|fb:app_id)["\'][^>]*>\s*/i',
        '/<script\s+type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>\s*/is'
    );

    return preg_replace($patterns, '', $html);
}

function DOCUMENTS_seoOutputFilter($html)
{
    if (!is_string($html) || stripos($html, '</head>') === false) {
        return $html;
    }

    $html = DOCUMENTS_seoRemoveManagedTags($html);
    $header = DOCUMENTS_seoHeaderCode();
    if ($header === '') {
        return $html;
    }

    return preg_replace('/<\/head>/i', $header . '</head>', $html, 1);
}
