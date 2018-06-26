<?php
namespace Svbk\WP\Email\Marketing;

use Svbk\WP\Email\Contact;
use Exception;
use SendinBlue\Client as SendInBlue_Client;

class SendInBlue extends ServiceInterface {

	public $id = 'sendinblue';

	public $config;
	public $client;
	
	public $client_contacts;
	
	public $name_attribute = 'NOME';

	const DATE_FORMAT = 'Y-m-d';

	public function __construct( $api_key = null ) {
		
		if( $api_key ) {
			$this->config = new SendInBlue_Client\Configuration;
			$this->config->setApiKey( 'api-key', $api_key );
		} else {
			$this->config = SendInBlue_Client\Configuration::getDefaultConfiguration();
		}
		
		if( ! $this->config->getApiKey('api-key') ) {
			do_action( 'log', 'critical', 'Missing Sendinblue API key' );
			throw new Exceptions\ApiKeyInvalid();
		}
		
		$this->client = new SendInBlue_Client\ApiClient( $this->config );
		$this->client_contacts = new SendInBlue_Client\Api\ContactsApi( $this->client );
	}

	public static function remove_plugin_script(){
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_plugin_script' ), 1000 );
	}

	public static function dequeue_plugin_script(){
		wp_dequeue_script( 'sib-front-js' );
	}

	public function createContact( Contact $contact ) {

		$contact_attributes = $this->parseContact( $contact );

		if( $contact->email ) {
			$contact_attributes['email']= $contact->email;
		}

		$contact_attributes['updateEnabled'] = false;

		$contact_attributes = apply_filters( 'svbk_email_contact_create_sendinblue_attributes', $contact_attributes, $contact, $contact->lists, $this );

		do_action( 'log', 'debug', 'SendinBlue createContact() invoked', 
			array( 'attributes' => $contact_attributes )
		);

		$createContact = new SendInBlue_Client\Model\CreateContact( $contact_attributes );

		if ( ! empty( $contact->lists ) ) {
			$createContact->setListIds( $contact->lists );
		}

		try {				

			$raw_result = $this->client_contacts->createContact( $createContact );
			
		} catch ( SendInBlue_Client\ApiException $e ) {
			
			$error = $e->getResponseBody();
			
			if( 'duplicate_parameter' === $error->code ) {
				do_action( 'log', 'notice', 'Sendinblue createContact() API duplicate contact found' );		
						
				throw new Exceptions\ContactAlreadyExists( null, 0, $e );
			}			
			
			do_action( 'log', 'error', 'Sendinblue createContact() API request error', 
				array( 'error' => $error ) 
			);			
			
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );
		} catch ( Exception $e ) {
			
			do_action( 'log', 'error', 'Sendinblue createContact() API request general error', 
				array( 'error' => $e->getMessage() ) 
			);				
			
			throw new Exceptions\ServiceError( $e->getMessage() );
		}
		
		$user_id = empty( $raw_result->id ) ? null : $raw_result->getId();
					
		do_action('svbk_email_contact_created', $user_id, $raw_result, $contact_attributes, $this );
		do_action('svbk_email_contact_created_sendinblue', $user_id, $raw_result, $contact_attributes, $this );					

		do_action( 'log', 'info', 'Sendinblue createContact() successful', 
			array( 'result' => $raw_result ) 
		);				

		return $user_id;
	}


	public function getContact( $search_contact ){
		
		do_action( 'log', 'debug', 'SendinBlue createContact() invoked', 
			array( 'email' => $search_contact->email )
		);		
		
		try {
			$raw_result = $this->client_contacts->getContactInfo( $search_contact->email );
		} catch ( SendInBlue_Client\ApiException $e ) {
			
			$error = $e->getResponseBody();
			
			if( 'document_not_found' === $error->code ) {
				do_action( 'log', 'notice', 'Sendinblue getContact() API contact not found',
					array( 'contact' => $contact )
				);		
				throw new Exceptions\ContactNotExists();
			}
			
			do_action( 'log', 'error', 'Sendinblue getContact() API request error', 
				array( 'error' => $error ) 
			);				
			
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );
			
		} catch ( Exception $e ) {
			do_action( 'log', 'error', 'Sendinblue getContact() API request general error', 
				array( 'error' => $e->getMessage() ) 
			);			
			
			throw new Exceptions\ServiceError( $e->getMessage() );
		}
	
		do_action( 'log', 'info', 'Sendinblue getContact() API request successful', 
			array( 'result' => $raw_result ) 
		);		
	
		return $this->castContact( $raw_result );
	}

	public function listSubscribe( Contact $contact, $lists = array() ) {
		
		foreach( $lists as $list_id ) {
			$contact->listSubscribe( $list_id );
		}		
		
		$this->saveContact( $contact );
		
		return $result;
	}

	public function listUnsubscribe( Contact $contact, $lists = array() ) {
		
		foreach( $lists as $list_id ) {
			$contact->listUnsubscribe( $list_id );
		}		
		
		$result = $this->saveContact(
			$contact, [
				'unlinkListIds' => $lists,
			]
		);
		
		return $result;
	}

	public function saveContact( Contact $contact, $custom_attributes = array() ) {

		$updateContact = new SendInBlue_Client\Model\UpdateContact( $custom_attributes );

		$data = $this->parseContact( $contact );
		
		if( !empty( $data ) ) {
			$updateContact->setAttributes( $data['attributes'] );
		}
		
		if ( ! empty( $contact->lists ) ) {
			$updateContact->setListIds( $contact->lists );
		}
		
		do_action( 'log', 'debug', 'SendinBlue saveContact() invoked', 
			array( 'contact' => $contact )
		);		
		
		try {
			$raw_result = $this->client_contacts->updateContact( $contact->email, $updateContact );
		} catch ( SendInBlue_Client\ApiException $e ) {
			
			$error = $e->getResponseBody();
			
			if( 'document_not_found' === $error->code ) {
				do_action( 'log', 'notice', 'Sendinblue saveContact() API contact not found',
					array( 'contact' => $contact )
				);		
				throw new Exceptions\ContactNotExists();
			}			
			
			do_action( 'log', 'error', 'Sendinblue saveContact() API request error', 
				array( 'error' => $error ) 
			);	
			
			throw new Exceptions\ServiceError( $e->getResponseBody()->message );
		} catch ( Exception $e ) {
			
			do_action( 'log', 'error', 'Sendinblue saveContact() API request general error', 
				array( 'error' => $e->getMessage() ) 
			);				
			
			throw new Exceptions\ServiceError( $e->getMessage() );
		}
		
		do_action( 'log', 'info', 'Sendinblue saveContact() API request successful', 
			array( 'result' => $raw_result ) 
		);	

		return true;

	}
	
	protected function parseContact( Contact $contact ){
		
		$data =  array();

		if( ! empty( $contact->attributes ) ) {
			$data['attributes'] = $contact->attributes;
		}

		if( $contact->first_name() ) {
			$data['attributes'][$this->name_attribute] = $contact->first_name();
		}
		
		if( $contact->last_name() ){
			$data['attributes']['SURNAME'] = $contact->last_name();
		}

		if( $contact->phone ) {
			$data['attributes']['sms'] = $contact->phone;
			$data['attributes']['sms'] = $contact->phone;
		}		
		
		return $data;
	}
	
	public function castContact( $raw_result ){
		
		$contact = new Contact();
		
		if ( $raw_result->getId() ) {
			$contact->id = $raw_result->getId();
		}
		
		if ( $raw_result->getEmail() ) {
			$contact->email = $raw_result->getEmail();
		}
		
		if ( $raw_result->getListIds() ) {
			$contact->lists = $raw_result->getListIds();
		}		
		
		$attributes = $raw_result->getAttributes();
		
		if( !empty( $attributes[$this->name_attribute] )  ) {
			$contact->first_name = $attributes[$this->name_attribute];
		}
		
		if( !empty( $attributes['SURNAME'] ) ) {
			$contact->last_name = $attributes['SURNAME'];
		}	
		
		if( !empty( $attributes['sms'] ) ) {
			$contact->phone = $attributes['sms'];
		}			
		
		return $contact;
		
	}
	
	public function getLists( $limit = 10, $offset = 0 ) {
		
		$cache_key = 'svbk_email_sendinblue_lists_' . $limit . '_' . $offset; 
		
		$lists = get_transient( $cache_key );
		
		if ( false === $lists ) {
			
			try {
		
				do_action( 'log', 'debug', 'SendinBlue getLists() invoked' );	
			
				$list_client = new SendInBlue_Client\Api\ListsApi( $this->client );
				$list_result = $list_client->getLists($limit, $offset);
			
			} catch (Exception $e) {
				
				do_action( 'log', 'error', 'SendinBlue getLists() request error',
					array( 'error' => $e->getMessage() )
				);
				
			    return false;
			}				
			
			$lists = wp_list_pluck( $list_result->getLists(), 'name', 'id' );
			
			do_action( 'log', 'info', 'SendinBlue getLists() successful retreive',
				array( 'result' => $list_result ) 
			);			
			
			set_transient( $cache_key, $lists, 1 * MINUTE_IN_SECONDS );	
		} else {
			do_action( 'log', 'debug', 'SendinBlue getLists() loaded from cache', 
				array( 'lists' => $lists ) 
			);
		}
		
		return $lists;
	}
	
}
