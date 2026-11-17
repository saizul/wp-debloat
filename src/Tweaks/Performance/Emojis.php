<?php
/**
 * Tweak: remove the emoji script and styles.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the emoji detection script, its styles and the emoji conversion in
 * feeds and e-mails. Browsers show emoji natively.
 */
final class Emojis extends Tweak {

	public function id(): string {
		return 'emojis';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove emoji scripts and styles', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the inline emoji detection script and emoji styles from every page, the admin and embeds, and stops converting emoji to images in feeds and e-mails. Modern browsers display emoji on their own.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'embed_head', 'print_emoji_detection_script' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'enqueue_embed_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'tinymce_plugins' ) );
		add_filter( 'emoji_svg_url', '__return_false' );
		// The admin hooks are added after plugins load, so remove them later.
		add_action( 'admin_init', array( $this, 'remove_admin_hooks' ) );
	}

	/**
	 * Remove the admin emoji hooks.
	 */
	public function remove_admin_hooks(): void {
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Drop the TinyMCE emoji plugin.
	 *
	 * @param mixed $plugins Plugin names.
	 * @return mixed
	 */
	public function tinymce_plugins( $plugins ) {
		return is_array( $plugins ) ? array_values( array_diff( $plugins, array( 'wpemoji' ) ) ) : $plugins;
	}
}
