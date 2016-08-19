<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'LastUserLogin' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['LastUserLogin'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['LastUserLoginAlias'] = __DIR__ . '/LastUserLogin.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for LastUserLogin extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the LastUserLogin extension requires MediaWiki 1.25+' );
}