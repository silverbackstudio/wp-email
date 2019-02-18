<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Svbk\WP\Email\Contact;
use Svbk\WP\Email\Message;
use Svbk\WP\Email\Transactional\SendInBlue;
use Svbk\WP\Email\Transactional\Exceptions;
use SendinBlue\Client as SendInBlue_Client;

final class SendinblueTransactionalTest extends TestCase {

	protected $sendinblue;
	protected $message;

	const TEST_API_KEY = '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';

	protected function setUp() : void {

	}

	protected function setupMessage( $message ) {

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
		$sendinblue = new SendInBlue( 'foo-api-key', new SendInBlue_Client\Configuration() );

		$this->expectException( Exceptions\ApiKeyInvalid::class );

		$sendinblue = new SendInBlue( '', new SendInBlue_Client\Configuration() );
	}

	public function testCanCatchMissingFrom() {

		$this->expectException( Exceptions\MessageMissingFrom::class );

		$sendinblue = new SendInBlue( 'api-key' );

		$message = new Message();
		$message = $this->setupMessage( $message );
		$message->from = array();

		$sentSmtpEmail = $sendinblue->send( $message );

	}

	public function testCanCatchMissingTo() {

		$this->expectException( Exceptions\MessageMissingTo::class );

		$sendinblue = new SendInBlue( 'api-key' );

		$message = new Message();
		$message = $this->setupMessage( $message );
		$message->to = array();

		$sentSmtpEmail = $sendinblue->send( $message );

	}

	public function testCanCatchTemplateMissingTo() {

		$this->expectException( Exceptions\MessageMissingTo::class );

		$sendinblue = new SendInBlue( 'api-key' );

		$message = new Message();
		$message = $this->setupMessage( $message );
		$message->to = array();

		$sentSmtpEmail = $sendinblue->sendTemplate( 1, $message );

	}

	public function testCanCatchMissingSubject() {

		$this->expectException( Exceptions\MessageMissingSubject::class );

		$sendinblue = new SendInBlue( 'api-key' );

		$message = new Message();
		$message = $this->setupMessage( $message );
		$message->subject = '';

		$sentSmtpEmail = $sendinblue->send( $message );

	}

	public function testCanCatchMissingBody() {

		$sendinblue = new SendInBlue( 'api-key' );

		$message = new Message();
		$message = $this->setupMessage( $message );

		$message->html_body = '';

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$message->html_body = 'My HTML Body';
		$message->text_body = '';

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$message->html_body = '';

		$this->expectException( Exceptions\MessageMissingBody::class );

		$sendinblue->send( $message );

	}

	public function testCanPrepareEmptyMessage() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$this->assertNull( $sentSmtpEmail->getCc() );
		$this->assertNull( $sentSmtpEmail->getBcc() );
		$this->assertNull( $sentSmtpEmail->getReplyTo() );
		$this->assertNull( $sentSmtpEmail->getHeaders() );
		$this->assertNull( $sentSmtpEmail->getAttachment() );
		$this->assertNull( $sentSmtpEmail->getParams() );
		$this->assertNull( $sentSmtpEmail->getTags() );
		$this->assertNull( $sentSmtpEmail->getTemplateId() );

	}

	public function testCanPrepareEmptyTemplateMessage() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$message->html_body = '';
		$message->text_body = '';
		$message->subject = '';

		$sentSmtpEmail = $sendinblue->prepareSendTemplate( $message );

		$this->assertNull( $sentSmtpEmail->getEmailCc() );
		$this->assertNull( $sentSmtpEmail->getEmailBcc() );
		$this->assertNull( $sentSmtpEmail->getReplyTo() );
		$this->assertNull( $sentSmtpEmail->getHeaders() );
		$this->assertNull( $sentSmtpEmail->getAttachment() );
		$this->assertNull( $sentSmtpEmail->getHeaders() );
		$this->assertNull( $sentSmtpEmail->getAttributes() );
		$this->assertNull( $sentSmtpEmail->getTags() );

	}

	public function testCanPrepareMessageFrom() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$contact_from = new Contact(
			[
				'first_name' => 'My From',
				'email' => 'from@example.com',
			]
		);
		$message->setFrom( $contact_from );

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$from = $sentSmtpEmail->getSender();
		$this->assertIsNotArray( $from );
		$this->assertInstanceOf( SendinBlue_Client\Model\SendSmtpEmailSender::class, $from );
		$this->assertEquals( 'from@example.com', $from->getEmail() );
		$this->assertEquals( 'My From', $from->getName() );
	}

	public function testCanPrepareMessageReplyTo() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$contact_rt = new Contact(
			[
				'first_name' => 'My ReplyTo',
				'email' => 'reply-to@example.com',
			]
		);
		$message->setReplyTo( $contact_rt );

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$reply_to = $sentSmtpEmail->getReplyTo();
		$this->assertIsNotArray( $reply_to );
		$this->assertInstanceOf( SendinBlue_Client\Model\SendSmtpEmailReplyTo::class, $reply_to );
		$this->assertEquals( 'reply-to@example.com', $reply_to->getEmail() );
		$this->assertEquals( 'My ReplyTo', $reply_to->getName() );

		$sendEmail = $sendinblue->prepareSendTemplate( $message );

		$reply_to = $sendEmail->getReplyTo();
		$this->assertIsNotArray( $reply_to );
		$this->assertEquals( 'reply-to@example.com', $reply_to );
	}

	public function testCanPrepareMessageRecipients() {

		$sendinblue = new SendInBlue( 'api-key' );
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

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$to = $sentSmtpEmail->getTo();
		$this->assertCount( 2, $to );
		$this->assertContainsOnly( SendinBlue_Client\Model\SendSmtpEmailTo::class, $to );
		$this->assertEquals( 'to@example.com', $to[0]->getEmail() );
		$this->assertEquals( 'My To', $to[0]->getName() );

		$cc = $sentSmtpEmail->getCc();
		$this->assertCount( 1, $cc );
		$this->assertContainsOnly( SendinBlue_Client\Model\SendSmtpEmailCc::class, $cc );
		$this->assertEquals( 'cc@example.com', $cc[0]->getEmail() );
		$this->assertEquals( 'My CC', $cc[0]->getName() );

		$bcc = $sentSmtpEmail->getBcc();
		$this->assertCount( 1, $bcc );
		$this->assertContainsOnly( SendinBlue_Client\Model\SendSmtpEmailBcc::class, $bcc );
		$this->assertEquals( 'bcc@example.com', $bcc[0]->getEmail() );
		$this->assertEquals( 'My BCC', $bcc[0]->getName() );

		$sendEmail = $sendinblue->prepareSendTemplate( $message );

		$to = $sendEmail->getEmailTo();
		$this->assertCount( 2, $to );
		$this->assertEquals( 'to@example.com', $to[0] );

		$cc = $sendEmail->getEmailCc();
		$this->assertCount( 1, $cc );
		$this->assertEquals( 'cc@example.com', $cc[0] );

		$bcc = $sendEmail->getEmailBcc();
		$this->assertCount( 1, $bcc );
		$this->assertEquals( 'bcc@example.com', $bcc[0] );
	}

	public function testCanPrepareMessageSubject() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$message->subject = 'My Subject';

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$subject = $sentSmtpEmail->getSubject();
		$this->assertEquals( 'My Subject', $subject );
	}

	public function testCanPrepareMessageContent() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$message->html_body = 'My HTML Body';
		$message->text_body = 'My TEXT Body';

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$this->assertEquals( 'My HTML Body', $sentSmtpEmail->getHtmlContent() );
		$this->assertEquals( 'My TEXT Body', $sentSmtpEmail->getTextContent() );

	}

	public function testCanPrepareMessageHeaders() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$message->addHeader( 'MyHeaderName1', 'My Header1 Value' );
		$message->addHeader( 'MyHeaderName2', 'My Header2 Value' );

		$sentSmtpEmail = $sendinblue->prepareSend( $message );

		$this->assertEquals(
			[
				'MyHeaderName1' => 'My Header1 Value',
				'MyHeaderName2' => 'My Header2 Value',
			],
			$sentSmtpEmail->getHeaders()
		);

		$sendEmail = $sendinblue->prepareSendTemplate( $message );
		$this->assertEquals(
			[
				'MyHeaderName1' => 'My Header1 Value',
				'MyHeaderName2' => 'My Header2 Value',
			],
			$sendEmail->getHeaders()
		);
	}

	public function testCanPrepareMessageTags() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$tags = [ 'tag1', 'tag2', 'tag3' ];
		$message->tags = $tags;

		$sentSmtpEmail = $sendinblue->prepareSend( $message );
		$this->assertEquals( $tags, $sentSmtpEmail->getTags() );

		$sendEmail = $sendinblue->prepareSendTemplate( $message );
		$this->assertEquals( $tags, $sendEmail->getTags() );
	}

	public function testCanPrepareMessageAttributes() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$attributes = [
			'attr1' => 'value1',
			'attr2' => 'value2',
			'attr3' => 'value3',
			'attr4' => 'value4',
		];
		$message->setAttributes( $attributes );

		$sentSmtpEmail = $sendinblue->prepareSend( $message );
		$this->assertEquals( $attributes, $sentSmtpEmail->getParams() );

		$sendEmail = $sendinblue->prepareSendTemplate( $message );
		$this->assertEquals( $attributes, $sendEmail->getAttributes() );

	}

	public function testCanPrepareMessageTemplateId() {

		$sendinblue = new SendInBlue( 'api-key' );
		$message = new Message();

		$this->setupMessage( $message );

		$sentSmtpEmail = $sendinblue->prepareSend( $message, 999 );

		$this->assertEquals( 999, $sentSmtpEmail->getTemplateId() );
	}

	// public function testCanSendMessage() {
	// $sendinblue = new SendInBlue( self::TEST_API_KEY );
	// $message = new Message();
	// $this->setupMessage( $message );
	// $message->addRecipient(
	// new Contact(
	// [
	// 'first_name' => 'My To2',
	// 'email' => 'to2@example.com',
	// ]
	// )
	// );
	// $message_id = $sendinblue->send( $message );
	// $this->assertIsString( $message_id );
	// }
	// public function testCanSendMessageTemplate() {
	// $sendinblue = new SendInBlue( self::TEST_API_KEY );
	// $message = new Message();
	// $message->addRecipient(
	// new Contact(
	// [
	// 'first_name' => 'My Template To',
	// 'email' => 'to-template@example.com',
	// ]
	// )
	// );
	// $message->attributes = array(
	// 'TEST_ATTRIBUTE' => 'John',
	// 'INPUT_FNAME' => 'Doe'
	// );
	// $message_id = $sendinblue->sendTemplate( 32, $message );
	// $this->assertIsString( $message_id );
	// }
	public function testCanGetTemplates() {

		$sendinblue = new SendInBlue( self::TEST_API_KEY );

		$templates = $sendinblue->getTemplates();

		$this->assertIsArray( $templates );

	}

}
