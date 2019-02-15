<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Svbk\WP\Email\Message;
use Svbk\WP\Email\Contact;

final class MessageTest extends TestCase {


	// public function testCanSetMessageProperties() {
		// $attributes = array(
		// 'first_name' => 'First',
		// 'middle_name' => 'Middle',
		// 'last_name' => 'Last',
		// 'email' => 'user@example.com',
		// );
		// $contact = new Message($attributes);
		// $this->assertEquals(
		// 'First Middle Last <user@example.com>' ,
		// $contact->emailAddress()
		// );
	// }
	public function testCanCastRecipients() {

		$message = new Message();

		$message->addRecipient( 'FirstName LastName <user@example.com>' );

		$this->assertContainsOnly(
			Contact::class,
			$message->to
		);

		$this->assertIsArray(
			$message->to
		);

		$this->assertEquals(
			'FirstName LastName <user@example.com>' ,
			$message->to[0]->emailAddress()
		);

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$message->addRecipient( $attributes );

		$this->assertEquals(
			'First Middle Last <user@example.com>' ,
			$message->to[1]->emailAddress()
		);

		$contact = new Contact( $attributes );

		$message->addRecipient( $contact );

		$this->assertEquals(
			'First Middle Last <user@example.com>' ,
			$message->to[2]->emailAddress()
		);

	}

	public function testCanSetRecipients() {

		$message = new Message();

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contact = new Contact( $attributes );

		// Check Reply To
		$message->setFrom( $contact->emailAddress() );

		$this->assertEquals(
			$contact,
			$message->from
		);

		$message->addRecipient( $contact );

		$this->assertEquals(
			$contact,
			$message->to[0]
		);

		$this->assertNotEquals(
			$message->to,
			$message->cc
		);

		// Check CC
		$message->addRecipient( $contact, 'cc' );

		$this->assertEquals(
			$contact,
			$message->cc[0]
		);

		// Check BCC
		$message->addRecipient( $contact, 'bcc' );

		$this->assertEquals(
			$contact,
			$message->bcc[0]
		);

		// Check Reply To
		$message->setReplyTo( $contact );

		$this->assertEquals(
			$contact,
			$message->reply_to
		);

		// Check Reply To From String
		$message->setReplyTo( $contact->emailAddress() );

		$this->assertEquals(
			$contact,
			$message->reply_to
		);

	}

	public function testCanAddBatchRecipients() {

		$message = new Message();

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contact = new Contact( $attributes );

		$message->addRecipients( [ $contact, $contact, $contact ] );
		$this->assertCount( 3, $message->to );

		$message->addRecipients( [ $contact, $contact, $contact ], 'cc' );
		$this->assertCount( 3, $message->cc );

		$message->addRecipients( 'First Middle Last <dest@example.com>,First2 Middle2 Last2 <dest2@example.com>', 'cc' );
		$this->assertCount( 5, $message->cc );
		$this->assertContainsOnly( Contact::class, $message->cc );

		$this->assertEquals(
			'First2 Middle2 Last2 <dest2@example.com>' ,
			$message->cc[4]->emailAddress()
		);

	}


}
