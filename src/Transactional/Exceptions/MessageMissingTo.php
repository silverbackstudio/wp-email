<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class MessageMissingTo extends Exception {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Your email has no "to" recipient. Please specify one.', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
