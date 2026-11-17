<?php
/**
 * Tweak: send basic security headers.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Adds conservative security headers to front-end responses.
 */
final class SecurityHeaders extends Tweak {

	/**
	 * Header name => value.
	 */
	const HEADERS = array(
		'X-Content-Type-Options' => 'nosniff',
		'X-Frame-Options'        => 'SAMEORIGIN',
		'Referrer-Policy'        => 'strict-origin-when-cross-origin',
	);

	public function id(): string {
		return 'security_headers';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Send basic security headers', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Adds X-Content-Type-Options: nosniff, X-Frame-Options: SAMEORIGIN and Referrer-Policy: strict-origin-when-cross-origin to front-end pages, unless another plugin already set them. Other sites can no longer show your pages in a frame.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function apply(): void {
		add_filter( 'wp_headers', array( $this, 'headers' ), 20 );
	}

	/**
	 * Add missing headers.
	 *
	 * @param mixed $headers Headers.
	 * @return mixed
	 */
	public function headers( $headers ) {
		if ( ! is_array( $headers ) ) {
			return $headers;
		}
		$present = array_change_key_case( $headers, CASE_LOWER );
		foreach ( self::HEADERS as $name => $value ) {
			if ( ! isset( $present[ strtolower( $name ) ] ) ) {
				$headers[ $name ] = $value;
			}
		}

		return $headers;
	}
}
