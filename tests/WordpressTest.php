<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Svbk\WP\Email\Message;
use Svbk\WP\Email\Contact;
use Svbk\WP\Email\Wordpress;

final class WordpressTest extends TestCase {


	protected $filters = [];
	protected $wp;

	protected function setUp() : void {
		$_SERVER['SERVER_NAME'] = 'www.example.com';

		$this->wp = new Wordpress();
	}

	public function testCanCreateMessage() {

		$to = 'First Middle Last <dest@example.com>';
		$subject = 'Test Subject';
		$body = 'Message Body';

		$message = $this->wp->message( $to, $subject, $body );

		$this->assertInstanceOf(
			Message::class,
			$message
		);

	}

	public function testCanCreateBasicMessage() {

		$to = 'First Middle Last <dest@example.com>';
		$subject = 'Test Subject';
		$body = 'Message Body';

		$message = $this->wp->message( $to, $subject, $body );

		$this->assertInstanceOf(
			Message::class,
			$message
		);

		$this->assertIsArray(
			$message->to
		);

		$this->assertContainsOnly(
			Contact::class,
			$message->to
		);

		$this->assertIsArray(
			$message->cc
		);

		$this->assertEmpty(
			$message->cc
		);

		$this->assertEquals(
			'WordPress <wordpress@example.com>',
			$message->from->emailAddress()
		);

		$this->assertEquals(
			$subject,
			$message->subject
		);

		$this->assertEquals(
			$body,
			$message->text_body
		);

		$this->assertEquals(
			'First',
			$message->to[0]->first_name()
		);

	}

	public function testCanSetMessageMultipleRecipients() {

		$to = 'First Middle Last <dest@example.com>,First2 Middle2 Last2 <dest2@example.com>';
		$subject = 'Test Subject';
		$body = 'Message Body';

		$message = $this->wp->message( $to, $subject, $body );

		$this->assertContainsOnly(
			Contact::class,
			$message->to
		);

		$this->assertIsArray(
			$message->to
		);

		$this->assertNotEmpty(
			$message->to
		);

		$this->assertEquals(
			'First',
			$message->to[0]->first_name()
		);

		$this->assertEquals(
			'First2',
			$message->to[1]->first_name()
		);

	}

	public function testCanSetMessageSubject() {
		$to = 'First Middle Last <dest@example.com>';
		$subject = 'Test Subject';
		$body = 'Message Body';

		$message = $this->wp->message( $to, $subject, $body );

		$this->assertEquals(
			$subject,
			$message->subject
		);

	}

	public function testCanSetMessageRecipientsFromHeaders() {
		$to = 'First Middle Last <dest@example.com>';
		$subject = 'Test Subject';
		$body = 'Message Body';

		$headers = array();
		$headers[] = 'Cc: John Q Codex <jqc@wordpress.org>';
		$headers[] = 'Cc: iluvwp@wordpress.org'; // note you can just use a simple email address
		$headers[] = 'Bcc: John Q Codex <jqc@wordpress.org>';

		$message = $this->wp->message( $to, $subject, $body, $headers );

		$this->assertCount( 2, $message->cc );
		$this->assertContainsOnly( Contact::class, $message->bcc );

		$this->assertEquals(
			'John',
			$message->cc[0]->first_name()
		);

		$this->assertEquals(
			'iluvwp@wordpress.org',
			$message->cc[1]->email
		);

		$this->assertCount( 1, $message->bcc );
		$this->assertContainsOnly( Contact::class, $message->bcc );

		$this->assertEquals(
			'John',
			$message->bcc[0]->first_name()
		);

		$this->assertEquals(
			'jqc@wordpress.org',
			$message->bcc[0]->email
		);

		$this->assertEmpty( $message->reply_to );

		$headers[] = 'Reply-To: John Q Codex <jqc@wordpress.org>';
		$message = $this->wp->message( $to, $subject, $body, $headers );

		$this->assertIsNotArray( $message->reply_to );
		$this->assertInstanceOf( Contact::class, $message->reply_to );

		$this->assertEquals(
			'John',
			$message->reply_to->first_name()
		);

		$this->assertEquals(
			'jqc@wordpress.org',
			$message->reply_to->email
		);

	}

	public function testCanSetMessageHtmlContentType() {
		$to = 'First Middle Last <dest@example.com>';
		$subject = 'Test Subject';
		$body = 'Message Body';

		$headers = array();
		$headers[] = 'Content-Type: text/html; charset=utf-8';

		$message = $this->wp->message( $to, $subject, $body, $headers );

		$this->assertEquals(
			'',
			$message->text_body
		);

		$this->assertEquals(
			$body,
			$message->html_body
		);

		$resultHeaders = $message->getHeaders( true );

		$this->assertEquals(
			'text/html',
			$resultHeaders['Content-Type']
		);

		$this->assertEquals(
			'utf-8',
			$resultHeaders['charset']
		);

	}

	public function testCanSetMessageCustomHeaders() {
		$to = 'First Middle Last <dest@example.com>';
		$subject = 'Test Subject';
		$body = 'Message Body';

		$headers = array();
		$headers[] = 'CustomHeader: customvalue';

		$message = $this->wp->message( $to, $subject, $body, $headers );

		$resultHeaders = $message->getHeaders( true );

		$this->assertEquals(
			'customvalue',
			$resultHeaders['CustomHeader']
		);

	}

	public function testCanSetMessageFrom() {
		$to = 'First Middle Last <dest@example.com>';
		$subject = 'Test Subject';
		$body = 'Message Body';
		$headers = array();
		$from = 'John Q Codex <jqc@wordpress.org>';
		$headers[] = 'From: ' . $from;

		$message = $this->wp->message( $to, $subject, $body, $headers );

		$resultHeaders = $message->getHeaders( true );

		$this->assertEquals(
			$from,
			$message->from->emailAddress()
		);

	}


}
