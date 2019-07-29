<?php


namespace Svbk\WP\Email\Marketing\Exceptions;

use Exception;

class ContactAlreadyExists extends Exception {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Contact already exists', 'svbk-email' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
