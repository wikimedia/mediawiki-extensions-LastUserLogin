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

class LastUserLogin extends SpecialPage {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'LastUserLogin', 'lastlogin' );
	}

	/**
	 * Updates the database when a user logs in
	 * @param Title &$title
	 * @param mixed $unused
	 * @param OutputPage $output
	 * @param User $user
	 * @param WebRequest $request
	 * @param MediaWiki $mediaWiki
	 */
	public static function onBeforeInitialize(
		Title &$title, $unused, OutputPage $output, User $user, WebRequest $request, MediaWiki $mediaWiki
	) {
		if ( !$request->wasPosted() ) {
			$userUpdate = $user->getInstanceForUpdate();
			if ( $userUpdate ) {
				$userUpdate->touch();
				$userUpdate->saveSettings();
			}
		}
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

		if ( $user->getBlock() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ( !$this->userCanExecute( $user ) ) {
			$this->displayRestrictionError();
			return;
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
		$orderby = $request->getVal( 'order_by', 'user_name' );
		if ( !isset( $fields[ $orderby ] ) ) {
			$orderby = 'user_name';
		}

		// Get order_type and validate it
		$ordertype = $request->getVal( 'order_type', 'ASC' );
		if ( $ordertype !== 'DESC' ) {
			$ordertype = 'ASC';
		}

		// Get ALL users, paginated
		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select(
			'user', array_keys( $fields ), '', __METHOD__, [ 'ORDER BY' => $orderby . ' ' . $ordertype ]
		);
		if ( $result === false ) {
			$output->addHTML( '<p>' . $this->msg( 'lastuserlogin-nousers' )->text() . '</p>' );
			return;
		}

		// Build the table
		$out = '<table class="wikitable sortable">';

		// Build the table header
		$title = $this->getPageTitle();
		$out .= '<tr>';
		// Invert the order.
		$ordertype = ( $ordertype == 'ASC' ) ? 'DESC' : 'ASC';
		$linkRenderer = $this->getLinkRenderer();
		foreach ( $fields as $key => $value ) {
			$attrs = [ 'order_by' => $key, 'order_type' => $ordertype ];
			$link = $linkRenderer->makeLink( $title, $this->msg( $value )->text(), [], $attrs );
			$out .= '<th>' . $link . '</th>';
		}
		$out .= '<th>' . $this->msg( 'lastuserlogin-daysago' )->text() . '</th>';
		$out .= '</tr>';

		// Build the table rows
		foreach ( $result as $row ) {
			$out .= '<tr>';
			foreach ( $fields as $key => $value ) {
				if ( $key === 'user_touched' ) {
					$lastLogin = $lang->timeanddate( wfTimestamp( TS_MW, $row->$key ), true );
					$secondsAgo = time() - wfTimestamp( TS_UNIX, $row->$key );
					$daysAgo = $lang->formatNum( round( $secondsAgo / 3600 / 24, 2 ) );
					$out .= '<td>' . $lastLogin . '</td>';
					$out .= '<td style="text-align: right;">' . $daysAgo . '</td>';
				} elseif ( $key === 'user_name' ) {
					$userPage = Title::makeTitle( NS_USER, $row->$key );
					$userName = $linkRenderer->makeLink( $userPage, $userPage->getText() );
					$out .= '<td>' . $userName . '</td>';
				} elseif ( $key === 'user_email_authenticated' ) {
					$out .= Html::element( 'td', [],  $this->msg( 'htmlform-' . ( $row->$key ? 'yes' : 'no' ) ) );
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
