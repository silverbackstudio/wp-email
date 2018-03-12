<?php


namespace Svbk\WP\Email\Marketing\Exceptions;

use Exception;

class ContactNotExists extends Exception {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Contact does not exists', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
