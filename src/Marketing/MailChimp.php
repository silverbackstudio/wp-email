<?php

namespace Svbk\WP\Email\Marketing;

use Svbk\WP\Email\Contact;
use \DrewM\MailChimp\MailChimp as MailChimp_Client;

class MailChimp extends ServiceInterface {

	public $client;

	public function __construct( $api_key ) {
		
		if( empty( $api_key ) ){
			throw new Exceptions\ApiKeyInvalid();
		}
		
		$this->client = new MailChimp_Client( $api_key );
	}

	public function create( Contact $contact, $update = true ) {

		$subscriber_hash = $this->client->subscriberHash( $email );

		$user_attributes = $this->attributes;
		
		if( $contact->first_name ) {
			$user_attributes['FNAME'] = $contact->first_name;
		}
		
		if( $contact->last_name ) {
			$user_attributes['LNAME'] = $contact->last_name;
		}

		foreach ( $contact->lists as $list_id ) {

			$user_info = $this->client->get( "lists/$list_id/members/$subscriber_hash" );

			if ( ! $this->client->success() ) {

				$result = $this->client->post(
					"lists/$list_id/members", 
					array(
						'email_address' => $contact->email,
						'status'        => 'subscribed',
						'ip_signup'     => $_SERVER['REMOTE_ADDR'],
						'ip_opt'        => $_SERVER['REMOTE_ADDR'],
						'language'      => substr( get_locale(), 0, 2 ),
						'merge_fields' => $user_attributes,
					)
				);

				if ( ! $this->client->success() ) {
					throw new Exceptions\ServiceError( $this->client->getLastError() );
				}
			} elseif ( $update ) {

				$result = $this->update( $contact, $user_attributes, $list_id );

				if ( ! empty( $contact->lists ) ) {
					$this->listSubscribe( $contact, $contact->lists );
				}
			}
		}

		return $result;
	}

	public function updateAttributes( Contact $contact ) {
		$this->update( $email, [ 'merge_fields' => $contact->attributes ] );
	}

	public function update( Contact $contact, $attributes ) {

		$subscriber_hash = $this->client->subscriberHash( $email );

		foreach ( $contact->lists as $list_id ) {

			$user_info = $this->client->get( "lists/$list_id/members/$subscriber_hash" );
	
			if ( $this->client->success() ) {
	
				$result = $this->client->patch( "lists/$list_id/members/$subscriber_hash", $attributes );
	
				if ( ! $this->client->success() ) {
					throw new Exceptions\ServiceError( $this->client->getLastError() );
				}
			} else {
				throw new Exceptions\ContactNotExist();
			}
			
		}

		return $result;
	}

	public function listSubscribe( Contact $contact, $lists ) {

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
				throw new Exceptions\ContactNotExist();
			}
		}

		return $results;
	}

	public function listUnsubscribe( Contact $contact, $lists ) {

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
				throw new Exceptions\ContactNotExist();
			}
		}

		return $result;
	}

}
