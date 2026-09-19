<?php

/* Documents native syndication and statistics callbacks. PHP 5.6+. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'distribution.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_distributionFeedRows($topic, $limits)
{
    global $_TABLES;

    $topic = trim((string) $topic);
    if ($topic === '') {
        $topic = 'all';
    }

    $limit = 10;
    $since = 0;
    $limits = trim((string) $limits);
    if ($limits !== '') {
        if (substr($limits, -1) === 'h') {
            $hours = (int) substr($limits, 0, -1);
            if ($hours > 0) {
                $since = time() - ($hours * 3600);
                $limit = 0;
            }
        } elseif (ctype_digit($limits)) {
            $limit = max(1, min(1000, (int) $limits));
        }
    }

    $sql = "SELECT DISTINCT d.doc_url FROM {$_TABLES['documents_docs']} AS d "
        . "INNER JOIN {$_TABLES['documents_values']} AS v ON v.doc_url=d.doc_url "
        . "INNER JOIN {$_TABLES['documents_fields']} AS f ON f.fid=v.field_id "
        . "INNER JOIN {$_TABLES['documents_cat']} AS c ON c.cid=f.cat_id "
        . "WHERE d.active=1";

    if ($topic !== 'all') {
        $safeTopic = DB_escapeString($topic);
        $sql .= " AND c.cat_url='{$safeTopic}'";
    }
    if ($since > 0) {
        $sql .= " AND UNIX_TIMESTAMP(COALESCE(d.modified,d.created)) >= " . (int) $since;
    }

    $sql .= COM_getPermSQL('AND', 0, 2, 'd')
        . " ORDER BY COALESCE(d.modified,d.created) DESC, d.did DESC";
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }

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

function plugin_getfeednames_documents()
{
    global $_TABLES;

    $feeds = array(array('id' => 'all', 'name' => 'All documents'));
    $sql = "SELECT c.cat_url, c.cat_name FROM {$_TABLES['documents_cat']} AS c WHERE 1=1"
        . COM_getPermSQL('AND', 0, 2, 'c')
        . " ORDER BY c.cat_order ASC, c.cat_name ASC";
    $result = DB_query($sql);

    while ($row = DB_fetchArray($result)) {
        if (is_array($row) && !empty($row['cat_url'])) {
            $feeds[] = array(
                'id' => (string) $row['cat_url'],
                'name' => stripslashes((string) $row['cat_name'])
            );
        }
    }

    return $feeds;
}

function plugin_getfeedcontent_documents($feed, &$link, &$update)
{
    global $_TABLES, $_DOCUMENTS_CONF;

    $feedId = (int) $feed;
    $result = DB_query(
        "SELECT topic, limits, content_length FROM {$_TABLES['syndication']} "
        . "WHERE fid={$feedId} LIMIT 1"
    );
    $feedConfig = DB_fetchArray($result);
    if (!is_array($feedConfig)) {
        $link = rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/';
        $update = '';
        return array();
    }

    $topic = isset($feedConfig['topic']) ? (string) $feedConfig['topic'] : 'all';
    $limits = isset($feedConfig['limits']) ? (string) $feedConfig['limits'] : '';
    $contentLength = isset($feedConfig['content_length']) ? (int) $feedConfig['content_length'] : 0;
    $items = DOCUMENTS_distributionFeedRows($topic, $limits);

    $content = array();
    $ids = array();
    foreach ($items as $item) {
        $ids[] = $item['id'];
        $summary = isset($item['excerpt']) ? (string) $item['excerpt'] : '';
        if ($contentLength > 1 && strlen($summary) > $contentLength) {
            if (function_exists('MBYTE_substr')) {
                $summary = MBYTE_substr($summary, 0, $contentLength) . '...';
            } else {
                $summary = substr($summary, 0, $contentLength) . '...';
            }
        } elseif ($contentLength === 0) {
            $summary = '';
        }

        $content[] = array(
            'title' => $item['title'],
            'summary' => $summary,
            'link' => $item['url'],
            'uid' => $item['uid'],
            'author' => $item['author'],
            'date' => $item['date-modified'],
            'format' => 'plaintext'
        );
    }

    $link = ($topic === 'all')
        ? rtrim((string) $_DOCUMENTS_CONF['site_url'], '/') . '/'
        : DOCUMENTS_interopCanonicalUrl($topic);
    $update = implode(',', $ids);

    return $content;
}

function plugin_feedupdatecheck_documents(
    $feed,
    $topic,
    $update_data,
    $limit,
    $updated_type = '',
    $updated_topic = '',
    $updated_id = ''
) {
    if ($updated_type === 'documents' && $updated_id !== '') {
        return false;
    }

    $items = DOCUMENTS_distributionFeedRows($topic, $limit);
    $ids = array();
    foreach ($items as $item) {
        $ids[] = $item['id'];
    }

    return implode(',', $ids) === (string) $update_data;
}

function DOCUMENTS_distributionStats()
{
    global $_TABLES;

    $sql = "SELECT COUNT(*) AS total, COALESCE(SUM(d.hits),0) AS views "
        . "FROM {$_TABLES['documents_docs']} AS d WHERE d.active=1"
        . COM_getPermSQL('AND', 0, 2, 'd');
    $row = DB_fetchArray(DB_query($sql));

    return array(
        'total' => is_array($row) && isset($row['total']) ? (int) $row['total'] : 0,
        'views' => is_array($row) && isset($row['views']) ? (int) $row['views'] : 0
    );
}

function plugin_statssummary_documents()
{
    global $LANG_DOCUMENTS_1;

    $stats = DOCUMENTS_distributionStats();
    $label = isset($LANG_DOCUMENTS_1['plugin_name']) ? $LANG_DOCUMENTS_1['plugin_name'] : 'Documents';
    $value = COM_numberFormat($stats['total']) . ' (' . COM_numberFormat($stats['views']) . ')';

    return array($label, $value);
}

function plugin_showstats_documents()
{
    global $_TABLES, $LANG_DOCUMENTS_1;

    $title = isset($LANG_DOCUMENTS_1['stats_title']) ? $LANG_DOCUMENTS_1['stats_title'] : 'Documents statistics';
    $sql = "SELECT d.doc_url, d.hits FROM {$_TABLES['documents_docs']} AS d "
        . "WHERE d.active=1 AND d.hits>0"
        . COM_getPermSQL('AND', 0, 2, 'd')
        . " ORDER BY d.hits DESC, d.did DESC LIMIT 10";
    $result = DB_query($sql);
    $rows = array();

    while ($row = DB_fetchArray($result)) {
        if (!is_array($row) || empty($row['doc_url'])) {
            continue;
        }
        $item = DOCUMENTS_interopItem($row['doc_url'], 0);
        if (empty($item)) {
            continue;
        }
        $rows[] = '<li>' . DOCUMENTS_renderItemLink($item)
            . ' <span class="documents-stats-count">(' . COM_numberFormat((int) $row['hits']) . ')</span></li>';
    }

    $html = COM_startBlock($title);
    if (empty($rows)) {
        $html .= '<p>0</p>';
    } else {
        $html .= '<ol class="documents-stats-list">' . implode('', $rows) . '</ol>';
    }
    $html .= COM_endBlock();

    return $html;
}
