<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.1.0                                                    |
// +---------------------------------------------------------------------------+
// | mysql_install.php                                                         |
// |                                                                           |
// | Installation SQL                                                          |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2014 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// +---------------------------------------------------------------------------+
// | Created with the Geeklog Plugin Toolkit.                                  |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is licensed under the terms of the GNU General Public License|
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      |
// | See the GNU General Public License for more details.                      |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+


$_SQL[] = "
CREATE TABLE {$_TABLES['documents_cat']} (

  cid int auto_increment,
  cat_name varchar(40) NOT NULL default '',
  cat_url varchar(40) NOT NULL default '',
  cat_order smallint(5) unsigned NOT NULL default '10',
  css varchar(18) NOT NULL default '',
  map INT,
  template varchar(18) NOT NULL default '',
  list_index tinyint(1) unsigned NOT NULL default '1',
  submitable tinyint(1) unsigned NOT NULL default '1',
  cat_help varchar(255) NOT NULL default '',
  custom_header varchar(255) NOT NULL default '',
  custom_footer varchar(255) NOT NULL default '',
  
  owner_id mediumint(8) unsigned NOT NULL default '1',
  group_id mediumint(8) unsigned NOT NULL default '1',
  perm_owner tinyint(1) unsigned NOT NULL default '3',
  perm_group tinyint(1) unsigned NOT NULL default '2',
  perm_members tinyint(1) unsigned NOT NULL default '2',
  perm_anon tinyint(1) unsigned NOT NULL default '2',
  PRIMARY KEY (cid)
) ENGINE=MyISAM
";

$_SQL[] = "
CREATE TABLE {$_TABLES['documents_docs']} (
  
  did int auto_increment,
  doc_url varchar(40) NOT NULL default '',
  active tinyint(1) unsigned NOT NULL default '1',
  created datetime DEFAULT NULL,
  modified datetime DEFAULT NULL,
  hits mediumint(8) unsigned NOT NULL default '0',

  owner_id mediumint(8) unsigned NOT NULL default '1',
  group_id mediumint(8) unsigned NOT NULL default '1',
  perm_owner tinyint(1) unsigned NOT NULL default '3',
  perm_group tinyint(1) unsigned NOT NULL default '2',
  perm_members tinyint(1) unsigned NOT NULL default '2',
  perm_anon tinyint(1) unsigned NOT NULL default '2',
  PRIMARY KEY (did)
) ENGINE=MyISAM
";

$_SQL[] = "
CREATE TABLE {$_TABLES['documents_fields']} (

  fid int auto_increment,
  cat_id int NOT NULL default 0,
  f_name varchar(255) NOT NULL default '',
  f_order smallint(5) unsigned NOT NULL default '1',
  f_type varchar(255) NOT NULL default '',
  sel_id varchar(40) NOT NULL default '',
  var_name varchar(18) NOT NULL default '',
  f_help varchar(255) NOT NULL default '',
  f_required tinyint(1) unsigned NOT NULL default '0',
  f_on_list tinyint(0) unsigned NOT NULL default '0',
  display_empty tinyint(1) unsigned NOT NULL default '1',
  owner_id mediumint(8) unsigned NOT NULL default '1',
  group_id mediumint(8) unsigned NOT NULL default '1',
  perm_owner tinyint(1) unsigned NOT NULL default '3',
  perm_group tinyint(1) unsigned NOT NULL default '2',
  perm_members tinyint(1) unsigned NOT NULL default '2',
  perm_anon tinyint(1) unsigned NOT NULL default '2',
  PRIMARY KEY (fid)
) ENGINE=MyISAM
";

$_SQL[] = "
CREATE TABLE {$_TABLES['documents_values']} (
  
  vid int auto_increment,
  field_id int NOT NULL default 0,
  v_value text DEFAULT NULL,
  doc_url varchar(40) NOT NULL default '',

  owner_id mediumint(8) unsigned NOT NULL default '1',
  group_id mediumint(8) unsigned NOT NULL default '1',
  perm_owner tinyint(1) unsigned NOT NULL default '3',
  perm_group tinyint(1) unsigned NOT NULL default '2',
  perm_members tinyint(1) unsigned NOT NULL default '2',
  perm_anon tinyint(1) unsigned NOT NULL default '2',
  PRIMARY KEY (vid)
) ENGINE=MyISAM
";

$_SQL[] = "
CREATE TABLE {$_TABLES['documents_selects_group']} (

  gid int auto_increment,
  g_name varchar(255) NOT NULL default '',
  g_help varchar(255) NOT NULL default '',

  PRIMARY KEY (gid)
) ENGINE=MyISAM
";

$_SQL[] = "
CREATE TABLE {$_TABLES['documents_selects']} (

  sid int auto_increment,
  s_group mediumint(8) NOT NULL default '0',
  s_name varchar(255) NOT NULL default '',
  s_value text DEFAULT NULL,
  s_order smallint(5) unsigned NOT NULL default '1',

  PRIMARY KEY (sid)
) ENGINE=MyISAM
";


$_SQL[] = "CREATE TABLE {$_TABLES['documents_pics']} (
    pi_pid varchar(40) NOT NULL,
    pi_img_num tinyint(2) unsigned NOT NULL,
    pi_filename varchar(128) NOT NULL,
    PRIMARY KEY (pi_pid,pi_img_num)
) ENGINE=MyISAM
	";
?>
