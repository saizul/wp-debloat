<?php
/**
 * Tweak: no Dashicons for logged-out visitors.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Dequeues the Dashicons font for visitors who are not logged in.
 */
final class Dashicons extends Tweak {

	public function id(): string {
		return 'dashicons_logged_out';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove Dashicons for logged-out visitors', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Dequeues the Dashicons icon font on the front end for visitors who are not logged in (the admin bar needs it only for logged-in users). If a theme or plugin declares Dashicons as a dependency, it is still loaded.', 'wp-debloat' );
	}

	public function apply(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue' ), 100 );
	}

	/**
	 * Dequeue the style.
	 */
	public function dequeue(): void {
		if ( is_user_logged_in() || is_customize_preview() ) {
			return;
		}
		wp_dequeue_style( 'dashicons' );
	}
}
