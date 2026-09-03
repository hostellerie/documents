<?php

/* Regression test: an existing MediaGallery album must remain selectable even
 * when it is outside the current user's member-album tree. PHP 5.6+. */

function DOCUMENTS_hasMediaGallery()
{
    return true;
}

class mgAlbum
{
    public $id;
    public $title;
    public $valid = true;
    public $access = 3;

    public function __construct($id)
    {
        $this->id = (int) $id;
        $this->title = 'Album ' . (int) $id;
    }

    public function getChildrenVisible()
    {
        return array();
    }
}

function plugin_getuseroption_mediagallery()
{
    return array(
        array('Media Gallery', '/mediagallery/album.php?aid=1200')
    );
}

$_USER = array('uid' => 2);
$_CONF = array('path' => '/tmp/');

require dirname(__DIR__) . '/mediagallery_adapter.php';

$html = DOCUMENTS_mediaGalleryAlbumSelect('album', '1305');

if (strpos($html, 'value="1305" selected="selected"') === false) {
    fwrite(STDERR, "Stored album 1305 was not preserved as selected.\n");
    exit(1);
}

if (strpos($html, 'value="1200"') === false) {
    fwrite(STDERR, "Current member album tree disappeared from selector.\n");
    exit(1);
}

echo "OK\n";
