<?php
// Polyfills for PHPePub running on PHP 8+ (each was removed in PHP 8)
// Define the namespaced each() function(s) expected by PHPePub if missing.
namespace PHPePub\Core {
	if ( ! function_exists( __NAMESPACE__ . '\\each' ) ) {
		/**
		 * PHP 7 each() equivalent for PHP 8+
		 * Returns the current key/value pair from the array and advances the array cursor.
		 * @param array $array
		 * @return array|false
		 */
		function each( &$array ) {
			if ( ! is_array( $array ) )
				return false;
			$key = key( $array );
			if ( $key === null )
				return false;
			$value = current( $array );
			next( $array );
			return [ 
				1       => $value,
				'value' => $value,
				0       => $key,
				'key'   => $key,
			];
		}
	}
}

namespace PHPePub\Core\Structure {
	if ( ! function_exists( __NAMESPACE__ . '\\each' ) ) {
		/**
		 * PHP 7 each() equivalent for PHP 8+ in PHPePub Core\Structure namespace
		 * @param array $array
		 * @return array|false
		 */
		function each( &$array ) {
			if ( ! is_array( $array ) )
				return false;
			$key = key( $array );
			if ( $key === null )
				return false;
			$value = current( $array );
			next( $array );
			return [ 
				1       => $value,
				'value' => $value,
				0       => $key,
				'key'   => $key,
			];
		}
	}
}

namespace PHPePub\Core\Structure\OPF {
	if ( ! function_exists( __NAMESPACE__ . '\\each' ) ) {
		function each( &$array ) {
			if ( ! is_array( $array ) )
				return false;
			$key = key( $array );
			if ( $key === null )
				return false;
			$value = current( $array );
			next( $array );
			return [ 
				1       => $value,
				'value' => $value,
				0       => $key,
				'key'   => $key,
			];
		}
	}
}
