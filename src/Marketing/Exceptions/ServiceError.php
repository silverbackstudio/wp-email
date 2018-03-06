<?php


namespace Svbk\WP\Email\Marketing\Exceptions;

use Exception;

class ServiceError extends Exception {

	public function __construct( $message, $code = 0, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}

	// custom string representation of object
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}
