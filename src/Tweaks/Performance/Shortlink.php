<?php
/**
 * Tweak: remove the shortlink tag and header.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: remove the shortlink tag and header.
 */
final class Shortlink extends Tweak {

	public function id(): string {
		return 'shortlink';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove the shortlink', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the ?p= shortlink from the page head and the Link HTTP header. Search engines use the canonical URL instead.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}
}
