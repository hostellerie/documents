<?php

/* MediaGallery integration boundary for Documents 1.2.0. */

function DOCUMENTS_mediaGalleryLoadAlbumClass()
{
    global $_CONF;

    if (!DOCUMENTS_hasMediaGallery()) {
        return false;
    }

    if (class_exists('mgAlbum')) {
        return true;
    }

    $common = $_CONF['path'] . 'plugins/mediagallery/include/common.php';
    $classAlbum = $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
    if (!is_file($common) || !is_file($classAlbum)) {
        return false;
    }

    require_once $common;
    require_once $classAlbum;

    return class_exists('mgAlbum');
}

function DOCUMENTS_mediaGalleryMemberRootId($uid = 0)
{
    global $_USER;

    if (!DOCUMENTS_mediaGalleryLoadAlbumClass()) {
        return 0;
    }

    $uid = (int) $uid;
    if ($uid <= 0) {
        $uid = isset($_USER['uid']) ? (int) $_USER['uid'] : 0;
    }
    if ($uid <= 1) {
        return 0;
    }

    /* MediaGallery already exposes the member album through Geeklog's user
     * menu callback. Reuse that public plugin callback instead of knowing the
     * mg_albums schema. */
    if (!function_exists('plugin_getuseroption_mediagallery')) {
        return 0;
    }

    $items = plugin_getuseroption_mediagallery();
    if (!is_array($items)) {
        return 0;
    }

    foreach ($items as $item) {
        if (!is_array($item) || empty($item[1])) {
            continue;
        }
        $url = html_entity_decode((string) $item[1], ENT_QUOTES, 'UTF-8');
        $query = parse_url($url, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            continue;
        }
        $params = array();
        parse_str($query, $params);
        if (isset($params['aid']) && is_numeric($params['aid'])) {
            return (int) $params['aid'];
        }
    }

    return 0;
}

function DOCUMENTS_mediaGalleryAlbumTree($rootId, $depth = 0, &$seen = array())
{
    $rootId = (int) $rootId;
    if ($rootId <= 0 || !DOCUMENTS_mediaGalleryLoadAlbumClass() || isset($seen[$rootId])) {
        return array();
    }

    $seen[$rootId] = true;
    $album = new mgAlbum($rootId);
    if (empty($album->valid) || (isset($album->access) && (int) $album->access < 1)) {
        return array();
    }

    $items = array(array(
        'id' => (int) $album->id,
        'title' => (string) $album->title,
        'depth' => (int) $depth
    ));

    $children = method_exists($album, 'getChildrenVisible')
        ? $album->getChildrenVisible()
        : $album->getChildren();
    if (!is_array($children)) {
        return $items;
    }

    foreach ($children as $childId) {
        $childId = (int) $childId;
        if ($childId <= 0) {
            continue;
        }
        $childItems = DOCUMENTS_mediaGalleryAlbumTree($childId, $depth + 1, $seen);
        foreach ($childItems as $childItem) {
            $items[] = $childItem;
        }
    }

    return $items;
}

function DOCUMENTS_mediaGallerySelectedAlbum($albumId)
{
    $albumId = (int) trim((string) $albumId);
    if ($albumId <= 0 || !DOCUMENTS_mediaGalleryLoadAlbumClass()) {
        return array();
    }

    $album = new mgAlbum($albumId);
    if (empty($album->valid) || (isset($album->access) && (int) $album->access < 1)) {
        return array();
    }

    return array(
        'id' => (int) $album->id,
        'title' => (string) $album->title,
        'depth' => 0
    );
}

function DOCUMENTS_mediaGalleryAlbumSelect($name, $selected = '', $uid = 0)
{
    $selectedId = (int) trim((string) $selected);
    $rootId = DOCUMENTS_mediaGalleryMemberRootId($uid);
    $seen = array();
    $albums = $rootId > 0
        ? DOCUMENTS_mediaGalleryAlbumTree($rootId, 0, $seen)
        : array();

    /* An administrator may edit a document owned by another user, and legacy
     * documents may reference an album outside the current member-album tree.
     * Keep an existing accessible album selectable so a simple edit never
     * silently loses the stored relation. */
    if ($selectedId > 0 && !isset($seen[$selectedId])) {
        $selectedAlbum = DOCUMENTS_mediaGallerySelectedAlbum($selectedId);
        if (!empty($selectedAlbum)) {
            $albums[] = $selectedAlbum;
        }
    }

    if (empty($albums)) {
        return '';
    }

    $html = '<select name="' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') . '">';
    $html .= '<option value="">----</option>';
    foreach ($albums as $album) {
        $id = (int) $album['id'];
        $isSelected = ($selectedId === $id) ? ' selected="selected"' : '';
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, (int) $album['depth']));
        $title = htmlspecialchars(strip_tags((string) $album['title']), ENT_QUOTES, 'UTF-8');
        $html .= '<option value="' . $id . '"' . $isSelected . '>' . $indent . $title . '</option>';
    }
    $html .= '</select>';

    return $html;
}

function DOCUMENTS_mediaGalleryRenderAlbum($albumId)
{
    $albumId = (int) $albumId;
    if ($albumId <= 0 || !DOCUMENTS_hasMediaGallery() || !function_exists('PLG_replaceTags')) {
        return '';
    }

    return (string) PLG_replaceTags('[gallery:' . $albumId . ']');
}
