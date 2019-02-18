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

	public function testCanSetFrom() {

		$message = new Message();

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contact = new Contact( $attributes );
		
		$message->setFrom( $contact );
		$this->assertEquals(
			$contact,
			$message->from
		);
		
		$message->setFrom( $contact->emailAddress() );
		$this->assertEquals(
			$contact,
			$message->from
		);		

		$message->setFrom( $attributes );
		$this->assertEquals(
			$contact,
			$message->from
		);		

	}
	
	public function testCanSetReplyTo() {

		$message = new Message();

		$attributes = array(
			'first_name' => 'First',
			'middle_name' => 'Middle',
			'last_name' => 'Last',
			'email' => 'user@example.com',
		);

		$contact = new Contact( $attributes );
		
		$message->setReplyTo( $contact );
		$this->assertEquals(
			$contact,
			$message->reply_to
		);
		
		$message->setFrom( $contact->emailAddress() );
		$this->assertEquals(
			$contact,
			$message->reply_to
		);		

		$message->setFrom( $attributes );
		$this->assertEquals(
			$contact,
			$message->reply_to
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
	
	public function testAttributes() {

		$message = new Message();

		$attributes1 = array(
			'attr1' => 'val1',
			'attr2' => 'val2',
			'attr3' => 'val3',
		);

		$attributes2 = array(
			'attr4' => 'val4',
			'attr5' => 'val5',
			'attr6' => 'val6',	
		);

		$this->assertIsArray(
			$message->getAttributes()
		);

		$this->assertEmpty(
			$message->getAttributes()
		);
	
		$message->setAttributes( $attributes1 );
		$this->assertEquals(
			$attributes1,
			$message->getAttributes()
		);	
		
		$message->setAttributes( $attributes2 );
		$this->assertEquals(
			array(
				'attr1' => 'val1',
				'attr2' => 'val2',
				'attr3' => 'val3',
				'attr4' => 'val4',
				'attr5' => 'val5',
				'attr6' => 'val6',				
			),
			$message->getAttributes()
		);			

		$message->setAttributes( $attributes1, true );
		$this->assertEquals(
			$attributes1,
			$message->getAttributes()
		);
		
		$message->setAttribute( 'attr4', 'val4' );
		$this->assertEquals(
			array(
				'attr1' => 'val1',
				'attr2' => 'val2',
				'attr3' => 'val3',
				'attr4' => 'val4',
			),
			$message->getAttributes()
		);		

	}		
	
	public function testAttachments() {

		$message = new Message();

		$this->assertIsArray(
			$message->getAttachments()
		);

		$this->assertEmpty(
			$message->getAttachments()
		);

		$message->addAttachment( '/my/path/to/attachment1' );
		$message->addAttachment( '/my/path/to/attachment2' );
		
		$this->assertEquals(
			array(
				'/my/path/to/attachment1',
				'/my/path/to/attachment2'
			),
			$message->getAttachments()
		);	

	}	
	
	public function testHeaders() {

		$message = new Message();

		$this->assertIsArray(
			$message->getHeaders()
		);

		$this->assertEmpty(
			$message->getHeaders()
		);

		$message->addHeader( 'HeaderName', 'HeaderValue1' );
		$message->addHeader( 'HeaderName', 'HeaderValue2' );
		$message->addHeader( 'HeaderName3', 'HeaderValue3' );
		
		$this->assertEquals(
			array(
				'HeaderName' => 'HeaderValue1, HeaderValue2',
				'HeaderName3' => 'HeaderValue3',
			),
			$message->getHeaders(true)
		);	
		
		$this->assertEquals(
			array(
				['name' => 'HeaderName', 'content' => 'HeaderValue1' ],
				['name' => 'HeaderName', 'content' => 'HeaderValue2' ],
				['name' => 'HeaderName3', 'content' => 'HeaderValue3' ],
			),
			$message->getHeaders(false)
		);	

	}		
	

}
