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
	 */
	public static function updateUserTouched() {
		global $wgCookiePrefix;

		if ( isset( $_COOKIE ) and isset( $_COOKIE[ $wgCookiePrefix . 'UserID' ] ) ) {
			$dbw = wfGetDB( DB_MASTER );
			$query = 'UPDATE ' . $dbw->tableName( 'user' ) . ' SET user_touched = "' . $dbw->timestamp() . '" WHERE user_id = ' . intval( $_COOKIE[ $wgCookiePrefix . 'UserID' ] );
			$dbw->query( $query );
		}
	}

	/**
	 * Show the special page
	 *
	 * @param $parameter Mixed: parameter passed to the page or null
	 */
	public function execute( $parameter ) {
		global $wgUser, $wgOut, $wgLang, $wgRequest;

		if ( $wgUser->isBlocked() ) {
			throw new UserBlockedError( $this->getUser()->getBlock() );
		}

		if ( !$this->userCanExecute( $wgUser ) ) {
			$this->displayRestrictionError();
			return;
		}

		$this->setHeaders();

		$fields = array(
			'user_name' => 'lastuserlogin-userid',
			'user_real_name' => 'lastuserlogin-username',
			'user_email' => 'lastuserlogin-useremail',
			'user_touched' => 'lastuserlogin-lastlogin'
		);

		// Get order_by and validate it
		$orderby = $wgRequest->getVal( 'order_by', 'user_name' );
		if ( !isset( $fields[ $orderby ] ) ) {
			$orderby = 'user_name';
		}

		// Get order_type and validate it
		$ordertype = $wgRequest->getVal('order_type', 'ASC');
		if ( $ordertype !== 'DESC' ) {
			$ordertype = 'ASC';
		}

		// Get ALL users, paginated
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select( 'user', array_keys( $fields ) , '', __METHOD__, array( 'ORDER BY' => $orderby . ' ' . $ordertype ) );
		if ( $result === false ) {
			$wgOut->addHTML( '<p>' . wfMessage( 'lastuserlogin-nousers' )->text() . '</p>' );
			return;
		}

		// Build the table
		$out = '<table class="wikitable">';

		// Build the table header
		$title = $this->getTitle();
		$out .= '<tr>';
		$ordertype = ( $ordertype == 'ASC' ) ? 'DESC' : 'ASC'; // Invert the order
		foreach ( $fields as $key => $value ) {
			$out .= '<th><a href="' . htmlspecialchars( $title->getLocalUrl( array( 'order_by' => $key, 'order_type' => $ordertype ) ) ) . '">' . wfMessage( $value )->text() . '</a></th>';
		}
		$out .= '<th>' . wfMessage( 'lastuserlogin-daysago' )->text() . '</th>';
		$out .= '</tr>';

		// Build the table rows
		foreach ( $result as $row ) {
			$out .= '<tr>';
			foreach ( $fields as $key => $value ) {
				if ( $key === 'user_touched' ) {
					$lastLogin = $wgLang->timeanddate( wfTimestamp( TS_MW, $row[ $key ] ), true );
					$daysAgo = $wgLang->formatNum( round( ( time() - wfTimestamp( TS_UNIX, $row[ $key ] ) ) / 3600 / 24, 2 ), 2 );
					$out .= '<td>' . $lastLogin . '</td>';
					$out .= '<td style="text-align:right;">' . $daysAgo . '</td>';
				} elseif ( $key === 'user_name' ) {
					$userPage = Title::makeTitle( NS_USER, $row[ $key ] );
					$userName = Linker::link( $userPage, htmlspecialchars( $userPage->getText() ) );
					$out .= '<td>' . $userName . '</td>';
				} else {
					$out .= '<td>' . htmlspecialchars( $row[ $key ] ) . '</td>';
				}
			}
			$out .= '</tr>';
		}

		$out .= '</table>';
		$wgOut->addHTML( $out );
	}

	protected function getGroupName() {
		return 'users';
	}
}
