<?php
/**
 * Tweak: no pingbacks to your own site.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes links to this site from the list of URLs to ping.
 */
final class SelfPingbacks extends Tweak {

	public function id(): string {
		return 'self_pingbacks';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Disable self pingbacks', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Stops WordPress from sending pingbacks to your own site when a post links to another post, which creates pointless pingback comments and requests.', 'wp-debloat' );
	}

	public function apply(): void {
		add_action( 'pre_ping', array( $this, 'filter_links' ) );
	}

	/**
	 * Drop internal links. Called by reference through do_action_ref_array().
	 *
	 * @param string[] $links Links to ping.
	 */
	public function filter_links( &$links ): void {
		if ( ! is_array( $links ) ) {
			return;
		}
		$home  = untrailingslashit( (string) home_url() );
		$links = array_values(
			array_filter(
				$links,
				static function ( $link ) use ( $home ) {
					$link = (string) $link;

					return ! ( $link === $home || 0 === strpos( $link, $home . '/' ) || 0 === strpos( $link, $home . '?' ) );
				}
			)
		);
	}
}
