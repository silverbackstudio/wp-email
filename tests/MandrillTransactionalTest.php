<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Svbk\WP\Email\Contact;
use Svbk\WP\Email\Message;
use Svbk\WP\Email\Transactional\Mandrill;
use Svbk\WP\Email\Transactional\Exceptions;

final class MandrillTransactionalTest extends TestCase {

	protected $mandrill;
	protected $message;
	
	protected function setUp() : void {
	
	}

	protected function setupMessage( $message ){
		
		$message->subject = 'My Subject';
		$message->html_body = 'My HTML Body';		
		$message->text_body = 'My TEXT Body';		
		
		$message->setFrom(
			new Contact(
				[
					'first_name' => 'My From',
					'email' => 'from@example.com',
				]
			)
		);
		
		$message->addRecipient(
			new Contact(
				[
					'first_name' => 'My To',
					'email' => 'to@example.com',
				]
			)
		);
		
		$message->addRecipient(
			new Contact(
				[
					'first_name' => 'My To2',
					'email' => 'to2@example.com',
				]
			)
		);	
		
		return $message;
		
	}

	public function testCanCatchInvalidApiKey() {
		
		$this->expectException( Exceptions\ApiKeyInvalid::class );				
		
		$mandrill = new Mandrill('');
	}
	
	public function testCanCatchMissingFrom() {

		$this->expectException( Exceptions\MessageMissingFrom::class );
		
		$mandrill = new Mandrill( 'api-key' );
		
		$message = new Message();
		$message = $this->setupMessage($message);
		$message->from = array();
		
		$sentSmtpEmail = $mandrill->send( $message );
		
	}	
	
	public function testCanCatchMissingTo() {

		$this->expectException( Exceptions\MessageMissingTo::class );
		
		$mandrill = new Mandrill( 'api-key' );
		
		$message = new Message();
		$message = $this->setupMessage($message);
		$message->to = array();
	
		$sentSmtpEmail = $mandrill->send( $message );
		
	}
	
	public function testCanCatchTemplateMissingTo() {

		$this->expectException( Exceptions\MessageMissingTo::class );
		
		$mandrill = new Mandrill( 'api-key' );
		
		$message = new Message();
		$message = $this->setupMessage($message);
		$message->to = array();
	
		$sentSmtpEmail = $mandrill->sendTemplate( 1, $message );
		
	}	

	public function testCanCatchMissingSubject() {

		$this->expectException( Exceptions\MessageMissingSubject::class );
		
		$mandrill = new Mandrill( 'api-key' );
		
		$message = new Message();
		$message = $this->setupMessage($message);
		$message->subject = '';
	
		$sentSmtpEmail = $mandrill->send( $message );
	}	
	
	public function testCanCatchMissingBody() {

		$mandrill = new Mandrill( 'api-key' );
		
		$message = new Message();
		$message = $this->setupMessage($message);
		
		$message->html_body = '';
	
		$sentSmtpEmail = $mandrill->prepareSend( $message );

		$message->html_body = 'My HTML Body';
		$message->text_body = '';
		
		$sentSmtpEmail = $mandrill->prepareSend( $message );

		$message->html_body = '';

		$this->expectException( Exceptions\MessageMissingBody::class );
		
		$sentSmtpEmail = $mandrill->send( $message );
		
	}		

	// public function testCanPrepareEmptyMessage() {

	// 	$mandrill = new Mandrill( 'api-key' );
	// 	$message = new Message();

	// 	$this->setupMessage($message);

	// 	$sentSmtpEmail = $mandrill->prepareSend( $message );

	// 	$this->assertNull( $sentSmtpEmail->getCc() );
	// 	$this->assertNull( $sentSmtpEmail->getBcc() );
	// 	$this->assertNull( $sentSmtpEmail->getReplyTo() );
	// 	$this->assertNull( $sentSmtpEmail->getHeaders() );
	// 	$this->assertNull( $sentSmtpEmail->getAttachment() );
	// 	$this->assertNull( $sentSmtpEmail->getParams() );
	// 	$this->assertNull( $sentSmtpEmail->getTags() );
	// 	$this->assertNull( $sentSmtpEmail->getTemplateId() );

	// }

	public function testCanPrepareMessageFrom() {

		$mandrill = new Mandrill( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$contact_from = new Contact(
			[
				'first_name' => 'My From',
				'email' => 'from@example.com',
			]
		);
		$message->setFrom( $contact_from );

		$params = $mandrill->prepareSend( $message );
		$this->assertEquals( 'from@example.com', $params['from_email'] );
	}

	public function testCanPrepareMessageReplyTo() {

		$mandrill = new Mandrill( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$contact_rt = new Contact(
			[
				'first_name' => 'My ReplyTo',
				'email' => 'reply-to@example.com',
			]
		);
		$message->setReplyTo( $contact_rt );

		$params = $mandrill->prepareSend( $message );
		$this->assertArrayHasKey('Reply-To', $params['headers'] );
		$this->assertEquals( $contact_rt->emailAddress(), $params['headers']['Reply-To'] );
	}

	public function testCanPrepareMessageRecipients() {

		$mandrill = new Mandrill( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$contact_cc = new Contact(
			[
				'first_name' => 'My CC',
				'email' => 'cc@example.com',
			]
		);
		$message->addRecipient( $contact_cc, 'cc' );

		$contact_bcc = new Contact(
			[
				'first_name' => 'My BCC',
				'email' => 'bcc@example.com',
			]
		);
		$message->addRecipient( $contact_bcc, 'bcc' );

		$params = $mandrill->prepareSend( $message );

		$this->assertCount( 4, $params['to'] );
		
		$this->assertEquals( 'to@example.com', $params['to'][0]['email'] );
		$this->assertEquals( 'My To', $params['to'][0]['name'] );
		$this->assertEquals( 'to', $params['to'][0]['type'] );

		$this->assertEquals( 'cc@example.com', $params['to'][2]['email'] );
		$this->assertEquals( 'My CC', $params['to'][2]['name'] );
		$this->assertEquals( 'cc', $params['to'][2]['type'] );

		$this->assertEquals( 'bcc@example.com', $params['to'][3]['email'] );
		$this->assertEquals( 'My BCC', $params['to'][3]['name'] );
		$this->assertEquals( 'bcc', $params['to'][3]['type'] );

	}

	public function testCanPrepareMessageSubject() {

		$mandrill = new Mandrill( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$message->subject = 'My Subject';

		$params = $mandrill->prepareSend( $message );

		$this->assertEquals( 'My Subject',  $params['subject'] );
	}

	// public function testCanPrepareMessageContent() {

	// 	$mandrill = new Mandrill( 'api-key' );
	// 	$message = new Message();
		
	// 	$this->setupMessage( $message );
		
	// 	$message->html_body = 'My HTML Body';
	// 	$message->text_body = 'My TEXT Body';

	// 	$params = $mandrill->prepareSend( $message );

	// 	$this->assertEquals( 'My HTML Body', $params['html'] );
	// 	$this->assertEquals( 'My TEXT Body', $params['text'] );
		
	// }

	public function testCanPrepareMessageHeaders() {

		$mandrill = new Mandrill( 'api-key' );
		$message = new Message();
		
		$this->setupMessage( $message );
		
		$message->addHeader( 'MyHeaderName1', 'My Header1 Value' );
		$message->addHeader( 'MyHeaderName2', 'My Header2 Value' );

		$params = $mandrill->prepareSend( $message );

		$this->assertEquals(
			[
				'MyHeaderName1' => 'My Header1 Value',
				'MyHeaderName2' => 'My Header2 Value',
			],
			$params['headers']
		);
		
	}

	public function testCanPrepareMessageTags() {

		$mandrill = new Mandrill( 'api-key' );
		$message = new Message();
		
		$this->setupMessage( $message );
		
		$tags = [ 'tag1', 'tag2', 'tag3' ];
		$message->tags = $tags;

		$params = $mandrill->prepareSend( $message );
		$this->assertEquals( $tags, $params['tags'] );
		
	}

	// public function testCanPrepareMessageAttributes() {

	// 	$mandrill = new Mandrill( 'api-key' );
	// 	$message = new Message();
		
	// 	$this->setupMessage( $message );
		
	// 	$attributes = [
	// 		'attr1' => 'value1',
	// 		'attr2' => 'value2',
	// 		'attr3' => 'value3',
	// 		'attr4' => 'value4',
	// 	];
	// 	$message->setAttributes( $attributes );
		
	// 	$params = $mandrill->prepareSend( $message );
	// 	$this->assertEquals( $attributes, $sentSmtpEmail->getParams() );
		
	// 	$sendEmail = $mandrill->prepareSendTemplate( $message );
	// 	$this->assertEquals( $attributes, $sendEmail->getAttributes() );		
		
	// }

	// public function testCanSendMessage() {

	// 	$mandrill = new Mandrill( self::TEST_API_KEY );
	// 	$message = new Message();

	// 	$this->setupMessage( $message );

	// 	$message->addRecipient(
	// 		new Contact(
	// 			[
	// 				'first_name' => 'My To2',
	// 				'email' => 'to2@example.com',
	// 			]
	// 		)
	// 	);

	// 	$message_id = $mandrill->send( $message );

	// 	$this->assertIsString( $message_id );

	// }
	
	// public function testCanSendMessageTemplate() {

	// 	$mandrill = new Mandrill( self::TEST_API_KEY );
	// 	$message = new Message();

	// 	$message->addRecipient(
	// 		new Contact(
	// 			[
	// 				'first_name' => 'My Template To',
	// 				'email' => 'to-template@example.com',
	// 			]
	// 		)
	// 	);
		
	// 	$message->attributes = array(
	// 		'TEST_ATTRIBUTE' => 'John',
	// 		'INPUT_FNAME' => 'Doe'
	// 	);

	// 	$message_id = $mandrill->sendTemplate( 32, $message );

	// 	$this->assertIsString( $message_id );
	// }

	// public function testCanGetTemplates() {

	// 	$mandrill = new Mandrill( self::TEST_API_KEY );

	// 	$templates = $mandrill->getTemplates();

	// 	$this->assertIsArray( $templates );

	// }	

}
