<?php

namespace Svbk\WP\Email\Marketing;

use Svbk\WP\Email\Contact;
use Exception;
use DateTime;


class NullService extends ServiceInterface {

	const DATE_FORMAT = DateTime::ISO8601;

	public function createContact( Contact $contact ) {
		return 1;
	}
	
	public function getContact( $search_contact ) { 
		return new Contact();
	}

	public function listSubscribe( Contact $contact, $lists = array() ) {
		return 1;
	}

	public function listUnsubscribe( Contact $contact, $lists = array() ) {
		return 1;
	}

	public function saveContact( Contact $contact, $custom_attributes = array()) {
		return 1;
	}
	
	public static function formatDate( DateTime $date ) {
		return $date->format( self::DATE_FORMAT );
	}	

}
