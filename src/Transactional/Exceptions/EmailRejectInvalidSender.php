<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class EmailRejectInvalidSender extends EmailReject {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Invalid sender address.', svbk-email );
		}

		parent::__construct( $message, $code, $previous );
	}

}
