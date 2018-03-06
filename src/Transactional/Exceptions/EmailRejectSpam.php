<?php


namespace Svbk\WP\Email\Transactional\Exceptions;

use Exception;

class EmailRejectSpam extends EmailReject {

	public function __construct( $message = null, $code = 0, Exception $previous = null ) {

		if ( ! $message ) {
			$message = __( 'Your mailbox is reporting our message as spam. Please add our domain to trusted senders or contact the website tech support', 'svbk-email-services' );
		}

		parent::__construct( $message, $code, $previous );
	}

}
