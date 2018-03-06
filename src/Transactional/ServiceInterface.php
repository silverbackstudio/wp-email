<?php

namespace Svbk\WP\Email\Transactional;

interface ServiceInterface {


	/**
	 * Sends the given email message.
	 *
	 * @param MessageInterface $message email message instance to be sent
	 * @return bool whether the message has been sent successfully
	 */
	public function send( $message );

	/**
	 * Sends the given email message.
	 *
	 * @param MessageInterface $message email message instance to be sent
	 * @return bool whether the message has been sent successfully
	 */
	public function sendTemplate( $template, $message );

}
