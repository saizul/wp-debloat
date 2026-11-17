<?php
/**
 * Tweak: disable the oEmbed provider and discovery.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Stops other sites from embedding your posts and removes the embed script.
 */
final class Embeds extends Tweak {

	public function id(): string {
		return 'embeds';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Disable WordPress post embeds (oEmbed provider)', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the oEmbed discovery links, the oEmbed REST route and the wp-embed script, and stops discovering oEmbed data on unknown sites. Other sites can no longer embed your posts as cards. Embeds from known providers such as YouTube keep working.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 4 );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		remove_filter( 'embed_oembed_html', 'wp_maybe_enqueue_oembed_host_js' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		add_filter( 'tiny_mce_plugins', array( $this, 'tinymce_plugins' ) );
		add_action( 'wp_footer', array( $this, 'dequeue_script' ), 1 );
	}

	/**
	 * Remove the wp-embed script if something enqueued it.
	 */
	public function dequeue_script(): void {
		wp_dequeue_script( 'wp-embed' );
	}

	/**
	 * Drop the TinyMCE embed plugin.
	 *
	 * @param mixed $plugins Plugin names.
	 * @return mixed
	 */
	public function tinymce_plugins( $plugins ) {
		return is_array( $plugins ) ? array_values( array_diff( $plugins, array( 'wpembed' ) ) ) : $plugins;
	}
}
