<?php

namespace MediaWiki\Extension\LastUserLogin;

use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use SpecialPage;
use UserBlockedError;
use Wikimedia\Rdbms\IConnectionProvider;

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

class SpecialLastUserLogin extends SpecialPage {
	/**
	 * Constructor
	 */
	public function __construct(
		private readonly IConnectionProvider $dbProvider,
	) {
		parent::__construct( 'LastUserLogin' );
	}

	/** @inheritDoc */
	public function getRestriction(): string {
		return 'lastlogin';
	}

	/**
	 * Show the special page
	 *
	 * @param mixed $parameter Parameter passed to the page or null
	 */
	public function execute( $parameter ) {
		$user = $this->getUser();
		$request = $this->getRequest();
		$output = $this->getOutput();
		$lang = $this->getLanguage();

		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
		}

		if ( !$this->userCanExecute( $user ) ) {
			$this->displayRestrictionError();
		}

		$this->setHeaders();

		$fields = [
			'user_name' => 'lastuserlogin-userid',
			'user_real_name' => 'lastuserlogin-username',
			'user_email' => 'lastuserlogin-useremail',
			'user_email_authenticated' => 'lastuserlogin-useremailauthenticated',
			'user_touched' => 'lastuserlogin-lastlogin',
		];

		// Get order_by and validate it
		$orderby = $request->getRawVal( 'order_by' ) ?? 'user_name';
		if ( !isset( $fields[ $orderby ] ) ) {
			$orderby = 'user_name';
		}

		// Get order_type and validate it
		$ordertype = $request->getRawVal( 'order_type' );
		if ( $ordertype !== 'DESC' ) {
			$ordertype = 'ASC';
		}

		// Get ALL users, paginated
		$dbr = $this->dbProvider->getReplicaDatabase();
		$result = $dbr->select(
			// @phan-suppress-next-line SecurityCheck-SQLInjection The $orderby is validated above
			'user', array_keys( $fields ), 'user_is_temp = 0', __METHOD__, [ 'ORDER BY' => $orderby . ' ' . $ordertype ]
		);
		if ( $result === false ) {
			$output->addHTML( Html::element( 'p', [], $this->msg( 'lastuserlogin-nousers' )->text() ) );
			return;
		}

		// Build the table
		$out = '<table class="wikitable sortable">';

		// Build the table header
		$title = $this->getPageTitle();
		$out .= '<tr>';
		// Invert the order.
		$ordertype = ( $ordertype === 'ASC' ) ? 'DESC' : 'ASC';
		$linkRenderer = $this->getLinkRenderer();
		foreach ( $fields as $key => $value ) {
			$attrs = [ 'order_by' => $key, 'order_type' => $ordertype ];
			$link = $linkRenderer->makeLink( $title, $this->msg( $value )->text(), [], $attrs );
			$out .= '<th>' . $link . '</th>';
		}
		$out .= Html::element( 'th', [], $this->msg( 'lastuserlogin-daysago' )->text() );
		$out .= '</tr>';

		// Build the table rows
		foreach ( $result as $row ) {
			$out .= '<tr>';
			foreach ( $fields as $key => $value ) {
				if ( $key === 'user_touched' ) {
					$lastLogin = $lang->timeanddate( wfTimestamp( TS_MW, $row->$key ), true );
					$secondsAgo = time() - (int)wfTimestamp( TS_UNIX, $row->$key );
					$daysAgo = $lang->formatNum( round( $secondsAgo / 3600 / 24, 2 ) );
					$out .= Html::element( 'td', [], $lastLogin );
					$out .= Html::element( 'td',
						[ 'style' => 'text-align: right' ],
						$daysAgo
					);
				} elseif ( $key === 'user_name' ) {
					$userPage = Title::makeTitle( NS_USER, $row->$key );
					$userName = $linkRenderer->makeLink( $userPage, $userPage->getText() );
					$out .= '<td>' . $userName . '</td>';
				} elseif ( $key === 'user_email_authenticated' ) {
					$out .= Html::element( 'td',
						[],
						$this->msg( 'htmlform-' . ( $row->$key ? 'yes' : 'no' ) )->text()
					);
				} else {
					$out .= '<td>' . htmlspecialchars( $row->$key ) . '</td>';
				}
			}
			$out .= '</tr>';
		}

		$out .= '</table>';
		$output->addHTML( $out );
	}

	/**
	 * @return string
	 */
	protected function getGroupName() {
		return 'users';
	}
}
