<?php

namespace Svbk\WP\Email;

class Wordpress {

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




}
