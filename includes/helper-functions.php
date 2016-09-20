<?php
/**
 * Utility helper functions
 */

/**
 * sanitize_html_class doesn't handle spaces (multiple classes). We remedy that.
 * @uses sanitize_html_class
 * @param  string|array      $classes Text or arrray of classes to sanitize
 * @return string            Sanitized CSS string
 */
function sswcmc_sanitize_html_class( $classes ) {

	if( is_string( $classes ) ) {
		$classes = explode(' ', $classes );
	}

	// If someone passes something not string or array, we get outta here.
	if( !is_array( $classes ) ) { return $classes; }

	$classes = array_map( 'sanitize_html_class' , $classes );

	return implode( ' ', $classes );
}