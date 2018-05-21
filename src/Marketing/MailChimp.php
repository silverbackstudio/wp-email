<?php

namespace Svbk\WP\Email\Marketing;

use Svbk\WP\Email\Contact;
use Svbk\WP\Email\Utils;
use \DrewM\MailChimp\MailChimp as MailChimp_Client;

class MailChimp extends ServiceInterface {

	public $id = 'mailchimp';
	public $client;

	public function __construct( $api_key ) {
		
		if( empty( $api_key ) ){
			throw new Exceptions\ApiKeyInvalid();
		}
		
		$this->client = new MailChimp_Client( $api_key );
	}

	public function createContact( Contact $contact ) {

		$subscriber_hash = $this->client->subscriberHash( $contact->email );

		$user_attributes = $contact->attributes;
		
		if( $contact->first_name() ) {
			$user_attributes['FNAME'] = $contact->first_name();
		}
		
		if( $contact->last_name() ) {
			$user_attributes['LNAME'] = $contact->last_name();
		}

		foreach ( $contact->lists as $list_id ) {

			$mc_attributes = array(
				'email_address' => $contact->email,
				'status'        => 'subscribed',
				'ip_signup'     => $_SERVER['REMOTE_ADDR'],
				'ip_opt'        => $_SERVER['REMOTE_ADDR'],
				'language'      => substr( get_locale(), 0, 2 ),
				'merge_fields' => Utils::upperKeys( array_filter( $user_attributes ) ),
			);

			$mc_attributes = apply_filters( 'svbk_email_contact_create_mailchimp_attributes', $mc_attributes, $contact, $list_id, $this );

			$raw_result = $this->client->post( "lists/$list_id/members", $mc_attributes	);

			if ( $this->client->success() ) {
				do_action('svbk_email_contact_created', $raw_result, $user_attributes, $this );
				do_action('svbk_email_contact_created_mailchimp', $raw_result, $user_attributes, $this );
			} else {
				throw new Exceptions\ContactAlreadyExists( $this->client->getLastError() );	
			}
			
		} 
		
		if( isset($raw_result['id']) ) {
			return $raw_result['id'];
		}
	}

	public function saveContact( Contact $contact, $custom_attributes = array() ) {

		$subscriber_hash = $this->client->subscriberHash( $contact->email );

		$attributes = $custom_attributes;
		$attributes['merge_fields'] = Utils::upperKeys( array_filter( $contact->attributes ) ) ;

		if( $contact->first_name() ) {
			$attributes['merge_fields']['FNAME'] = $contact->first_name();
		}
		
		if( $contact->last_name() ) {
			$attributes['merge_fields']['LNAME'] = $contact->last_name();
		}
		
		if(	$contact->phone ) {
			$attributes['PHONE'] = $contact->phone;
		}

		foreach ( $contact->lists as $list_id ) {

			$user_info = $this->client->get( "lists/$list_id/members/$subscriber_hash" );
	
			if ( $this->client->success() ) {
	
				$raw_result = $this->client->patch( "lists/$list_id/members/$subscriber_hash", $attributes );
	
				if ( $this->client->success() ) {
					do_action('svbk_email_contact_updated', $raw_result, $attributes, $this );
					do_action('svbk_email_contact_updated_mailchimp', $raw_result, $attributes, $this );								
				} else {
					throw new Exceptions\ServiceError( $this->client->getLastError() );
				}
				
			} else {
				throw new Exceptions\ContactNotExists();
			}
			
		}

		return $raw_result;
	}

	public function listSubscribe( Contact $contact, $lists = array() ) {

		$results = array();
		$args = array();

		$subscriber_hash = $this->client->subscriberHash( $email );

		foreach ( (array) $lists as $list_id ) {

			$user_info = $this->client->get( "lists/$list_id/members/$subscriber_hash" );

			if ( $this->client->success() ) {

				if ( isset( $user_info['status'] ) && ( 'unsubscribed' === $user_info['status'] ) ) {
					$args['status'] = 'subscribed';
				}

				$results[ $list_id ] = $this->patch( "lists/$list_id/members/$subscriber_hash", $args );

				if ( $this->success() ) {
					$contact->listSubscribe( $list_id );
				} else {
					throw new Exceptions\ServiceError( $this->client->getLastError() );
				}
				
			} else {
				throw new Exceptions\ContactNotExists();
			}
		}

		return $results;
	}

	public function listUnsubscribe( Contact $contact, $lists = array() ) {

		$results = array();
		$args = array();

		$subscriber_hash = $this->client->subscriberHash( $email );

		foreach ( (array) $lists as $list_id ) {

			$user_info = $this->client->get( "lists/$list_id/members/$subscriber_hash" );

			if ( $this->client->success() ) {

				if ( isset( $user_info['status'] ) && ( 'unsubscribed' !== $user_info['status'] ) ) {
					$args['status'] = 'unsubscribed';
				}

				$results[ $list_id ] = $this->patch( "lists/$list_id/members/$subscriber_hash" );

				if ( $this->success() ) {
					$contact->listUnsubscribe( $list_id );
				} else {
					throw new Exceptions\ServiceError( $this->client->getLastError() );
				}
				
			} else {
				throw new Exceptions\ContactNotExists();
			}
		}

		return $result;
	}

}
