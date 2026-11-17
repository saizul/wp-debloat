<?php
/**
 * Tweak: skip block library CSS on pages without blocks.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Dequeues the block library styles on single posts and pages whose content
 * has no blocks, in classic themes without block widgets.
 */
final class BlockLibraryCss extends Tweak {

	/**
	 * Style handles that only matter when blocks are on the page.
	 */
	const HANDLES = array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles' );

	public function id(): string {
		return 'block_css_classic_pages';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove block library CSS on pages without blocks', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'On single posts and pages written without blocks (classic editor, page builders), dequeues the block library stylesheets. Only for classic themes; skipped on block themes, on archives and when block widgets are in use. Blocks added by the theme templates or shortcodes can lose their styling.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function apply(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue' ), 100 );
	}

	/**
	 * Dequeue when safe.
	 */
	public function dequeue(): void {
		if ( ! $this->applies() ) {
			return;
		}
		foreach ( self::HANDLES as $handle ) {
			wp_dequeue_style( $handle );
		}
	}

	/**
	 * Whether the current view has no blocks at all.
	 */
	public function applies(): bool {
		if ( ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) || ! is_singular() ) {
			return false;
		}
		if ( is_active_widget( false, false, 'block' ) ) {
			return false;
		}
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return ! has_blocks( $post->post_content );
	}
}
