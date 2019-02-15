<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Svbk\WP\Email\Contact;
use Svbk\WP\Email\Utils;

final class UtilsTest extends TestCase {

	public function testCanExtractColumn() {

		$contacts = array();

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$attributes = array(
			'first_name' => 'First2',
			'middle_name' => 'Middle2',
			'last_name' => 'Last2',
			'email' => 'user2@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$emails = Utils::extract( $contacts, 'email' );

		$this->assertEquals(
			[ 'user@example.com', 'user2@example.com' ],
			$emails
		);
	}

	public function testCanExtractIndexColumn() {

		$contacts = array();

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$attributes = array(
			'first_name' => 'First2',
			'middle_name' => 'Middle2',
			'last_name' => 'Last2',
			'email' => 'user2@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$emails = Utils::extract( $contacts, 'first_name', 'email' );

		$this->assertEquals(
			[
				'user@example.com' => 'First',
				'user2@example.com' => 'First2',
			],
			$emails
		);
	}

	public function testCanExtractEmptyColumns() {

		$contacts = array();

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$attributes = array(
			// 'first_name' => 'First',
			'middle_name' => 'Middle2',
			'last_name' => 'Last2',
			'email' => 'user2@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$attributes = array(
			'first_name' => 'First3',
			'middle_name' => 'Middle3',
			'last_name' => 'Last3',
			// 'email' => 'user3@example.com',
		);

		$contacts[] = new Contact( $attributes );
		
		$attributes = array(
			'first_name' => 'First4',
			'middle_name' => 'Middle4',
			'last_name' => 'Last4',
			// 'email' => 'user4@example.com',
		);

		$contacts[] = new Contact( $attributes );		

		$emails = Utils::extract( $contacts, 'first_name', 'email' );

		$this->assertEquals(
			[
				'user@example.com' => 'First',
				'user2@example.com' => '',
				'First3',
				'First4',
			],
			$emails
		);
	}

	public function testCanParseRecipients() {

		$contacts = array();

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$attributes = array(
			'first_name' => 'First',
			'email' => 'user2@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$attributes = array(
			'email' => 'user3@example.com',
		);

		$contacts[] = new Contact( $attributes );

		// Invalid contact email
		$attributes = array(
			'first_name' => 'First',
		);

		$contacts[] = new Contact( $attributes );

		$contacts_addresses = [];

		foreach ( $contacts as $contact ) {
			$contacts_addresses[] = $contact->emailAddress();
		}

		unset( $contacts[3] );

		$parsed_contacts = Utils::parse_recipients( join( $contacts_addresses, ';' ) );

		$this->assertEquals(
			$contacts,
			$parsed_contacts
		);

		$parsed_contacts = Utils::parse_recipients( join( $contacts_addresses, ',' ) );

		$this->assertEquals(
			$contacts,
			$parsed_contacts
		);
	}

	public function testCanParseOneRecipient() {

		$contacts = array();

		// Invalid contact email
		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contacts[] = new Contact( $attributes );

		$contacts_addresses = [];

		foreach ( $contacts as $contact ) {
			$contacts_addresses[] = $contact->emailAddress();
		}

		$parsed_contacts = Utils::parse_recipients( join( $contacts_addresses, ';' ) );

		$this->assertEquals(
			$contacts,
			$parsed_contacts
		);

		$parsed_contacts = Utils::parse_recipients( join( $contacts_addresses, ',' ) );

		$this->assertEquals(
			$contacts,
			$parsed_contacts
		);
	}

	public function testCanParseOneInvalidRecipient() {

		$contacts = array();

		// Invalid contact email
		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
		);

		$contacts[] = new Contact( $attributes );

		$contacts_addresses = [];

		foreach ( $contacts as $contact ) {
			$contacts_addresses[] = $contact->emailAddress();
		}

		$parsed_contacts = Utils::parse_recipients( join( $contacts_addresses, ';' ) );

		$this->assertEmpty( $parsed_contacts );

		$parsed_contacts = Utils::parse_recipients( join( $contacts_addresses, ',' ) );

		$this->assertEmpty( $parsed_contacts );
	}

	public function testCanParseMalformedRecipient() {

		$recipients = ';aa;e23e<ddd;.,4ew,oe87fso8<;p';

		$parsed_contacts = Utils::parse_recipients( $recipients );

		$this->assertEmpty( $parsed_contacts );
	}

	public function testCanUppercaseArray() {

		$pairs = [
			'aaaa' => 'v1',
			'bbbAA' => 'v2',
			'.é' => 'v3',
			'2323' => 'v3',
			'v5',
			'\'' => 'v5',
		];

		$uc_pairs = Utils::upperKeys( $pairs );

		$this->assertEquals(
			[
				'AAAA' => 'v1',
				'BBBAA' => 'v2',
				// Same values, in case one days someone would like to use array_flip
				'.É' => 'v3',
				'2323' => 'v3',
				'v5',
				'\'' => 'v5',
			],
			$uc_pairs
		);
	}

}
