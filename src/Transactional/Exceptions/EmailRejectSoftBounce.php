<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class EmailRejectSoftBounce extends EmailReject {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Your mailbox isn\'t accepting our message. Please check your if mailbox your mailbox is full.', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
