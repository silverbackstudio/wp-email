<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class MessageMissingBody extends Exception {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Your email has no body. Please specify one at least one of html_body or html_content', 'svbk-email' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
