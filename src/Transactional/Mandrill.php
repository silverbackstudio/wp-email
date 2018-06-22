<?php
namespace Svbk\WP\Email\Transactional;

use Exception;

use Mandrill as Mandrill_Client;
use Mandrill_Error;

class Mandrill implements ServiceInterface {

	public $name = 'mandrill';

	public $client;
	
	const TEMPLATE_SUPPORT = true;

	public function __construct( $api_key ) {
		$this->setApiKey( $api_key );
	}
	
	public function setApiKey( $api_key ) {
		
		if( empty( $api_key ) ){
			do_action( 'log', 'critical', 'Missing Mandrill API key' );
			throw new Exceptions\ApiKeyInvalid();
		}		
		
		$this->client = new Mandrill_Client( $api_key );
	}	

	public function sendTemplate( $message, $template, $attributes = array() ) {
		$params = $this->messageParams( $message );

		$attributes = array_merge( $message->attributes, $attributes );
		$uc_attributes = array();
		$input_attributes = array();		
		
		if( ! empty( $attributes ) ) {
			
			foreach( $attributes as $key => $value ){
				$uc_attributes[ strtoupper( $key ) ] = $value;
				$input_attributes[ strtoupper( 'INPUT_' . $key ) ] = $value;
			}
			
			$params['global_merge_vars'] = self::castMergeTags( array_merge( $attributes, $uc_attributes, $input_attributes ) );
			$params['merge'] = true;
		}

		try {
			
			do_action( 'log', 'debug', 'Mandrill sendTemplate() invoked', 
				array(  'template' => $template, 'params' => $params )
			);			
			
			$results = $this->client->messages->sendTemplate( $template, array(), $params );

			if ( ! is_array( $results ) || ! isset( $results[0]['status'] ) ) {
				
				do_action( 'log', 'error', 'Mandrill sendTemplate() API request invalid response', 
					array( 'error' => $results ) 
				);
				
				throw new Exceptions\ServiceError( __( 'The requesto to our mail server failed, please try again later or contact the site owner.', 'svbk-email-services' ) );
			}
			
			$this->throwErrors( $results );
			
			do_action( 'log', 'info', 'Mandrill sendTempalte() successful', 
				array( 'results' => $results ) 
			);				

		} catch ( Mandrill_Error $e ) {

			do_action( 'log', 'error', 'Mandrill sendTemplate() API request error', 
				array( 'error' => $e->getMessage() ) 
			);			
			
			throw new Exceptions\ServiceError( $e->getMessage() );
		}

	}

	protected function messageParams( $message ) {

		$params = array();

		if ( $message->subject ) {
			$params['subject'] = $message->subject;
		}

		if ( $message->to ) {
			$params['to'][] = array(
				'name' => $message->to->name(),
				'email' => $message->to->email,
				'type' => 'to',
			);
		}

		if ( $message->from ) {
			$params['from_email'] = $message->from->email;
		}

		if ( $message->cc ) {
			$params['to'][] = array(
				'name' => $message->cc->name(),
				'email' => $message->cc->email,
				'type' => 'cc',
			);
		}

		if ( $message->bcc ) {
			$params['to'][] = array(
				'name' => $message->bcc->name(),
				'email' => $message->bcc->email,
				'type' => 'bcc',
			);
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

		$params = $this->messageParams( $message );

		if ( $message->html_body ) {
			$params['html'] = $message->html_body;
		}

		if ( $message->text_body ) {
			$params['text'] = $message->text_body;
		}

		try {
			do_action( 'log', 'debug', 'Mandrill send() invoked', 
				array( 'params' => $params ) 
			);			
			
			$results = $mandrill->messages->send( $params );

			if ( ! is_array( $results ) || ! isset( $results[0]['status'] ) ) {
				do_action( 'log', 'error', 'Mandrill sendTemplate() API request invalid response', 
					array( 'error' => $results ) 
				);				
				
				throw new Exceptions\ServiceError( __( 'The requesto to our mail server failed, please try again later or contact the site owner.', 'svbk-email-services' ) );
			}

			$this->throwErrors( $results );

			do_action( 'log', 'info', 'Mandrill send() successful', 
				array( 'results' => $results ) 
			);	

		} catch ( Mandrill_Error $e ) {
			
			do_action( 'log', 'error', 'Mandrill sendTemplate() API request error', 
				array( 'error' => $e->getMessage() ) 
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
						do_action( 'log', 'notice', 'Mandrill unmapped reject reason found: {reason}', 
							array( 'reason' => $result['reject_reason'] ) 
						);
												
						throw new Exceptions\EmailReject( sprintf( __( 'This email address has beeing rejected for an unknown reason [%s]. Please use another email address.', 'svbk-email-services' ), $result['reject_reason'] ) );
				}
			}

			if ( 'invalid' === $result['status'] ) {
				throw new Exceptions\EmailRejectInvalidAddress();
			}
			
		}

	}

}
