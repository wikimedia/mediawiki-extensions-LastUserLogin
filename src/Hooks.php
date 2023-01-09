<?php

namespace MediaWiki\Extension\LastUserLogin;

use MediaWiki\Hook\BeforeInitializeHook;

class Hooks implements BeforeInitializeHook {

	/**
	 * @inheritDoc
	 */
	public function onBeforeInitialize( $title, $unused, $output, $user, $request, $mediaWiki ) {
		if ( !$request->wasPosted() ) {
			$userUpdate = $user->getInstanceForUpdate();
			if ( $userUpdate ) {
				$userUpdate->touch();
				$userUpdate->saveSettings();
			}
		}
	}
}
