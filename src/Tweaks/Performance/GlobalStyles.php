<?php
/**
 * Tweak: remove global styles and duotone output in classic themes.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the global styles stylesheet and the duotone SVG filters on
 * classic themes. Block themes are never changed.
 */
final class GlobalStyles extends Tweak {

	public function id(): string {
		return 'global_styles';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove global styles and duotone filters (classic themes)', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the inline global-styles CSS (colour and font-size preset classes from theme.json) and the duotone SVG filters on the front end. Only applies to classic themes. Blocks that use preset colours, font sizes or duotone can lose that styling.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function apply(): void {
		// The theme is known only after it is set up.
		add_action( 'after_setup_theme', array( $this, 'remove' ), 99 );
	}

	/**
	 * Remove the hooks for classic themes.
	 */
	public function remove(): void {
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return;
		}
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		remove_action( 'wp_enqueue_scripts', array( 'WP_Duotone', 'output_global_styles' ), 11 );
		remove_action( 'wp_footer', array( 'WP_Duotone', 'output_footer_assets' ), 10 );
	}
}
