<?php
namespace Svbk\WP\Email\Transactional;

use Exception;

use Mandrill as Mandrill_Client;
use Mandrill_Error;

class Mandrill implements ServiceInterface {

	public $name = 'mandrill';

	public $client;

	const TEMPLATE_SUPPORT = true;
	const SERVICE_NAME = 'mandrill';

	public function __construct( $api_key ) {
		$this->setApiKey( $api_key );
	}

	public function setApiKey( $api_key ) {

		if ( empty( $api_key ) ) {
			do_action( 'log', 'critical', 'Missing Mandrill API key' );
			throw new Exceptions\ApiKeyInvalid();
		}

		$this->client = new Mandrill_Client( $api_key );
	}

	public function getApiKey() {
		return $this->client->apikey;
	}

	public function sendTemplate( $template, $message, $attributes = array() ) {
		$params = $this->prepareSend( $message );

		$attributes = array_merge( $message->getAttributes(), $attributes );
		$uc_attributes = array();
		$input_attributes = array();

		if ( ! empty( $attributes ) ) {

			foreach ( $attributes as $key => $value ) {
				$uc_attributes[ strtoupper( $key ) ] = $value;
				$input_attributes[ strtoupper( 'INPUT_' . $key ) ] = $value;
			}

			$params['global_merge_vars'] = self::castMergeTags( array_merge( $attributes, $uc_attributes, $input_attributes ) );
			$params['merge'] = true;
		}

		try {

			do_action(
				'log', 'debug', 'Mandrill sendTemplate() invoked',
				array(
					'template' => $template,
					'params' => $params,
				)
			);

			$results = $this->client->messages->sendTemplate( $template, array(), $params );

			if ( ! is_array( $results ) || ! isset( $results[0]['status'] ) ) {

				do_action(
					'log', 'error', 'Mandrill sendTemplate() API request invalid response',
					array(
						'error' => $results,
					)
				);

				throw new Exceptions\ServiceError( __( 'The requesto to our mail server failed, please try again later or contact the site owner.', 'svbk-email' ) );
			}

			$this->throwErrors( $results );

			do_action(
				'log', 'info', 'Mandrill sendTempalte() successful',
				array(
					'results' => $results,
				)
			);

		} catch ( Mandrill_Error $e ) {

			do_action(
				'log', 'error', 'Mandrill sendTemplate() API request error',
				array(
					'error' => $e->getMessage(),
				)
			);

			throw new Exceptions\ServiceError( $e->getMessage() );
		}

	}

	public function prepareSend( $message ) {

		$params = array();

		if ( $message->subject ) {
			$params['subject'] = $message->subject;
		}

		if ( ! empty( $message->to ) ) {
			foreach ( $message->to as $recipient ) {
				$params['to'][] = array(
					'name' => $recipient->name(),
					'email' => $recipient->email,
					'type' => 'to',
				);
			}
		} else {
			throw new Exceptions\MessageMissingTo();
		}

		if ( $message->from ) {
			$params['from_email'] = $message->from->email;
		}

		if ( ! empty( $message->cc ) ) {
			foreach ( $message->cc as $recipient ) {
				$params['to'][] = array(
					'name' => $recipient->name(),
					'email' => $recipient->email,
					'type' => 'cc',
				);
			}
		}

		if ( ! empty( $message->bcc ) ) {
			foreach ( $message->bcc as $recipient ) {
				$params['to'][] = array(
					'name' => $recipient->name(),
					'email' => $recipient->email,
					'type' => 'bcc',
				);
			}
		}

		$params['headers'] = array();

		$headers = $message->getHeaders( true );
		if ( ! empty( $headers ) ) {
			$params['headers'] = $headers;
		}

		if ( $message->reply_to ) {
			$params['headers']['Reply-To'] = $message->reply_to->emailAddress();
		}

		if ( $message->tags ) {
			$params['tags'] = $message->tags;
		}

		return apply_filters( 'svbk_mailing_mandrill_message_params', $params, $message, $this );
	}

	public function send( $message ) {

		if ( ! $message->from ) {
			throw new Exceptions\MessageMissingFrom();
		}

		if ( ! $message->html_body && ! $message->text_body ) {
			throw new Exceptions\MessageMissingBody();
		}

		if ( ! $message->subject ) {
			throw new Exceptions\MessageMissingSubject();
		}

		$params = $this->prepareSend( $message );

		if ( $message->html_body ) {
			$params['html'] = $message->html_body;
		}

		if ( $message->text_body ) {
			$params['text'] = $message->text_body;
		}

		try {
			do_action(
				'log', 'debug', 'Mandrill send() invoked',
				array(
					'params' => $params,
				)
			);

			$results = $this->client->messages->send( $params );

			if ( ! is_array( $results ) || ! isset( $results[0]['status'] ) ) {
				do_action(
					'log', 'error', 'Mandrill sendTemplate() API request invalid response',
					array(
						'error' => $results,
					)
				);

				throw new Exceptions\ServiceError( __( 'The requesto to our mail server failed, please try again later or contact the site owner.', 'svbk-email' ) );
			}

			$this->throwErrors( $results );

			do_action(
				'log', 'info', 'Mandrill send() successful',
				array(
					'results' => $results,
				)
			);

		} catch ( Mandrill_Error $e ) {

			do_action(
				'log', 'error', 'Mandrill sendTemplate() API request error',
				array(
					'error' => $e->getMessage(),
				)
			);

			throw new Exceptions\ServiceError( $e->getMessage() );
		}

	}

	public static function castMergeTags( $data, $prefix = '' ) {

		foreach ( $data as $key => &$value ) {
			$value = array(
				'name' => $prefix . strtoupper( $key ),
				'content' => $value,
			);
		}

		return $data;
	}

	public function throwErrors( $results ) {

		foreach ( $results as $result ) {

			if ( 'rejected' === $result['status'] ) {

				switch ( $result['reject_reason'] ) {
					case 'unsub':
					case 'custom':
						throw new Exceptions\EmailReject();
						break;
					case 'rule':
						throw new Exceptions\EmailRejectRule();
						break;
					case 'hard-bounce':
						throw new Exceptions\EmailRejectHardBounce();
						break;
					case 'soft-bounce':
						throw new Exceptions\EmailRejectSoftBounce();
						break;
					case 'invalid':
						throw new Exceptions\EmailRejectInvalidAddress();
						break;
					case 'spam':
						throw new Exceptions\EmailRejectSpam();
						break;
					case 'test':
						throw new Exceptions\EmailRejectTestAccount();
						break;
					case 'test-mode-limit':
						throw new Exceptions\EmailRejectTestLimit();
						break;
					case 'invalid-sender':
						throw new Exceptions\EmailRejectInvalidSender();
						break;
					default:
						do_action(
							'log', 'notice', 'Mandrill unmapped reject reason found: {reason}',
							array(
								'reason' => $result['reject_reason'],
							)
						);

						throw new Exceptions\EmailReject( sprintf( __( 'This email address has beeing rejected for an unknown reason [%s]. Please use another email address.', 'svbk-email' ), $result['reject_reason'] ) );
				}
			}

			if ( 'invalid' === $result['status'] ) {
				throw new Exceptions\EmailRejectInvalidAddress();
			}
		}

	}

}
