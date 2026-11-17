<?php
/**
 * Tweak: hide the WordPress version.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the generator tag and the WordPress version from asset URLs.
 */
final class HideVersion extends Tweak {

	public function id(): string {
		return 'hide_wp_version';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Hide the WordPress version', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the generator meta tag from the page head and feeds, and replaces ver= values equal to the WordPress version in script and style URLs with a hash, so the version is not shown but caches are still refreshed after an update.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'style_loader_src', array( $this, 'asset_src' ), 15 );
		add_filter( 'script_loader_src', array( $this, 'asset_src' ), 15 );
	}

	/**
	 * Replace a ver= that equals the WordPress version.
	 *
	 * @param mixed $src Asset URL.
	 * @return mixed
	 */
	public function asset_src( $src ) {
		if ( ! is_string( $src ) || false === strpos( $src, 'ver=' ) ) {
			return $src;
		}
		$version = (string) get_bloginfo( 'version' );
		$query   = (string) wp_parse_url( $src, PHP_URL_QUERY );
		parse_str( $query, $args );
		if ( '' === $version || ! isset( $args['ver'] ) || $version !== $args['ver'] ) {
			return $src;
		}

		return add_query_arg( 'ver', substr( md5( wp_salt( 'nonce' ) . $version ), 0, 8 ), $src );
	}
}
