<?php
namespace Svbk\WP\Email;

class Message {

	public $from;
	public $reply_to;

	public $to  = array();
	public $cc  = array();
	public $bcc = array();

	public $subject;

	public $html_body  = '';
	public $text_body  = '';

	public $attributes = array();
	public $tags = array();

	protected $headers = array();

	public $attachments = array();

	/**
	 * Class contructor
	 *
	 * @param array $properties  The properties to set
	 *
	 * @return void
	 */
	public function __construct( $properties = array() ) {

		foreach ( $properties as $property => $value ) {
			if ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			}
		}

	}

	/**
	 * Adds a message header
	 *
	 * @param string $name    The header name
	 * @param string $content The header content
	 *
	 * @return void
	 */
	public function addHeader( $name, $content ) {
		$this->headers[] = array(
			'name' => $name,
			'content' => $content,
		);
	}

	/**
	 * Get all message headers
	 *
	 * @param string $assoc If the function shoul return the values as an associated array. Default: true
	 *
	 * @return array
	 */
	public function getHeaders( $assoc = true ) {
		if ( ! $assoc ) {
			return $this->headers;
		}

		return array_column( $this->headers, 'content', 'name' );
	}

	/**
	 * Adds a message attachment
	 *
	 * @param string $path The absolute file path
	 *
	 * @return void
	 */
	public function addAttachment( $path ) {
		$this->attachments[] = $path;
	}

	/**
	 * Get all message attachments
	 *
	 * @param string $path The absolute file path
	 *
	 * @return string[]
	 */
	public function getAttachment() {
		return $this->attachments;
	}


	/**
	 * Adds a message recipient
	 *
	 * @param \Svbk\WP\Email\Contact|string|array $contact The contact to set, automatically parses string addresses and Contact props arrays
	 * @param string                              $type    The recipient type to set (to, cc, bcc, reply_to..)
	 *
	 * @return void
	 */
	public function addRecipient( $contact, $type = 'to' ) {

		$contact = Contact::normalize( $contact );

		if ( ! $contact ) {
			return;
		}

		switch ( $type ) {
			case 'to':
				$this->to[] = $contact;
				break;
			case 'cc':
				$this->cc[] = $contact;
				break;
			case 'bcc':
				$this->bcc[] = $contact;
				break;
		}
	}


	public function setReplyTo( $contact ) {

		$contact = Contact::normalize( $contact );

		if ( ! $contact ) {
			return;
		}

		$this->reply_to = $contact;
	}


	public function setFrom( $contact ) {

		$contact = Contact::normalize( $contact );

		if ( ! $contact ) {
			return;
		}

		$this->from = $contact;
	}


	/**
	 * Adds multiple message recipients
	 *
	 * @param \Svbk\WP\Email\Contact[]|string $contact The contact to set, automatically parses string addresses and Contact props arrays
	 * @param string                          $type    The recipient type to set (to, cc, bcc, reply_to..)
	 *
	 * @return void
	 */
	public function addRecipients( $contacts, $type = 'to' ) {

		if ( is_string( $contacts ) ) {
			$contacts = Utils::parse_recipients( $contacts );
		}

		foreach ( $contacts as $contact ) {
			$this->addRecipient( $contact, $type );
		}

	}

	/**
	 * Return the message as array
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'from' => $this->from,
			'to' => $this->to,
			'reply_to' => $this->reply_to,
			'cc' => $this->cc,
			'bcc' => $this->bcc,
			'subject' => $this->subject,
			'html_body' => $this->html_body,
			'text_body' => $this->text_body,
			'attributes' => $this->attributes,
			'tags' => $this->tags,
			'headers' => $this->headers,
			'attachments' => $this->attachments,
		);
	}

}
