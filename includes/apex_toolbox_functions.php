<?php
// Forbid accessing directly
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.1 401 Unauthorized' );
	exit;
}

/**
 * Get the domain name for a specific URL - only returns the host part
 *
 * @param string $sURL URL to get the host name from
 *
 * @author Nigel Wells
 * @version 0.0.1.16.10.07
 * @return string;
 */
if ( ! function_exists( 'getDomain' ) ) {
	function getDomain( $sURL ) {
		// If no scheme then add it in
		if ( strpos( $sURL, '://' ) === false ) {
			$sURL = 'http://' . $sURL;
		}
		// Parse the URL
		$asParts = parse_url( $sURL );

		if ( ! $asParts ) {
			// replace this with a better error result
			wp_die( 'ERROR: Path corrupt for parsing.' );
		}

		return $asParts['host'];
	}
}