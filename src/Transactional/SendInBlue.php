<?php
namespace Svbk\WP\Email\Transactional;

use SendinBlue\Client as SendInBlue_Client;
use Exception;

class SendInBlue implements ServiceInterface {

	public $api_key;
	
	public $config;
	public $client;
	public $smtp_client;
	
	const TEMPLATE_SUPPORT = true;

	public function __construct( $api_key = null ) {
		
		if( $api_key ) {
			$this->config = new SendInBlue_Client\Client\Configuration;
			$this->config->setApiKey( 'api-key', $api_key );
		} else {
			$this->config = SendInBlue_Client\Configuration::getDefaultConfiguration();
		}
		
		if( ! $this->config->getApiKey('api-key') ) {
			throw new Exceptions\ApiKeyInvalid();
		}
		
		$this->client = new SendInBlue_Client\ApiClient( $this->config );
		$this->smtp_client = new SendInBlue_Client\Api\SMTPApi( $this->client );
	}

	public static function setApiKey( $api_key ) {
		SendInBlue_Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', $api_key );
	}

	public function sendTemplate( $email, $template, $attributes = array() ) {

		$sendEmail = $this->compose( $email );
		
		$attributes = array_merge( $email->attributes, $attributes );
		$uc_attributes = array();
		$input_attributes = array();		
		
		if( ! empty( $attributes ) ) {
			
			foreach( $attributes as $key => $value ){
				$uc_attributes[ strtoupper( $key ) ] = $value;
				$input_attributes[ strtoupper( 'INPUT_' . $key ) ] = $value;
			}
			
			$sendEmail->setAttributes( array_merge( $attributes, $uc_attributes, $input_attributes ) );		
		}		
		
		try {
			$result = $this->smtp_client->sendTemplate( $template, $sendEmail );
		} catch ( SendInBlue_Client\ApiException $e ) {
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );			
		} catch ( Exception $e ) {
			throw new Exceptions\ServiceError( $e->getMessage() );
		}

	}

	public function send( $email ) {

		$sendEmail = $this->compose( $email );
		
		$sendEmail->setSender( $email->from );
		$sendEmail->setSubject( $email->subject );
		$sendEmail->htmlContent( $email->html_body );
		$sendEmail->textContent( $email->text_body );

		try {
			$result = $this->smtp_client->sendTransacEmail( $sendEmail );
		} catch ( SendInBlue_Client\ApiException $e ) {
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );			
		} catch ( Exception $e ) {
			throw new Exceptions\ServiceError( $e->getMessage() );
		}

	}
	
	public function compose( $email ){
		
		$sendEmail = new SendInBlue_Client\Model\SendEmail();
		
		if( $email->to ) { 
			$sendEmail->setEmailTo( array( $email->to->email ) );
		}
	
		if( $email->cc ) {
			$sendEmail->setEmailCc( array( $email->cc->email ) );
		}		
		
		if( $email->bcc ) {
			$sendEmail->setEmailBcc( array( $email->bcc->email ) );
		}

		if ( $email->reply_to ) {
			$sendEmail->setReplyTo( $email->reply_to->email );
		}
		
		return $sendEmail;
		
	}

}
