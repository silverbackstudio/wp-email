<?php

namespace Svbk\WP\Email\Transactional;


class NullService implements ServiceInterface {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Prints an admin notice to warn adininstrator of potential missing config.
	 */
	public function admin_notice() {
		?>
		<div class="notice notice-warning">
			<p><?php _e( 'Warning: A Form hasn\'t been configured to send emails, please check form configuration', svbk-email ); ?></p>
		</div>
		<?php
	}

	/**
	 * Sends the given email message.
	 *
	 * @param MessageInterface $message email message instance to be sent
	 * @return bool whether the message has been sent successfully
	 */
	public function send( $message ) {
		return true;
	}

	/**
	 * Sends the given email message.
	 *
	 * @param MessageInterface $message email message instance to be sent
	 * @return bool whether the message has been sent successfully
	 */
	public function sendTemplate( $template, $message ) {
		return true;
	}

}
