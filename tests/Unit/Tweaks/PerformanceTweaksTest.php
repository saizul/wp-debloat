<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit\Tweaks;

use Saizul\Debloat\Tests\WpTestCase;
use WpStubs;

final class PerformanceTweaksTest extends WpTestCase {

	public function test_emojis_removes_core_hooks_including_admin_ones_later(): void {
		add_action( 'wp_head', 'print_emoji_detection_script', 7 );
		add_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		add_filter( 'the_content_feed', 'wp_staticize_emoji' );
		add_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		$tweak = self::tweak( 'emojis' );
		$tweak->apply();

		self::assertFalse( self::hooked( 'wp_head', 'print_emoji_detection_script' ) );
		self::assertFalse( self::hooked( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' ) );
		self::assertFalse( self::hooked( 'the_content_feed', 'wp_staticize_emoji' ) );
		self::assertFalse( self::hooked( 'wp_mail', 'wp_staticize_emoji_for_email' ) );
		self::assertFalse( apply_filters( 'emoji_svg_url', 'https://s.w.org/' ) );
		self::assertSame( array( 'lists', 'wplink' ), apply_filters( 'tiny_mce_plugins', array( 'lists', 'wpemoji', 'wplink' ) ) );

		// Admin hooks are registered after plugins load.
		add_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		add_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		do_action( 'admin_init' );
		self::assertFalse( self::hooked( 'admin_print_scripts', 'print_emoji_detection_script' ) );
		self::assertFalse( self::hooked( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' ) );
	}

	public function test_embeds_removes_discovery_and_route(): void {
		add_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		add_action( 'wp_head', 'wp_oembed_add_discovery_links', 4 );
		add_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_action( 'rest_api_init', 'wp_oembed_register_route' );
		self::tweak( 'embeds' )->apply();

		self::assertFalse( self::hooked( 'wp_head', 'wp_oembed_add_discovery_links' ) );
		self::assertFalse( self::hooked( 'wp_head', 'wp_oembed_add_host_js' ) );
		self::assertFalse( self::hooked( 'rest_api_init', 'wp_oembed_register_route' ) );
		self::assertFalse( apply_filters( 'embed_oembed_discover', true ) );
		self::assertSame( array( 'lists' ), apply_filters( 'tiny_mce_plugins', array( 'wpembed', 'lists' ) ) );
		do_action( 'wp_footer' );
		self::assertSame( array( 'wp-embed' ), WpStubs::calls( 'dequeue_script' ) );
	}

	public function test_jquery_migrate_is_removed_on_the_front_end_only(): void {
		self::tweak( 'jquery_migrate' )->apply();
		$scripts = (object) array( 'registered' => array( 'jquery' => (object) array( 'deps' => array( 'jquery-core', 'jquery-migrate' ) ) ) );
		do_action( 'wp_default_scripts', $scripts );
		self::assertSame( array( 'jquery-core' ), $scripts->registered['jquery']->deps );

		WpStubs::$admin = true;
		$admin          = (object) array( 'registered' => array( 'jquery' => (object) array( 'deps' => array( 'jquery-core', 'jquery-migrate' ) ) ) );
		do_action( 'wp_default_scripts', $admin );
		self::assertSame( array( 'jquery-core', 'jquery-migrate' ), $admin->registered['jquery']->deps );

		do_action( 'wp_default_scripts', (object) array( 'registered' => array() ) );
		self::addToAssertionCount( 1 );
	}

	public function test_dashicons_only_for_logged_out_visitors(): void {
		self::tweak( 'dashicons_logged_out' )->apply();
		WpStubs::$logged_in = true;
		do_action( 'wp_enqueue_scripts' );
		self::assertSame( array(), WpStubs::calls( 'dequeue_style' ) );

		WpStubs::$logged_in = false;
		do_action( 'wp_enqueue_scripts' );
		self::assertSame( array( 'dashicons' ), WpStubs::calls( 'dequeue_style' ) );
	}

	public function test_block_library_css_only_on_classic_singular_pages_without_blocks(): void {
		$tweak = self::tweak( 'block_css_classic_pages' );
		$tweak->apply();
		WpStubs::$is['singular'] = true;
		WpStubs::$queried        = WpStubs::post( 1, array( 'post_content' => '<p>Classic</p>' ) );
		self::assertTrue( $tweak->applies() );
		do_action( 'wp_enqueue_scripts' );
		self::assertSame( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles' ), WpStubs::calls( 'dequeue_style' ) );

		WpStubs::$queried = WpStubs::post( 2, array( 'post_content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ) );
		self::assertFalse( $tweak->applies(), 'Has blocks.' );

		WpStubs::$queried     = WpStubs::post( 3, array( 'post_content' => 'Classic' ) );
		WpStubs::$block_theme = true;
		self::assertFalse( $tweak->applies(), 'Block theme.' );

		WpStubs::$block_theme  = false;
		WpStubs::$block_widget = 'sidebar-1';
		self::assertFalse( $tweak->applies(), 'Block widgets.' );

		WpStubs::$block_widget   = false;
		WpStubs::$is['singular'] = false;
		self::assertFalse( $tweak->applies(), 'Archive.' );
	}

	public function test_global_styles_removed_for_classic_themes_only(): void {
		self::tweak( 'global_styles' )->apply();
		WpStubs::$block_theme = true;
		add_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		do_action( 'after_setup_theme' );
		self::assertTrue( self::hooked( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' ) );

		WpStubs::$block_theme = false;
		add_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
		add_action( 'wp_footer', array( 'WP_Duotone', 'output_footer_assets' ), 10 );
		do_action( 'after_setup_theme' );
		self::assertFalse( self::hooked( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' ) );
		self::assertFalse( self::hooked( 'wp_footer', 'wp_enqueue_global_styles' ) );
		self::assertFalse( self::hooked( 'wp_footer', array( 'WP_Duotone', 'output_footer_assets' ) ) );
	}

	public function test_heartbeat_front_end_and_interval(): void {
		self::tweak( 'heartbeat_front_end' )->apply();
		do_action( 'wp_enqueue_scripts' );
		self::assertSame( array( 'heartbeat' ), WpStubs::calls( 'deregister_script' ) );

		self::tweak( 'heartbeat_interval' )->apply();
		self::assertSame( array( 'interval' => 60 ), apply_filters( 'heartbeat_settings', array() ) );
		WpStubs::reset();
		self::tweak( 'heartbeat_interval', 120 )->apply();
		self::assertSame( array( 'a' => 1, 'interval' => 120 ), apply_filters( 'heartbeat_settings', array( 'a' => 1 ) ) );
	}

	public function test_self_pingbacks_removes_internal_links_by_reference(): void {
		self::tweak( 'self_pingbacks' )->apply();
		$links = array( 'https://example.com/other-post/', 'https://external.org/', 'https://example.com', 'https://example.community/x', 'https://example.com?p=2' );
		$pung  = array();
		do_action_ref_array( 'pre_ping', array( &$links, &$pung, 1 ) );

		self::assertSame( array( 'https://external.org/', 'https://example.community/x' ), $links );
	}

	public function test_revisions_limit_respects_lower_constant(): void {
		self::tweak( 'revisions_limit', 5 )->apply();

		self::assertSame( 5, apply_filters( 'wp_revisions_to_keep', -1, null ) );
		self::assertSame( 3, apply_filters( 'wp_revisions_to_keep', 3, null ) );
		self::assertSame( 0, apply_filters( 'wp_revisions_to_keep', 0, null ) );
		self::assertSame( 5, apply_filters( 'wp_revisions_to_keep', 50, null ) );
	}

	public function test_autosave_interval_sets_the_editor_setting(): void {
		self::tweak( 'autosave_interval', 300 )->apply();

		self::assertTrue( defined( 'AUTOSAVE_INTERVAL' ) );
		self::assertSame( array( 'autosaveInterval' => 300 ), apply_filters( 'block_editor_settings_all', array( 'autosaveInterval' => 60 ) ) );
	}

	public function test_asset_query_strings_strips_ver_on_the_front_end(): void {
		self::tweak( 'asset_query_strings' )->apply();

		self::assertSame( 'https://example.com/a.css', apply_filters( 'style_loader_src', 'https://example.com/a.css?ver=6.9' ) );
		self::assertSame( 'https://example.com/a.js?x=1', apply_filters( 'script_loader_src', 'https://example.com/a.js?ver=1.2&x=1' ) );
		WpStubs::$admin = true;
		self::assertSame( 'https://example.com/a.css?ver=6.9', apply_filters( 'style_loader_src', 'https://example.com/a.css?ver=6.9' ) );
	}

	/**
	 * @dataProvider head_cleanups
	 *
	 * @param array<int,array{0:string,1:string,2:int}> $hooks Hooks that must be removed.
	 */
	public function test_head_cleanup_tweaks_remove_their_hooks( string $id, array $hooks ): void {
		foreach ( $hooks as $hook ) {
			add_action( $hook[0], $hook[1], $hook[2] );
		}
		self::tweak( $id )->apply();
		foreach ( $hooks as $hook ) {
			self::assertFalse( self::hooked( $hook[0], $hook[1] ), $id . ': ' . $hook[1] );
		}
	}

	/** @return array<string,array{0:string,1:array<int,array{0:string,1:string,2:int}>}> */
	public static function head_cleanups(): array {
		return array(
			'rsd'       => array( 'rsd_link', array( array( 'wp_head', 'rsd_link', 10 ) ) ),
			'wlw'       => array( 'wlw_manifest', array( array( 'wp_head', 'wlwmanifest_link', 10 ) ) ),
			'shortlink' => array( 'shortlink', array( array( 'wp_head', 'wp_shortlink_wp_head', 10 ), array( 'template_redirect', 'wp_shortlink_header', 11 ) ) ),
			'rest link' => array( 'rest_api_link', array( array( 'wp_head', 'rest_output_link_wp_head', 10 ), array( 'template_redirect', 'rest_output_link_header', 11 ) ) ),
			'feeds'     => array( 'feed_links_extra', array( array( 'wp_head', 'feed_links_extra', 3 ) ) ),
		);
	}

	public function test_feed_links_extra_keeps_the_main_feed(): void {
		add_action( 'wp_head', 'feed_links', 2 );
		self::tweak( 'feed_links_extra' )->apply();

		self::assertTrue( self::hooked( 'wp_head', 'feed_links', 2 ) );
		self::assertFalse( apply_filters( 'feed_links_show_comments_feed', true ) );
	}

	public function test_remote_block_patterns(): void {
		self::tweak( 'remote_block_patterns' )->apply();

		self::assertFalse( apply_filters( 'should_load_remote_block_patterns', true ) );
	}
}
