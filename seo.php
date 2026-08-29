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

function DOCUMENTS_seoPageNumber()
{
    if (!isset($_GET['page'])) {
        return 1;
    }

    return max(1, (int) $_GET['page']);
}

function DOCUMENTS_seoContext()
{
    global $_CONF, $_DOCUMENTS_CONF, $LANG_DOCUMENTS_1;

    $mode = isset($_REQUEST['mode']) ? trim((string) $_REQUEST['mode']) : '';
    $categorySlug = isset($_REQUEST['cat']) ? trim((string) $_REQUEST['cat']) : '';
    $documentId = isset($_REQUEST['doc']) ? trim((string) $_REQUEST['doc']) : '';

    if ($mode === 'view' && $documentId !== '') {
        $item = DOCUMENTS_interopItem($documentId, 0);
        if (!empty($item)) {
            $description = trim((string) $item['excerpt']);
            if ($description === '') {
                $description = DOCUMENTS_seoFallbackDescription($item['title'], $item['category']);
            }

            return array(
                'title' => $item['title'],
                'description' => $description,
                'canonical' => $item['url'],
                'image' => isset($item['image']) ? $item['image'] : '',
                'type' => 'article',
                'schema_type' => 'CreativeWork',
                'created' => isset($item['date-created']) ? (int) $item['date-created'] : 0,
                'modified' => isset($item['date-modified']) ? (int) $item['date-modified'] : 0,
                'author' => isset($item['author']) ? $item['author'] : '',
                'category' => isset($item['category']) ? $item['category'] : '',
                'page' => 1
            );
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
                'description' => $description,
                'canonical' => $canonical,
                'image' => '',
                'type' => 'website',
                'schema_type' => 'CollectionPage',
                'created' => 0,
                'modified' => 0,
                'author' => '',
                'category' => '',
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
        'page' => 1
    );
}

function DOCUMENTS_seoJsonLd($context)
{
    global $_CONF;

    $data = array(
        '@context' => 'https://schema.org',
        '@type' => isset($context['schema_type']) ? $context['schema_type'] : 'WebPage',
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
        $data['isPartOf'] = array('@type' => 'WebSite', 'name' => $_CONF['site_name'], 'url' => $_CONF['site_url']);
    }

    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
    $siteName = isset($_CONF['site_name']) ? $_CONF['site_name'] : '';

    $header = '<link rel="canonical" href="' . DOCUMENTS_seoEscape($canonical) . '"' . XHTML . '>' . LB;
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
        '/<meta\s+name=["\']twitter:[^"\']+["\'][^>]*>\s*/i',
        '/<meta\s+property=["\'](?:og:[^"\']+|fb:app_id)["\'][^>]*>\s*/i'
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
