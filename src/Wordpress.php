<?php

namespace Svbk\WP\Email;

class Wordpress {

	public static $last_email_id = '';
	public static $last_email_content = '';
	public static $last_email_data;

	public static $password_key = '';

	public function message( $to, $subject, $message, $headers = '', $attachments = array() ) {

		/**
		 * Filters the wp_mail() arguments.
		 *
		 * @since 2.2.0
		 *
		 * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
		 *                    subject, message, headers, and attachments values.
		 */
		$atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

		$message = new Message();

		if ( isset( $atts['to'] ) ) {
			$message->addRecipients( $atts['to'] );
		}

		if ( isset( $atts['subject'] ) ) {
			$message->subject = $atts['subject'];
		}

		if ( isset( $atts['message'] ) ) {
			$body = $atts['message'];
		}

		if ( isset( $atts['headers'] ) ) {
			$headers = $atts['headers'];
		}

		if ( isset( $atts['attachments'] ) ) {
			$attachments = $atts['attachments'];
		}

		if ( ! is_array( $attachments ) ) {
			$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
		}

		// Headers
		if ( isset( $atts['headers'] ) ) {
			if ( ! is_array( $headers ) ) {
				// Explode the headers out, so this function can take both
				// string headers and an array of headers.
				$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
			} else {
				$tempheaders = $headers;
			}

			$headers = array();

			// If it's actually got contents
			if ( ! empty( $tempheaders ) ) {
				// Iterate through the raw headers
				foreach ( (array) $tempheaders as $header ) {
					if ( strpos( $header, ':' ) === false ) {
						if ( false !== stripos( $header, 'boundary=' ) ) {
							$parts = preg_split( '/boundary=/i', trim( $header ) );
							$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
						}
						continue;
					}
					// Explode them out
					list( $name, $content ) = explode( ':', trim( $header ), 2 );

					// Cleanup crew
					$name    = trim( $name );
					$content = trim( $content );

					switch ( strtolower( $name ) ) {
						// Mainly for legacy -- process a From: header if it's there
						case 'from':
							$message->setFrom( Contact::fromEmailAddress( $content ) );
							break;
						case 'content-type':
							if ( strpos( $content, ';' ) !== false ) {
								list( $type, $charset_content ) = explode( ';', $content );
								$content_type = trim( $type );
								if ( false !== stripos( $charset_content, 'charset=' ) ) {
									$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
								} elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
									$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
									$charset = '';
								}

								// Avoid setting an empty $content_type.
							} elseif ( '' !== trim( $content ) ) {
								$content_type = trim( $content );
							}
							break;
						case 'cc':
							$message->addRecipients( $content, 'cc' );
							break;
						case 'bcc':
							$message->addRecipients( $content, 'bcc' );
							break;
						case 'reply-to':
							$message->setReplyTo( Contact::fromEmailAddress( $content ) );
							break;
						default:
							// Add it to our grand headers array
							$message->addHeader( trim( $name ), trim( $content ) );
							break;
					}
				}
			}
		}

		// From email and name
		if ( empty( $message->from ) ) {
			// Get the site domain and get rid of www.
			$sitename = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
			}

			$message->from = new Contact(
				array(
					'email' => 'wordpress@' . $sitename,
					'first_name' => 'WordPress',
				)
			);
		}

		/**
		 * Filters the email address to send from.
		 *
		 * @since 2.2.0
		 *
		 * @param string $from_email Email address to send from.
		 */
		$message->from->email = apply_filters( 'wp_mail_from', $message->from->email );

		/**
		 * Filters the name to associate with the "from" email address.
		 *
		 * @since 2.3.0
		 *
		 * @param string $from_name Name associated with the "from" email address.
		 */
		$message->from->setName( apply_filters( 'wp_mail_from_name', $message->from->name() ) );

		// If we don't have a content-type from the input headers
		if ( ! isset( $content_type ) ) {
			$content_type = 'text/plain';
		}

		/**
		 * Filters the wp_mail() content type.
		 *
		 * @since 2.3.0
		 *
		 * @param string $content_type Default wp_mail() content type.
		 */
		$content_type = apply_filters( 'wp_mail_content_type', $content_type );

		$message->addHeader( 'Content-Type', $content_type );

		// Set whether it's plaintext, depending on $content_type
		if ( 'text/html' == $content_type ) {
			$message->html_body = $body;
		} else {
			$message->text_body = $body;
		}

		// If we don't have a charset from the input headers
		if ( ! isset( $charset ) ) {
			$charset = get_bloginfo( 'charset' );
		}

		/**
		 * Filters the default wp_mail() charset.
		 *
		 * @since 2.3.0
		 *
		 * @param string $charset Default email charset.
		 */
		$message->addHeader( 'charset', apply_filters( 'wp_mail_charset', $charset ) );

		if ( false !== stripos( $content_type, 'multipart' ) && ! empty( $boundary ) ) {
			$message->addHeader( 'Content-Type', sprintf( "%s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
		}

		return $message;

	}

	public static function trackedMessages(){
		return array(
			'new_user_notification_email' => __( 'New User Welcome', 'svbk-email' ),
			// 'user_request_action' => __( 'User Request Action', 'svbk-email' ),
			// 'password_change_email' => __( 'Password Change', 'svbk-email' ),
		);
	}

	public static function trackMessages(){
		add_filter( 'wp_new_user_notification_email' , array( self::class, 'track_wp_new_user_notification_email' ), 10, 3 );
		add_filter( 'retrieve_password_key' , array( self::class, 'store_password_key' ), 10, 3 );
		// add_filter( 'user_request_action_email_content', array( self::class, 'track_user_request_action' ), 10, 2 );
		// add_filter( 'password_change_email', array( self::class, 'track_password_change_email' ), 10, 3 );
		// add_filter( 'email_change_email', array( self::class, 'track_email_change_email' ), 10, 3 );
	}

	public static function getCommonData( $user = null ){
		
		$data = array(
			'ADMIN_EMAIL' => get_option( 'admin_email' ),
			'SITENAME' => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			'SITEURL' => home_url(),
		);

		if ( $user instanceof \WP_User ) {
			$user = $user->to_array();
		}

		if ( $user ) {
			$data['USERNAME'] = $user['user_login'];
			$data['EMAIL'] = $user['user_email'];
			$data['USER_NICENAME'] = $user['user_nicename'];
			$data['USER_ID'] = $user['ID'];
		}
		
		return $data;
	}

	public static function store_password_key( $key, $user ){
		self::$last_email_data['password_reset_link'] = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
		
		return $key;
	}
	
	public static function track_wp_new_user_notification_email( $params, $user ){
		self::$last_email_id = 'new_user_notification_email';
		
		$new_params = $params;
		
		self::$last_email_data = array_merge( self::$last_email_data, self::getCommonData($user), $new_params );
		self::$last_email_content = $params['message'];
		
		return $params;
	}

	// public static function track_password_change_email( $pass_change_email, $user, $userdata ){
	// 	self::$last_email_id = 'password_change_email';
	// 	self::$last_email_data =  array_merge( self::getCommonData($user), $pass_change_email, $user, $userdata );
	// 	self::$last_email_content = $email_text;
	// }

	// public static function track_email_change_email( $email_change_email, $user, $userdata ){
	// 	self::$last_email_id = 'email_change_email';
	// 	self::$last_email_data = $email_data;
	// 	self::$last_email_content = $email_text;
	// }	
	
	// public static function track_user_request_action( $email_text, $email_data ){
	// 	self::$last_email_id = 'user_request_action';
	// 	self::$last_email_data = $email_data;
	// 	self::$last_email_content = $email_text;
	// }		
	
	public static function clearTracker(){
		self::$last_email_id = '';
		self::$last_email_data = null;
		self::$last_email_content = null;
	}		

}
