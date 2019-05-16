<?php
namespace Svbk\WP\Email\Transactional;

use Svbk\WP\Email\Utils;
use SendinBlue\Client as SendInBlue_Client;
use Exception;
use GuzzleHttp;

class SendInBlue implements ServiceInterface {

	public $api_key;

	public $config;
	public $client;
	public $smtp_client;

	const TEMPLATE_SUPPORT = true;

	public function __construct( $api_key = null, $config = null, $httpClient = null ) {

		if ( $config instanceof SendInBlue_Client\Configuration ) {
			$this->config = $config;
		} else {
			$this->config = SendInBlue_Client\Configuration::getDefaultConfiguration();
		}

		if ( is_string( $api_key ) ) {
			$this->config = new SendInBlue_Client\Configuration();
			$this->config->setApiKey( 'api-key', $api_key );
		} else {
			$this->config = SendInBlue_Client\Configuration::getDefaultConfiguration();
		}

		if ( ! $this->config->getApiKey( 'api-key' ) ) {

			do_action( 'log', 'critical', 'Missing Sendinblue API key' );

			throw new Exceptions\ApiKeyInvalid();
		}

		if ( ! $httpClient ) {
			$httpClient = new GuzzleHttp\Client();
		}

		$this->smtp_client = new SendInBlue_Client\Api\SMTPApi(
			$httpClient,
			$this->config
		);
	}

	public function setApiKey( $api_key ) {
		$this->config->setApiKey( 'api-key', $api_key );
	}

	public function sendTemplate( $template, $message, $attributes = array() ) {
        
		do_action(
			'log', 'debug', 'SendinBlue sendTemplate() invoked',
			array(
				'template' => $template,
				'attributes' => $attributes,
			)
		);

		return $this->send( $message, $template, $attributes );
	}


	public function send( $message, $template = null, $attributes = array() ) {

		do_action(
			'log', 'debug', 'SendinBlue send() invoked',
			array(
				'email' => $message,
				'template' => $template,
				'attributes' => $attributes,
			)
		);

		if ( ! empty( $attributes ) ) {
			$message->setAttributes( $attributes );
		}

		$sendSmtpEmail = $this->prepareSend( $message, $template );

		try {
		    $result = $this->smtp_client->sendTransacEmail( $sendSmtpEmail );
		} catch ( SendInBlue_Client\ApiException $e ) {

			$error = json_decode( $e->getResponseBody() );

			do_action(
				'log', 'error', 'Sendinblue send() API request error',
				array(
					'error' => $e->getResponseBody(),
				)
			);

			throw new Exceptions\ServiceError( $error->message );

		} catch ( Exception $e ) {

			do_action(
				'log', 'error', 'Sendinblue send() generic request error',
				array(
					'error' => $e->getMessage(),
				)
			);

			throw new Exceptions\ServiceError( $e->getMessage() );

		}

		do_action(
			'log', 'info', 'Sendinblue send() successful',
			array(
				'result' => $result,
			)
		);
        
	    $message_id = $result->getMessageId();
		
	    return $message_id ?: true;
	}


	public function prepareSend( $message, $template = null ) {

		do_action(
			'log', 'debug', 'SendinBlue send() invoked',
			array(
				'email' => $message,
			)
		);
		$sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail();

		if ( ! empty( $message->from ) ) {
			$sender = new SendInBlue_Client\Model\SendSmtpEmailSender();
			$sender->setEmail( $message->from->email );
			$sender->setName( $message->from->name() ?: null );
			$sendSmtpEmail->setSender( $sender );
			
		} elseif ( !$template ) {
			throw new Exceptions\MessageMissingFrom();
		}

		if ( ! empty( $message->to ) ) {
			$sendSmtpEmail->setTo( self::castContacts( $message->to,  SendinBlue_Client\Model\SendSmtpEmailTo::class ) );
		} else {
			throw new Exceptions\MessageMissingTo();
		}

		if ( ! empty( $message->cc ) ) {
			$sendSmtpEmail->setCc( self::castContacts( $message->cc,  SendinBlue_Client\Model\SendSmtpEmailCc::class ) );
		}

		if ( ! empty( $message->bcc ) ) {
			$sendSmtpEmail->setBcc( self::castContacts( $message->bcc, SendinBlue_Client\Model\SendSmtpEmailBcc::class ) );
		}

		if ( ! empty( $message->reply_to ) ) {
			$emailReplyTo = new SendinBlue_Client\Model\SendSmtpEmailReplyTo();
			$emailReplyTo->setEmail( $message->reply_to->email );
			$emailReplyTo->setName( $message->reply_to->name() ?: null );
			$sendSmtpEmail->setReplyTo( $emailReplyTo );
		}

		if ( $message->subject ) {
			$sendSmtpEmail->setSubject( $message->subject );
		} elseif ( !$template ) {
			throw new Exceptions\MessageMissingSubject();
		}

		if ( ! $message->html_body && ! $message->text_body && !$template ) {
			throw new Exceptions\MessageMissingBody();
		}

		if ( $message->html_body ) {
			$sendSmtpEmail->setHtmlContent( $message->html_body );
		}

		if ( $message->text_body ) {
			$sendSmtpEmail->setTextContent( $message->text_body );
		}

		$headers = $message->getHeaders( true );
		if ( ! empty( $headers ) ) {
			$sendSmtpEmail->setHeaders( $headers );
		}

		if ( ! empty( $message->tags ) ) {
			$sendSmtpEmail->setTags( $message->tags );
		}

		if ( ! empty( $message->getAttributes() ) ) {
			$sendSmtpEmail->setParams( $message->getAttributes() );
		}

		if ( ! empty( $template ) ) {
			$sendSmtpEmail->setTemplateId( intval( $template )  );
		}

		// $attachments = $message->getAttachments();
		// if ( !empty($attachments) ) {
		// $sendSmtpEmail->setHedaers( $attachments );
		// }
		return $sendSmtpEmail;
	}

	public static function castContacts( $contacts, $class ) {
		$cast_contacts = array();

		foreach ( $contacts as $contact ) {
			$cast_contact = new $class();

			if ( $contact->email ) {
				$cast_contact->setEmail( $contact->email );
			}

			$name = $contact->name();

			if ( $name ) {
				$cast_contact->setName( $name );
			}

			$cast_contacts[] = $cast_contact;
		}

		return $cast_contacts;
	}


	public function getTemplates( $limit = 50, $offset = 0 ) {

		$templateQuery = $this->smtp_client->getSmtpTemplates( null, $limit, $offset );

		$templates = array();

		if ( $templateQuery->getCount() > 0 ) {
			$templateObjects = $templateQuery->getTemplates();

			foreach ( $templateObjects as $template ) {
				$templates[ $template->getId() ] = $template->getName();
			}
		}

		return $templates;
	}

}
