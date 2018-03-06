<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class EmailRejectTestLimit extends EmailReject {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'The test mode sending limit is beeing reached.', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
