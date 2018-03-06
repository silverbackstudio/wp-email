<?php
namespace Svbk\WP\Email\Transactional;

use Exception;

use Mandrill as Mandrill_Client;
use Mandrill_Error;

class Mandrill implements ServiceInterface {

	public $name = 'mandrill';

	public $api_key;
	public $client;
	
	const TEMPLATE_SUPPORT = true;

	public function __contruct() {
		$this->client = new Mandrill_Client( $this->api_key );
	}

	public function sendTemplate( Message $email, $template, $attributes = array() ) {

		$params = $this->messageParams( $email );

		if ( $email->attributes ) {
			$params['global_merge_vars'] = self::castMergeTags( $email->attributes );
			$params['merge'] = true;
		}

		try {
			$results = $this->client->messages->sendTemplate( $template, array(), $params );

			if ( ! is_array( $results ) || ! isset( $results[0]['status'] ) ) {
				throw new Exceptions\ServiceError( __( 'The requesto to our mail server failed, please try again later or contact the site owner.', 'svbk-email-services' ) );
			}

			$this->throwErrors( $results );

		} catch ( Mandrill_Error $e ) {
			throw new Exceptions\ServiceError( $e->getMessage() );
		}

	}

	protected function messageParams( Message $email ) {

		$params = array();

		if ( $email->subject ) {
			$params['subject'] = $email->subject;
		}

		if ( $email->to ) {
			$params['to'][] = array(
				'email' => trim( $email->to ),
				'type' => 'to',
			);
		}

		if ( $email->from ) {
			$params['from_email'] = $email->from;
		}

		if ( $email->cc ) {
			$params['to'][] = array(
				'email' => trim( $email->cc ),
				'type' => 'cc',
			);
		}

		if ( $email->bcc ) {
			$params['to'][] = array(
				'email' => trim( $email->bcc ),
				'type' => 'bcc',
			);
		}

		if ( $email->reply_to ) {
			$params['headers']['Reply-To'] = $email->reply_to;
		}

		if ( $email->tags ) {
			$params['tags'] = $email->tags;
		}

		return apply_filters( 'svbk_mailing_mandrill_message_params', $params, $email, $this );
	}

	public function send( Message $email ) {

		$params = $this->messageParams( $email );

		if ( $email->html_body ) {
			$params['html'] = $email->html_body;
		}

		if ( $email->text_body ) {
			$params['text'] = $email->text_body;
		}

		try {
			$results = $mandrill->messages->send( $params );

			if ( ! is_array( $results ) || ! isset( $results[0]['status'] ) ) {
				throw new Exceptions\ServiceError( __( 'The requesto to our mail server failed, please try again later or contact the site owner.', 'svbk-email-services' ) );
			}

			$this->throwErrors( $results );

		} catch ( Mandrill_Error $e ) {
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

		return $inputData;
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
						throw new Exceptions\EmailReject( sprintf( __( 'This email address has beeing rejected for an unknown reason [%s]. Please use another email address.', 'svbk-email-services' ), $result['reject_reason'] ) );
				}
			}

			if ( 'invalid' === $result['status'] ) {
				throw new Exceptions\EmailRejectInvalidAddress();
			}
		}

	}

}
