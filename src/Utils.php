<?php

namespace Svbk\WP\Email;

class Utils {

	public static function upperKeys( $pairs ) {

		$uc_pairs = array();

		if ( ! function_exists( 'mb_strtoupper' ) ) {
			foreach ( $pairs as $key => $value ) {
				$uc_pairs[ strtoupper( $key ) ] = $value;
			}
		} else {
			foreach ( $pairs as $key => $value ) {
				$uc_pairs[ mb_strtoupper( $key ) ] = $value;
			}
		}

		return $uc_pairs;
	}

	public static function parse_recipients( $recipients ) {

		if ( ! is_array( $recipients ) ) {
			$recipients = preg_split( '/[,;]/', $recipients );
		}

		return array_filter( array_map( array( Contact::class, 'fromEmailAddress' ), $recipients ) );
	}

	public static function extract( $objects, $property, $key = null ) {
		$result = array();

		// if ( version_compare(PHP_VERSION, '7.0.0') >= 0 ) {
		// return array_column($objects, $property, $key);
		// }
		foreach ( $objects as $object ) {
			if ( $key && property_exists( $object, $key ) && ! empty( $object->{$key} ) ) {
				$result[ $object->{$key} ] = $object->{$property};
			} else {
				$result[] = $object->{$property};
			}
		}

		return $result;
	}

}
