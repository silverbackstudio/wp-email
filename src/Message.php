<?php
namespace Svbk\WP\Email;


class Message {


	public $from;
	public $to;
	public $reply_to;
	public $cc;
	public $bcc;
	public $subject;
	public $html_body;
	public $text_body;
	public $attributes = array();
	public $tags = array();


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
		);
	}

}
