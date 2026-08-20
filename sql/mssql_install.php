<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Documents Plugin 1.0                                                      |
// +---------------------------------------------------------------------------+
// | mssql_install.php                                                         |
// |                                                                           |
// | Installation SQL                                                          |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012 by the following authors:                              |
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
CREATE TABLE [dbo].[{$_TABLES['documents']}] (
    [did] [varchar] (40) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL,
    [owner_id] [numeric](8, 0) NOT NULL,
    [group_id] [numeric](8, 0) NOT NULL,
    [perm_owner] [tinyint] NOT NULL,
    [perm_group] [tinyint] NOT NULL,
    [perm_members] [tinyint] NOT NULL,
    [perm_anon] [tinyint] NOT NULL
) ON [PRIMARY] 
";

$_SQL[] = "ALTER TABLE [dbo].[{$_TABLES['documents']}] ADD
	CONSTRAINT [PK_{$_TABLES['documents']}] PRIMARY KEY CLUSTERED
	(
		[did]
	)  ON [PRIMARY]
";

?>
