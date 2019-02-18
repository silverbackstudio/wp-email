<?php

namespace Svbk\WP\Email\Marketing;

use DateTime;
use Svbk\WP\Email\Contact;
use Svbk\WP\Email\Utils;
use \DrewM\MailChimp\MailChimp as MailChimp_Client;

class MailChimp extends ServiceInterface {

	public $id = 'mailchimp';
	public $client;

	const DATE_FORMAT = DateTime::ISO8601;

	public function __construct( $api_key ) {

		if ( empty( $api_key ) ) {
			do_action( 'log', 'critical', 'Missing Mailchimp API key' );
			throw new Exceptions\ApiKeyInvalid();
		}

		$this->client = new MailChimp_Client( $api_key );
	}

	public function createContact( Contact $contact ) {

		$subscriber_hash = $this->client->subscriberHash( $contact->email );

		do_action(
			'log', 'debug', 'Mailchimp createContact() invoked',
			array(
				'contact' => $contact,
			)
		);

		$user_attributes = $contact->getAttributes();

		if ( $contact->first_name() ) {
			$user_attributes['FNAME'] = $contact->first_name();
		}

		if ( $contact->last_name() ) {
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

			$raw_result = $this->client->post( "lists/$list_id/members", $mc_attributes );

			if ( $this->client->success() ) {
				do_action( 'svbk_email_contact_created', $raw_result, $user_attributes, $this );
				do_action( 'svbk_email_contact_created_mailchimp', $raw_result, $user_attributes, $this );

				do_action(
					'log', 'debug', 'Mailchimp user successfully inserted',
					array(
						'result' => $raw_result,
						'list_id' => $list_id,
					)
				);
			} else {
				do_action(
					'log', 'notice', 'Mailchimp createContact() request not successful on list {list_id}',
					array(
						'error' => $this->client->getLastError(),
						'list_id' => $list_id,
					)
				);
				throw new Exceptions\ContactAlreadyExists( $this->client->getLastError() );
			}
		}

		if ( isset( $raw_result['id'] ) ) {
			return $raw_result['id'];
		}
	}

	public function saveContact( Contact $contact, $custom_attributes = array() ) {

		$subscriber_hash = $this->client->subscriberHash( $contact->email );

		do_action(
			'log', 'debug', 'Mailchimp saveContact() invoked',
			array(
				'contact' => $contact,
			)
		);

		$attributes = $custom_attributes;
		$attributes['merge_fields'] = Utils::upperKeys( array_filter( $contact->getAttributes() ) );

		if ( $contact->first_name() ) {
			$attributes['merge_fields']['FNAME'] = $contact->first_name();
		}

		if ( $contact->last_name() ) {
			$attributes['merge_fields']['LNAME'] = $contact->last_name();
		}

		if ( $contact->phone ) {
			$attributes['PHONE'] = $contact->phone;
		}

		foreach ( $contact->lists as $list_id ) {

			$user_info = $this->client->get( "lists/$list_id/members/$subscriber_hash" );

			if ( $this->client->success() ) {

				$raw_result = $this->client->patch( "lists/$list_id/members/$subscriber_hash", $attributes );

				do_action(
					'log', 'info', 'Mailchimp user successfully inserted',
					array(
						'result' => $raw_result,
						'list_id' => $list_id,
					)
				);

				if ( $this->client->success() ) {
					do_action( 'svbk_email_contact_updated', $raw_result, $attributes, $this );
					do_action( 'svbk_email_contact_updated_mailchimp', $raw_result, $attributes, $this );

					do_action(
						'log', 'info', 'Mailchimp user successfully patched',
						array(
							'result' => $raw_result,
							'list_id' => $list_id,
						)
					);

				} else {
					do_action(
						'log', 'error', 'Mailchimp user PATCH request not successful on list {list_id}',
						array(
							'error' => $this->client->getLastError(),
							'list_id' => $list_id,
						)
					);

					throw new Exceptions\ServiceError( $this->client->getLastError() );
				}
			} else {

				do_action(
					'log', 'notice', 'Mailchimp user GET request  to update not successful on list {list_id}',
					array(
						'error' => $this->client->getLastError(),
						'list_id' => $list_id,
					)
				);

				throw new Exceptions\ContactNotExists();
			}
		}

		return $raw_result;
	}

	public function listSubscribe( Contact $contact, $lists = array() ) {

		$results = array();
		$args = array();

		$subscriber_hash = $this->client->subscriberHash( $email );

		do_action(
			'log', 'debug', 'Mailchimp listSubscribe() invoked',
			array(
				'contact' => $contact,
				'lists' => $lists,
			)
		);

		foreach ( (array) $lists as $list_id ) {

			$user_info = $this->client->get( "lists/$list_id/members/$subscriber_hash" );

			if ( $this->client->success() ) {

				if ( isset( $user_info['status'] ) && ( 'unsubscribed' === $user_info['status'] ) ) {
					$args['status'] = 'subscribed';
				}

				$results[ $list_id ] = $this->patch( "lists/$list_id/members/$subscriber_hash", $args );

				if ( $this->success() ) {
					$contact->listSubscribe( $list_id );

					do_action(
						'log', 'debug', 'Mailchimp user successfully subscribed to list',
						array(
							'result' => $results[ $list_id ],
							'list_id' => $list_id,
						)
					);

				} else {

					do_action(
						'log', 'error', 'Mailchimp user PATCH request not successful on list {list_id}',
						array(
							'error' => $this->client->getLastError(),
							'list_id' => $list_id,
						)
					);

					throw new Exceptions\ServiceError( $this->client->getLastError() );
				}
			} else {

				do_action(
					'log', 'notice', 'Mailchimp user GET request to subscribe not successful on list {list_id}',
					array(
						'error' => $this->client->getLastError(),
						'list_id' => $list_id,
					)
				);

				throw new Exceptions\ContactNotExists();
			}
		}

		return $results;
	}

	public function listUnsubscribe( Contact $contact, $lists = array() ) {

		$results = array();
		$args = array();

		do_action(
			'log', 'debug', 'Mailchimp listUnsubscribe() invoked',
			array(
				'contact' => $contact,
				'lists' => $lists,
			)
		);

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

					do_action(
						'log', 'debug', 'Mailchimp user successfully unsubscribed from list',
						array(
							'result' => $results[ $list_id ],
							'list_id' => $list_id,
						)
					);

				} else {

					do_action(
						'log', 'error', 'Mailchimp user PATCH request not successful on list {list_id}',
						array(
							'error' => $this->client->getLastError(),
							'list_id' => $list_id,
						)
					);

					throw new Exceptions\ServiceError( $this->client->getLastError() );
				}
			} else {

				do_action(
					'log', 'notice', 'Mailchimp user GET request to unsubscribe not successful on list {list_id}',
					array(
						'error' => $this->client->getLastError(),
						'list_id' => $list_id,
					)
				);

				throw new Exceptions\ContactNotExists();
			}
		}

		return $result;
	}

	public static function formatDate( DateTime $date ) {
		return $date->format( self::DATE_FORMAT );
	}

}
