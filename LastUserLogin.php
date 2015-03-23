<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * @file
 */

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'LastUserLogin',
	'version' => '1.3',
	'author' => array( 'Justin G. Cramer', 'Danila Ulyanov', 'Thomas Klein', 'Luis Felipe Schenone' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:LastUserLogin',
	'descriptionmsg' => 'lastuserlogin-desc',
);

$wgAutoloadClasses['LastUserLogin'] = __DIR__ . '/LastUserLogin.body.php';
$wgExtensionMessagesFiles['LastUserLogin'] = __DIR__ . '/LastUserLogin.i18n.php';
$wgExtensionMessagesFiles['LastUserLoginAlias'] = __DIR__ . '/LastUserLogin.alias.php';

// New user right
$wgAvailableRights[] = 'lastlogin';
$wgGroupPermissions['sysop']['lastlogin'] = true;

// Set up the new special page
$wgSpecialPages['LastUserLogin'] = 'LastUserLogin';
$wgSpecialPageGroups['LastUserLogin'] = 'users';

// Register the method that updates the database when a user logs in
$wgExtensionFunctions[] = 'LastUserLogin::updateUserTouched';