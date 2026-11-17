<?php
/**
 * Tweak: turn XML-RPC off.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Disables every XML-RPC method and the pingback header.
 */
final class XmlrpcOff extends Tweak {

	public function id(): string {
		return 'xmlrpc_off';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Disable XML-RPC completely', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Turns off every xmlrpc.php method (the xmlrpc_enabled filter alone only blocks methods that need a login) and removes the X-Pingback header. Blocks brute-force and pingback abuse through XML-RPC. Breaks tools that still use XML-RPC, such as Jetpack connections, very old mobile apps and some desktop editors.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function apply(): void {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', array( $this, 'methods' ), 999 );
		add_filter( 'wp_headers', array( $this, 'headers' ) );
		add_filter( 'bloginfo_url', array( $this, 'pingback_url' ), 10, 2 );
	}

	/**
	 * No methods at all.
	 *
	 * @param mixed $methods Methods.
	 * @return array<string,mixed>
	 */
	public function methods( $methods ): array {
		return array();
	}

	/**
	 * Remove X-Pingback.
	 *
	 * @param mixed $headers Headers.
	 * @return mixed
	 */
	public function headers( $headers ) {
		if ( is_array( $headers ) ) {
			unset( $headers['X-Pingback'] );
		}

		return $headers;
	}

	/**
	 * Empty pingback URL for themes that print it.
	 *
	 * @param mixed  $url  URL.
	 * @param string $show Requested field.
	 * @return mixed
	 */
	public function pingback_url( $url, $show = '' ) {
		return 'pingback_url' === $show ? '' : $url;
	}
}
