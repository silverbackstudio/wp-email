<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class EmailRejectHardBounce extends EmailReject {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'The mailbox is non existent or disabled. Please check your email address is correct or your contact your mailbox provider.', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
