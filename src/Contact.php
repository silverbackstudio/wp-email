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
	
	public function __construct( $properties = array() ){
		
		foreach ( $properties as $property => $value ) {
			if ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			}
		}
		
	}
	
	public function first_name( $raw = false ){
	
		if ( $raw || ! $this->first_name ) {
			return $this->first_name;
		}
		
		$names = self::splitNames( $this->first_name ); 
		
		if( !$this->last_name && $names['last_name'] ) {
			return $names['first_name'];
		}
		
		return $this->first_name;
	}

	public function middle_name( $raw = false ){
	
		if ( $raw || $this->middle_name ) {
			return $this->middle_name;
		}
		
		$names = self::splitNames( $this->first_name ); 
		
		if( $names['middle_name'] ) {
			return $names['middle_name'];
		}
		
		return $this->middle_name;
	}
	
	public function last_name( $raw = false ){
	
		if ( $raw || $this->last_name ) {
			return $this->last_name;
		}
		
		$names = self::splitNames( $this->first_name ); 
		
		if( $names['last_name'] ) {
			return $names['last_name'];
		}
		
		return $this->last_name;
	}	
	
	public static function splitNames( $name ) {
		
	    $parts = array();
	
	    while ( strlen( trim($name)) > 0 ) {
	        $name = trim($name);
	        $string = preg_replace('#.*\s([\w-]*)$#', '$1', $name);
	        $parts[] = $string;
	        $name = trim( preg_replace('#'.$string.'#', '', $name ) );
	    }
	
	    if (empty($parts)) {
	        return false;
	    }
	
	    $parts = array_reverse($parts);
	    $name = array();
	    $name['first_name'] = $parts[0];
	    $name['middle_name'] = (isset($parts[2])) ? $parts[1] : '';
	    $name['last_name'] = (isset($parts[2])) ? $parts[2] : ( isset($parts[1]) ? $parts[1] : '');
	
	    return $name;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function uuid(){
		return md5( $this->email );
	}
	
	public function addAttribute( $key, $value ){
		$this->attributes[ $key ] = $value;
	}
	
	public function listSubscribe( $list_id, $marketing = null ) {
		
		if( $marketing ) {
			$marketing->listSubscribe( $this, $list_id );
			return true;
		}		
		
		if ( array_search($list_id, $this->lists) === false ) {
		    $this->lists[] = $list_id;
		    return true;
		} 
		
		return false;
	}
	
	public function listUnsubscribe( $list_id, $marketing = null ) {
		
		if( $marketing ) {
			$marketing->listUnsubscribe( $this );
		}		
		
		if (($key = array_search($list_id, $this->lists)) !== false) {
		    unset($this->lists[$key]);
		    return true;
		} 
		
		return false;
	}	
	
	public function name(){
		$name = '';
		
		if( $this->first_name ) {
			$name .= $this->first_name;
		}
		
		if( $this->last_name ) {
			$name .= ' ' . $this->last_name;
		}		
		
		return $name;
	}
	
	public function emailAddress() {
		
		$name = $this->name();
		
		$address = '';
		
		if( $name ) {
			return $name . ' <' . $this->email . '>'; 
		}
		
		return $this->email; 
	}	
	
}
