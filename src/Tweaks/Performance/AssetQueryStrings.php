<?php
/**
 * Tweak: remove version query strings from static assets.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes `ver=` from front-end script and style URLs.
 */
final class AssetQueryStrings extends Tweak {

	public function id(): string {
		return 'asset_query_strings';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove version query strings from scripts and styles', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the ver= parameter from front-end CSS and JavaScript URLs. Only useful for proxies that refuse to cache URLs with query strings. Visitors and caches may keep old files after an update until their cache expires, so this is not part of any preset.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function presets(): array {
		return array();
	}

	public function apply(): void {
		add_filter( 'style_loader_src', array( $this, 'strip' ), 20 );
		add_filter( 'script_loader_src', array( $this, 'strip' ), 20 );
	}

	/**
	 * Remove the parameter.
	 *
	 * @param mixed $src Asset URL.
	 * @return mixed
	 */
	public function strip( $src ) {
		if ( ! is_string( $src ) || is_admin() || false === strpos( $src, 'ver=' ) ) {
			return $src;
		}

		return remove_query_arg( 'ver', $src );
	}
}
