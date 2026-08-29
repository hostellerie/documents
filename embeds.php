<?php

/* Documents autotags and reusable block renderers. Compatible with PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'embeds.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_renderItemLink($item, $label = '')
{
    if (!is_array($item) || empty($item['url'])) {
        return '';
    }

    if ($label === '') {
        $label = isset($item['title']) ? $item['title'] : $item['id'];
    }

    return COM_createLink(
        htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'),
        $item['url']
    );
}

function DOCUMENTS_renderItemCard($item)
{
    if (!is_array($item) || empty($item['url'])) {
        return '';
    }

    $title = isset($item['title']) ? (string) $item['title'] : (string) $item['id'];
    $excerpt = isset($item['excerpt']) ? trim((string) $item['excerpt']) : '';
    $image = isset($item['image']) ? trim((string) $item['image']) : '';

    $html = '<article class="documents-card">';
    if ($image !== '') {
        $html .= '<a href="' . htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') . '">'
            . '<img class="documents-card__image" src="'
            . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" loading="lazy"></a>';
    }
    $html .= '<div class="documents-card__body"><h3 class="documents-card__title">'
        . DOCUMENTS_renderItemLink($item) . '</h3>';
    if ($excerpt !== '') {
        $html .= '<p class="documents-card__excerpt">'
            . htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $html .= '</div></article>';

    return $html;
}

function DOCUMENTS_renderCompactItems($items)
{
    if (!is_array($items) || empty($items)) {
        return '';
    }

    $html = '<ul class="documents-compact-list">';
    foreach ($items as $item) {
        $link = DOCUMENTS_renderItemLink($item);
        if ($link !== '') {
            $html .= '<li>' . $link . '</li>';
        }
    }
    $html .= '</ul>';

    return $html;
}

function DOCUMENTS_itemsByCategory($categorySlug, $limit)
{
    global $_TABLES;

    $categorySlug = trim((string) $categorySlug);
    $limit = max(1, min(50, (int) $limit));
    if ($categorySlug === '') {
        return array();
    }

    $safeCategory = DB_escapeString($categorySlug);
    $sql = "SELECT DISTINCT d.doc_url FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "INNER JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE d.active=1 AND c.cat_url='{$safeCategory}'"
        . COM_getPermSQL('AND', 0, 2, 'd')
        . " ORDER BY COALESCE(d.modified,d.created) DESC, d.did DESC LIMIT {$limit}";

    $result = DB_query($sql);
    $items = array();
    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['doc_url'])) {
            continue;
        }
        $item = DOCUMENTS_interopItem($row['doc_url'], 0);
        if (!empty($item)) {
            $items[] = $item;
        }
    }

    return $items;
}

function DOCUMENTS_popularItems($limit)
{
    global $_TABLES;

    $limit = max(1, min(50, (int) $limit));
    $sql = "SELECT d.doc_url FROM {$_TABLES['documents_docs']} AS d WHERE d.active=1"
        . COM_getPermSQL('AND', 0, 2, 'd')
        . " ORDER BY d.hits DESC, COALESCE(d.modified,d.created) DESC LIMIT {$limit}";

    $result = DB_query($sql);
    $items = array();
    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['doc_url'])) {
            continue;
        }
        $item = DOCUMENTS_interopItem($row['doc_url'], 0);
        if (!empty($item)) {
            $items[] = $item;
        }
    }

    return $items;
}

/**
 * Autotags:
 * [document:document-id]
 * [document:document-id card]
 * [document:document-id Custom link text]
 * [documents:category-slug]
 * [documents:category-slug 10]
 */
function plugin_autotags_documents($op, $content = '', $autotag = array())
{
    if ($op === 'tagname') {
        return array('document', 'documents');
    }
    if ($op === 'permission') {
        return array('document', 'documents');
    }
    if ($op === 'nopermission') {
        return array();
    }
    if ($op === 'description') {
        return array(
            'document' => 'Link to a Documents item, or render it as a card with the card option.',
            'documents' => 'Render a compact list of recent items from a Documents category.'
        );
    }
    if ($op === 'closetag') {
        return array();
    }
    if ($op !== 'parse' || !is_array($autotag) || empty($autotag['tagstr'])) {
        return $content;
    }

    $tag = isset($autotag['tag']) ? (string) $autotag['tag'] : '';
    $parm1 = isset($autotag['parm1']) ? trim((string) $autotag['parm1']) : '';
    $parm2 = isset($autotag['parm2']) ? trim((string) $autotag['parm2']) : '';
    $replacement = '';

    if ($tag === 'document' && $parm1 !== '') {
        $item = DOCUMENTS_interopItem($parm1, 0);
        if (!empty($item)) {
            if (strtolower($parm2) === 'card') {
                $replacement = DOCUMENTS_renderItemCard($item);
            } else {
                $replacement = DOCUMENTS_renderItemLink($item, $parm2);
            }
        }
    } elseif ($tag === 'documents' && $parm1 !== '') {
        $limit = ctype_digit($parm2) ? (int) $parm2 : 5;
        $replacement = DOCUMENTS_renderCompactItems(DOCUMENTS_itemsByCategory($parm1, $limit));
    }

    return str_replace($autotag['tagstr'], $replacement, $content);
}

/** Geeklog PHP block: latest publicly accessible Documents items. */
function phpblock_documents_recent()
{
    $items = DOCUMENTS_interopItems('id,title,url', 0, array(
        'limit' => 5,
        'order' => 'modified-desc'
    ));

    return DOCUMENTS_renderCompactItems($items);
}

/** Geeklog PHP block: most viewed publicly accessible Documents items. */
function phpblock_documents_popular()
{
    return DOCUMENTS_renderCompactItems(DOCUMENTS_popularItems(5));
}
