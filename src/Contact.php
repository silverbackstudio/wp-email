<?php
namespace Svbk\WP\Email;

class Contact {

	public $id;
	public $first_name;
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
