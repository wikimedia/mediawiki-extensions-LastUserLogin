<?php

namespace MediaWiki\Extension\LastUserLogin;

use MediaWiki\Hook\BeforeInitializeHook;
use MediaWiki\User\Options\UserOptionsManager;

class Hooks implements BeforeInitializeHook {

	public function __construct(
		private readonly UserOptionsManager $userOptionsManager,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforeInitialize( $title, $unused, $output, $user, $request, $mediaWiki ) {
		if ( !$user->isNamed() || $request->wasPosted() ) {
			return;
		}

		$this->userOptionsManager->setOption( $user, 'lastuserlogin-lastseen', wfTimestampNow() );
		$this->userOptionsManager->saveOptions( $user );
	}
}
