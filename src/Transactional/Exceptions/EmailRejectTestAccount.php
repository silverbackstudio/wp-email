<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class EmailRejectTestAccount extends EmailReject {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'This email address is a test account.', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
