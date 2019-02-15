<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class MessageMissingSubject extends Exception {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Your email has no subject. Please specify one.', 'svbk-email' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
