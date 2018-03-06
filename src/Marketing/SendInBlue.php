<?php
namespace Svbk\WP\Email\Marketing;

use Svbk\WP\Email\Contact;
use Exception;
use SendinBlue\Client as SendInBlue_Client;

class SendInBlue extends ServiceInterface {

	public $config;
	public $client;
	
	public $client_contacts;

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
		$this->client_contacts = new SendInBlue_Client\Api\ContactsApi( $this->client );
	}

	public function create( Contact $contact, $update = true ) {

		$data =  array();

		if( $contact->email ) {
			$data['email']= $contact->email;
		}
		
		if( ! empty( $contact->attributes ) ) {
			$data['attributes'] = $contact->attributes;
		}

		if( $contact->first_name ) {
			$data['attributes']['NAME'] = $contact->first_name;
			$data['attributes']['NOME'] = $contact->first_name;
		}
		
		if( $contact->last_name ){
			$data['attributes']['SURNAME'] = $contact->last_name;
		}

		$data['updateEnabled'] = $update;
		
		$createContact = new SendInBlue_Client\Model\CreateContact( $data );

		if ( ! empty( $contact->lists ) ) {
			$createContact->setListIds( $contact->lists );
		}

		try {
			$result = $this->client_contacts->createContact( $createContact );
		} catch ( SendInBlue_Client\ApiException $e ) {
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );
		} catch ( Exception $e ) {
			throw new Exceptions\ServiceError( $e->getMessage() );
		}

		return $result;
	}

	public function listSubscribe( Contact $contact, $lists = array() ) {
		$result = $this->update(
			$contact, [
				'listIds' => $lists,
			]
		);
		
		foreach( $lists as $list_id ) {
			$contact->listSubscribe( $list_id );
		}		
		
		return $result;
	}

	public function listUnsubscribe( Contact $contact, $lists = array() ) {
		$result = $this->update(
			$contact, [
				'unlinkListIds' => $lists,
			]
		);
		
		foreach( $lists as $list_id ) {
			$contact->listUnsubscribe( $list_id );
		}
		
		return $result;
	}


	public function updateAttributes( Contact $contact, $user_attributes = array() ) {
		return $this->update(
			$contact, [
				'attributes' => $user_attributes,
			]
		);
	}

	public function update( Contact $contact, $attributes = array() ) {

		$updateContact = new SendInBlue_Client\Model\UpdateContact( $attributes );

		try {
			$result = $this->client_contacts->updateContact( $createContact );
		} catch ( SendInBlue_Client\ApiException $e ) {
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );
		} catch ( Exception $e ) {
			throw new Exceptions\ServiceError( $e->getMessage() );
		}

	}

}
