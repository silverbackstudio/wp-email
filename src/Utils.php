<?php

namespace Svbk\WP\Email;

class Utils {
    
	public static function upperKeys( $pairs ){
	
		$uc_pairs = array();
	
		foreach( $pairs as $key => $value ) {
			$uc_pairs[ strtoupper( $key ) ] = $value;
		}
		
		return $uc_pairs;
		
	} 
    
}