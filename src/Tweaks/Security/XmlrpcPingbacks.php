<?php
/**
 * Tweak: disable XML-RPC pingback methods.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes only the pingback methods from XML-RPC.
 */
final class XmlrpcPingbacks extends Tweak {

	/**
	 * Methods that let anyone make the site send requests.
	 */
	const METHODS = array( 'pingback.ping', 'pingback.extensions.getPingbacks' );

	public function id(): string {
		return 'xmlrpc_pingbacks';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Disable XML-RPC pingback methods', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the pingback.ping and pingback.extensions.getPingbacks XML-RPC methods and the X-Pingback header, so your site cannot be used for pingback DDoS amplification. Other XML-RPC methods (Jetpack, apps) keep working. Incoming pingbacks stop.', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'xmlrpc_methods', array( $this, 'methods' ) );
		add_filter( 'wp_headers', array( $this, 'headers' ) );
	}

	/**
	 * Remove the methods.
	 *
	 * @param mixed $methods Methods.
	 * @return mixed
	 */
	public function methods( $methods ) {
		if ( is_array( $methods ) ) {
			foreach ( self::METHODS as $method ) {
				unset( $methods[ $method ] );
			}
		}

		return $methods;
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
}
