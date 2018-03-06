<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class EmailRejectRule extends EmailReject {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'This address has beed rejected, this sometimes is due to multiple tries to send to a full or disabled inbox. Please try again later', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
