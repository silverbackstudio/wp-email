<?php


namespace Svbk\WP\Email\Marketing\Exceptions;

use Exception;

class ApiKeyInvalid extends Exception {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Please specify an API key', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

	// custom string representation of object
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}
