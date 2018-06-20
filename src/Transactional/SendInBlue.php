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
			$this->config = new SendInBlue_Client\Configuration;
			$this->config->setApiKey( 'api-key', $api_key );
		} else {
			$this->config = SendInBlue_Client\Configuration::getDefaultConfiguration();
		}
		
		if( ! $this->config->getApiKey('api-key') ) {
			
			do_action( 'log', 'critical', 'Missing Sendinblue API key');
			
			throw new Exceptions\ApiKeyInvalid();
		}
		
		$this->client = new SendInBlue_Client\ApiClient( $this->config );
		$this->smtp_client = new SendInBlue_Client\Api\SMTPApi( $this->client );
	}

	public static function setApiKey( $api_key ) {
		SendInBlue_Client\Configuration::getDefaultConfiguration()->setApiKey( 'api-key', $api_key );
	}

	public function sendTemplate( $email, $template, $attributes = array() ) {

		do_action( 'log', 'debug', 'SendinBlue sendTemplate() invoked', 
			array( 'template' => $template, 'attributes' => $attributes ) 
		);

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
			
			do_action( 'log', 'error', 'Sendinblue sendTemplate() API request error', 
				array( 'error' => $e->getResponseBody() ) 
			);
			
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );			
		} catch ( Exception $e ) {
			
			do_action( 'log', 'error', 'Sendinblue sendTemplate() generic request error',
				array( 'error' => $e->getMessage() ) 
			);
			
			throw new Exceptions\ServiceError( $e->getMessage() );
		}
		
		do_action( 'log', 'info', 'Sendinblue sendTemplate() successful', 
			array( 'result' => $result ) 
		);			

	}

	public function send( $email ) {

		do_action( 'log', 'debug', 'SendinBlue send() invoked', 
			array( 'email' => $email ) 
		);

		$sendEmail = new SendInBlue_Client\Model\SendSmtpEmail();
		
		if( $email->to ) { 
			$emailTo = new SendinBlue_Client\Model\SendSmtpEmailTo();
			$emailTo->setEmail( $email->to->email );
			$emailTo->setName( $email->to->name() ?: null );
			$sendEmail->setTo( array( $emailTo ) );
		}
		
		if( $email->cc ) {
			$emailCc = new SendinBlue_Client\Model\SendSmtpEmailCc();
			$emailCc->setEmail( $email->cc->email );
			$emailCc->setName( $email->cc->name() ?: null  );
			$sendEmail->setCc( array( $emailCc ) );
		}		
		
		if( $email->bcc ) {
			$emailBcc = new SendinBlue_Client\Model\SendSmtpEmailBcc();
			$emailBcc->setEmail( $email->bcc->email );
			$emailBcc->setName( $email->bcc->name() ?: null );
			$sendEmail->setBcc( array( $emailBcc ) );
		}

		if ( $email->reply_to ) {
			$emailReplyTo = new SendinBlue_Client\Model\SendSmtpEmailReplyTo();
			$emailReplyTo->setEmail( $email->reply_to->email );
			$emailReplyTo->setName( $email->reply_to->name() ?: null  );
			$sendEmail->setReplyTo( $emailReplyTo );
		}
		
		$sender = new SendInBlue_Client\Model\SendSmtpEmailSender();
		$sender->setEmail( $email->from->email );
		$sender->setName( $email->from->name() ?: null );
		
		$sendEmail->setSender( $sender );
		$sendEmail->setSubject( $email->subject );
		$sendEmail->setHtmlContent( $email->html_body );
		$sendEmail->setTextContent( $email->text_body );
		
		try {
			$result = $this->smtp_client->sendTransacEmail( $sendEmail );
		} catch ( SendInBlue_Client\ApiException $e ) {
			
			do_action( 'log', 'error', 'Sendinblue send() API request error', 
				array( 'error' => $e->getResponseBody() ) 
			);
			
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );
			
		} catch ( Exception $e ) {
			
			do_action( 'log', 'error', 'Sendinblue send() generic request error', 
				array( 'error' => $e->getMessage() ) 
			);
			
			throw new Exceptions\ServiceError( $e->getMessage() );
			
		}
		
		do_action( 'log', 'info', 'Sendinblue send() successful', 
			array( 'result' => $result ) 
		);				

	}

}
