<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Svbk\WP\Email\Contact;

final class ContactTest extends TestCase {


	public function testCanSetContactProperties() {
		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contact = new Contact( $attributes );

		$this->assertEquals(
			'First Middle Last <user@example.com>' ,
			$contact->emailAddress()
		);
	}

	public function testCanFormatAddress() {

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contact = new Contact( $attributes );

		$this->assertEquals(
			'First Middle Last <user@example.com>' ,
			$contact->emailAddress()
		);

		$attributes = array(
			'email' => 'user@example.com',
		);

		$contact = new Contact( $attributes );

		$this->assertEquals(
			'user@example.com' ,
			$contact->emailAddress()
		);

	}

	public function testCanAssignAttributes() {

		$attributes = array(
			'id' => 2,
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'email@example.com',
			'phone' => '0552286576',
		);

		$contact = new Contact( $attributes );

		foreach ( $attributes as $key => $val ) {
			$this->assertEquals(
				$val ,
				$contact->{$key}
			);
		}

	}

	public function testCanSplitNames() {

		$contact = new Contact();
		$contact->first_name = 'First Middle Last';

		$this->assertEquals(
			'First',
			$contact->first_name()
		);

		$this->assertEquals(
			'Middle',
			$contact->middle_name()
		);

		$this->assertEquals(
			'Last',
			$contact->last_name()
		);

		$contact->setName( 'First Middle Last' );

		$this->assertEquals(
			'First',
			$contact->first_name()
		);

		$this->assertEquals(
			'Middle',
			$contact->middle_name()
		);

		$this->assertEquals(
			'Last',
			$contact->last_name()
		);

		$this->assertEquals(
			'First Middle Last',
			$contact->name()
		);

	}

	public function testCanBeCreatedFromValidEmailAddress() {
		$this->assertInstanceOf(
			Contact::class,
			Contact::fromEmailAddress( 'user@example.com' )
		);

		$this->assertInstanceOf(
			Contact::class,
			Contact::fromEmailAddress( 'Name <user@example.com>' )
		);
	}

	public function testCanRecognizeRecipientNames() {
		$this->assertEquals(
			'FirstName',
			Contact::fromEmailAddress( 'FirstName LastName <user@example.com>' )->first_name
		);

		$this->assertEquals(
			'LastName',
			Contact::fromEmailAddress( 'FirstName LastName <user@example.com>' )->last_name
		);

		$this->assertEquals(
			'FirstName',
			Contact::fromEmailAddress( 'FirstName <user@example.com>' )->first_name
		);

		$this->assertEquals(
			'FirstName',
			Contact::fromEmailAddress( 'FirstName<user@example.com>' )->first_name
		);

	}

	public function testCanRecognizeRecipientEmail() {

		$this->assertEquals(
			'user@example.com',
			Contact::fromEmailAddress( 'FirstName LastName <user@example.com>' )->email
		);

		$this->assertEquals(
			'user@example.com',
			Contact::fromEmailAddress( '<user@example.com>' )->email
		);

		$this->assertEquals(
			'user@example.com',
			Contact::fromEmailAddress( 'user@example.com' )->email
		);

	}

	public function testNormalize() {

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contact = new Contact( $attributes );
		
		$this->assertEquals(
			$contact,
			Contact::normalize( $contact )
		);
		
		$this->assertEquals(
			$contact,
			Contact::normalize( $contact->emailAddress() )
		);		

		$this->assertEquals(
			$contact,
			Contact::normalize( $attributes )
		);	
		
		$this->assertNull(
			Contact::normalize( (object)$attributes )
		);			

	}	


}
