LastUserLogin
=============
LastUserLogin is a MediaWiki extension that adds a special to see the time at which each user last logged in.

Installation
------------
To install the extension, simply copy the files to your extensions/ directory and add the following line to your LocalSettings.php:

	require_once "$IP/extensions/LastUserLogin/LastUserLogin.php";

Usage
-----
Once installed, visit Special:LastUserLogin to see the times at which each user last logged in. Only users with the 'lastlogin' right can view the special pages and by default only admins (sysops) are given the right. To give the right to other user groups (for example the 'bureaucrat' group) simply add the following to your LocalSettings.php:

	$wgGroupPermissions['bureaucrat']['lastlogin'] = true;