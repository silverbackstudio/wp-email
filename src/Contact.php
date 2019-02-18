<?php
namespace Svbk\WP\Email;

class Contact {

	public $id;
	public $first_name;
	public $middle_name;
	public $last_name;
	public $email;
	public $phone;

	public $attributes = array();

	public $lists;

	public function __construct( $properties = array() ) {

		foreach ( $properties as $property => $value ) {
			if ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			}
		}

	}

	public static function normalize( $contact ) {

		if ( is_string( $contact ) ) {
			$contact = self::fromEmailAddress( $contact );
		}

		if ( is_array( $contact ) ) {
			$contact = new self( $contact );
		}

		if ( ! is_a( $contact, static::class ) ) {
			return null;
		}

		return $contact;
	}

	/**
	 * Parses a RFC2822 recipient in a @\Svbk\WP\Email\Contact object
	 *
	 * @param string $address RFC2822 recipient address string like 'Name <address@example.com>'
	 *
	 * @return \Svbk\WP\Email\Contact
	 */
	public static function fromEmailAddress( $address ) {

		$contact = new self();

		$bracket_pos = strpos( $address, '<' );

		if ( $bracket_pos !== false ) {
			// Text before the bracketed email is the "From" name.
			if ( $bracket_pos > 0 ) {
				$from_name = substr( $address, 0, $bracket_pos );
				$from_name = str_replace( '"', '', $from_name );
				$from_name = trim( $from_name );
				$contact->setName( $from_name );
			}

			$address = substr( $address, $bracket_pos + 1 );
			$address = str_replace( '>', '', $address );
		}

		// Avoid setting an invalid email
		if ( ! self::validateEmail( $address ) ) {
			return false;
		}

		$contact->email = trim( $address );

		return $contact;
	}

	public function first_name( $raw = false ) {

		if ( $raw || ! $this->first_name ) {
			return $this->first_name;
		}

		$names = self::splitNames( $this->first_name );

		if ( ! $this->last_name && $names['last_name'] ) {
			return $names['first_name'];
		}

		return $this->first_name;
	}

	public function middle_name( $raw = false ) {

		if ( $raw || $this->middle_name ) {
			return $this->middle_name;
		}

		$names = self::splitNames( $this->first_name );

		if ( $names['middle_name'] ) {
			return $names['middle_name'];
		}

		return $this->middle_name;
	}

	public function last_name( $raw = false ) {

		if ( $raw || $this->last_name ) {
			return $this->last_name;
		}

		$names = self::splitNames( $this->first_name );

		if ( $names['last_name'] ) {
			return $names['last_name'];
		}

		return $this->last_name;
	}

	public static function splitNames( $name ) {

		$parts = array();

		$name_lenght = strlen( trim( $name ) );

		while ( $name_lenght > 0 ) {
			$name = trim( $name );
			$string = preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
			$parts[] = $string;
			$name = trim( preg_replace( '#' . $string . '#', '', $name ) );
			$name_lenght = strlen( trim( $name ) );
		}

		if ( empty( $parts ) ) {
			return false;
		}

		$parts = array_reverse( $parts );
		$name = array();
		$name['first_name'] = $parts[0];
		$name['middle_name'] = (isset( $parts[2] )) ? $parts[1] : '';
		$name['last_name'] = (isset( $parts[2] )) ? $parts[2] : ( isset( $parts[1] ) ? $parts[1] : '');

		return $name;
	}

	public function getId() {
		return $this->id;
	}

	public function uuid() {
		return md5( $this->email );
	}

	public function addAttribute( $key, $value ) {
		$this->setAttribute($key, $value);
	}
	
	/**
	 * Set a message attribute
	 *
	 * @param string $name  The attribute name
	 * @param string $value The attribute balue
	 *
	 * @return void
	 */
	public function setAttribute( $name, $value ) {
		$this->attributes[$name] = $value;
	}

	/**
	 * Set multiple message attributes
	 *
	 * @param array $attributes The attribute key=>values to set
	 * @param boolean $merge    Merges attributes to existing ones, otherwhise replaces the whole array.
	 *
	 * @return void
	 */
	public function setAttributes( $attributes, $merge = true ) {
		$this->attributes = $merge ? array_replace($this->attributes, $attributes ) : $attributes;
	}	

	/**
	 * Get all message attributes
	 *
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}	

	public function listSubscribe( $list_id, $marketing = null ) {

		if ( $marketing ) {
			$marketing->listSubscribe( $this, $list_id );
			return true;
		}

		if ( array_search( $list_id, $this->lists ) === false ) {
			$this->lists[] = $list_id;
			return true;
		}

		return false;
	}

	public function listUnsubscribe( $list_id, $marketing = null ) {

		if ( $marketing ) {
			$marketing->listUnsubscribe( $this );
		}

		if ( ($key = array_search( $list_id, $this->lists )) !== false ) {
			unset( $this->lists[ $key ] );
			return true;
		}

		return false;
	}

	public function name() {
		$name = '';

		if ( $this->first_name ) {
			$name .= $this->first_name;
		}

		if ( $this->middle_name ) {
			$name .= ' ' . $this->middle_name;
		}

		if ( $this->last_name ) {
			$name .= ' ' . $this->last_name;
		}

		return $name;
	}

	public function setName( $name ) {
		$names = $this->splitNames( $name );

		if ( $names['first_name'] ) {
			$this->first_name = $names['first_name'];
		}

		if ( $names['middle_name'] ) {
			$this->middle_name = $names['middle_name'];
		}

		if ( $names['last_name'] ) {
			$this->last_name = $names['last_name'];
		}

	}

	public static function validateEmail( $email ) {
		return boolval( filter_var( $email, FILTER_VALIDATE_EMAIL ) );
	}

	public function emailAddress() {

		if ( ! $this->email ) {
			return '';
		}

		$name = $this->name();

		if ( $name ) {
			return $name . ' <' . $this->email . '>';
		}

		return $this->email;
	}

}
