<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class EmailRejectInvalidAddress extends EmailReject {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Your email address is invalid. Please check the address or use another.', 'svbk-email' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
