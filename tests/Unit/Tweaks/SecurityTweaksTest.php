<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit\Tweaks;

use Saizul\Debloat\Tests\WpTestCase;
use Saizul\Debloat\Tweaks\Security\RestAllowlist;
use Saizul\Debloat\Tweaks\Security\RestUsers;
use WP_Error;
use WP_REST_Request;
use WpRedirectException;
use WpStubs;

final class SecurityTweaksTest extends WpTestCase {

	private const METHODS = array(
		'wp.getPosts'                      => 'x',
		'pingback.ping'                    => 'x',
		'pingback.extensions.getPingbacks' => 'x',
	);

	public function test_xmlrpc_off_removes_every_method_and_the_header(): void {
		self::tweak( 'xmlrpc_off' )->apply();

		self::assertFalse( apply_filters( 'xmlrpc_enabled', true ) );
		self::assertSame( array(), apply_filters( 'xmlrpc_methods', self::METHODS ) );
		self::assertSame( array( 'Content-Type' => 'text/html' ), apply_filters( 'wp_headers', array( 'Content-Type' => 'text/html', 'X-Pingback' => 'https://example.com/xmlrpc.php' ) ) );
		self::assertSame( '', apply_filters( 'bloginfo_url', 'https://example.com/xmlrpc.php', 'pingback_url' ) );
		self::assertSame( 'https://example.com', apply_filters( 'bloginfo_url', 'https://example.com', 'url' ) );
	}

	public function test_xmlrpc_pingbacks_only_removes_pingback_methods(): void {
		self::tweak( 'xmlrpc_pingbacks' )->apply();

		self::assertSame( array( 'wp.getPosts' => 'x' ), apply_filters( 'xmlrpc_methods', self::METHODS ) );
		self::assertSame( array(), apply_filters( 'wp_headers', array( 'X-Pingback' => 'x' ) ) );
		self::assertTrue( apply_filters( 'xmlrpc_enabled', true ) );
	}

	public function test_rest_users_blocks_logged_out_visitors(): void {
		self::tweak( 'rest_user_enumeration' )->apply();

		$result = apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/wp/v2/users' ) );
		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 401, $result->get_error_data()['status'] );
		self::assertInstanceOf( WP_Error::class, apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/wp/v2/users/1' ) ) );
		self::assertNull( apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/wp/v2/posts' ) ) );
		self::assertNull( apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/wp/v2/users-extra' ) ) );

		WpStubs::$logged_in = true;
		self::assertNull( apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/wp/v2/users/me' ) ) );
	}

	public function test_rest_users_route_matching(): void {
		self::assertTrue( RestUsers::is_users_route( '/wp/v2/users' ) );
		self::assertTrue( RestUsers::is_users_route( 'wp/v2/users/12' ) );
		self::assertTrue( RestUsers::is_users_route( '/WP/V2/USERS' ) );
		self::assertFalse( RestUsers::is_users_route( '/wp/v2/usersx' ) );
		self::assertFalse( RestUsers::is_users_route( '/my/v1/wp/v2/users' ) );
	}

	public function test_author_query_redirects_numeric_author_requests(): void {
		WpStubs::$can = false;
		$tweak        = self::tweak( 'author_query_enumeration' );
		$tweak->apply();
		self::assertTrue( self::hooked( 'template_redirect', array( $tweak, 'maybe_redirect' ), 1 ) );

		$tweak->maybe_redirect();
		$_GET['author'] = 'admin';
		$tweak->maybe_redirect();

		$_GET['author'] = '1';
		try {
			$tweak->maybe_redirect();
			self::fail( 'No redirect.' );
		} catch ( WpRedirectException $redirect ) {
			self::assertSame( 'https://example.com/', $redirect->getMessage() );
			self::assertSame( 301, $redirect->status );
		}
	}

	public function test_author_query_lets_administrators_through(): void {
		WpStubs::$can   = array( 'list_users' => true );
		$_GET['author'] = '1';
		self::tweak( 'author_query_enumeration' )->maybe_redirect();
		self::addToAssertionCount( 1 );
	}

	public function test_sitemap_users_provider_is_removed(): void {
		self::tweak( 'sitemap_users' )->apply();
		$provider = new \stdClass();

		self::assertFalse( apply_filters( 'wp_sitemaps_add_provider', $provider, 'users' ) );
		self::assertSame( $provider, apply_filters( 'wp_sitemaps_add_provider', $provider, 'posts' ) );
	}

	public function test_hide_version_removes_generator_and_hashes_core_version(): void {
		add_action( 'wp_head', 'wp_generator' );
		self::tweak( 'hide_wp_version' )->apply();

		self::assertFalse( self::hooked( 'wp_head', 'wp_generator' ) );
		self::assertSame( '', apply_filters( 'the_generator', '<generator>6.9</generator>' ) );
		$src = apply_filters( 'style_loader_src', 'https://example.com/wp-includes/style.css?ver=6.9' );
		self::assertStringNotContainsString( 'ver=6.9', $src );
		self::assertMatchesRegularExpression( '/\?ver=[0-9a-f]{8}$/', $src );
		self::assertSame( $src, apply_filters( 'style_loader_src', 'https://example.com/wp-includes/style.css?ver=6.9' ), 'Stable hash.' );
		self::assertSame( 'https://example.com/p.js?ver=2.0', apply_filters( 'script_loader_src', 'https://example.com/p.js?ver=2.0' ) );
		self::assertSame( 'https://example.com/p.js?ver=6.9.1', apply_filters( 'script_loader_src', 'https://example.com/p.js?ver=6.9.1' ) );
	}

	public function test_file_editor_denies_edit_capabilities(): void {
		$tweak = self::tweak( 'file_editor' );
		$tweak->apply();

		self::assertSame( array( 'do_not_allow' ), apply_filters( 'map_meta_cap', array( 'edit_themes' ), 'edit_themes' ) );
		self::assertSame( array( 'do_not_allow' ), apply_filters( 'map_meta_cap', array( 'edit_plugins' ), 'edit_plugins' ) );
		self::assertSame( array( 'do_not_allow' ), apply_filters( 'map_meta_cap', array( 'edit_files' ), 'edit_files' ) );
		self::assertSame( array( 'install_plugins' ), apply_filters( 'map_meta_cap', array( 'install_plugins' ), 'install_plugins' ) );
		self::assertStringContainsString( 'DISALLOW_FILE_EDIT', $tweak->notice() );
	}

	public function test_login_errors_are_generic(): void {
		self::tweak( 'login_errors' )->apply();
		$message = apply_filters( 'login_errors', '<strong>Error:</strong> The password you entered for the username <strong>admin</strong> is incorrect.' );

		self::assertStringNotContainsString( 'admin', $message );
		self::assertStringContainsString( 'login details are incorrect', $message );
		self::assertSame( '', apply_filters( 'login_errors', '' ) );
	}

	public function test_application_passwords(): void {
		self::tweak( 'application_passwords' )->apply();

		self::assertFalse( apply_filters( 'wp_is_application_passwords_available', true ) );
	}

	public function test_security_headers_do_not_override_existing_ones(): void {
		self::tweak( 'security_headers' )->apply();
		$headers = apply_filters( 'wp_headers', array( 'x-frame-options' => 'DENY' ) );

		self::assertSame( 'DENY', $headers['x-frame-options'] );
		self::assertArrayNotHasKey( 'X-Frame-Options', $headers );
		self::assertSame( 'nosniff', $headers['X-Content-Type-Options'] );
		self::assertSame( 'strict-origin-when-cross-origin', $headers['Referrer-Policy'] );
	}

	public function test_rest_allowlist_blocks_unlisted_routes_for_logged_out_visitors(): void {
		self::tweak( 'rest_logged_out_allowlist', array( '/oembed/1.0', '/contact-form-7/v1' ) )->apply();

		self::assertNull( apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/oembed/1.0/embed' ) ) );
		self::assertNull( apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'POST', '/contact-form-7/v1/contact-forms/5/feedback' ) ) );
		$blocked = apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/wp/v2/posts' ) );
		self::assertInstanceOf( WP_Error::class, $blocked );
		self::assertSame( 'rest_login_required', $blocked->get_error_code() );
		self::assertInstanceOf( WP_Error::class, apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/' ) ) );

		WpStubs::$logged_in = true;
		self::assertNull( apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/wp/v2/posts' ) ) );
	}

	public function test_rest_allowlist_matches_on_segment_boundaries(): void {
		self::assertTrue( RestAllowlist::is_allowed( '/wc/store/v1/cart', array( 'wc/store/v1/' ) ) );
		self::assertTrue( RestAllowlist::is_allowed( '/wc/store/v1', array( '/wc/store/v1' ) ) );
		self::assertFalse( RestAllowlist::is_allowed( '/wc/store/v10', array( '/wc/store/v1' ) ) );
		self::assertFalse( RestAllowlist::is_allowed( '/wp/v2/posts', array( '/' ) ), 'A bare slash never allows everything.' );
		self::assertFalse( RestAllowlist::is_allowed( '/wp/v2/posts', array() ) );
	}

	public function test_rest_allowlist_uses_the_default_list(): void {
		self::tweak( 'rest_logged_out_allowlist' )->apply();

		self::assertNull( apply_filters( 'rest_pre_dispatch', null, null, new WP_REST_Request( 'GET', '/oembed/1.0/embed' ) ) );
	}

	public function test_rest_guards_keep_earlier_results(): void {
		self::tweak( 'rest_logged_out_allowlist' )->apply();
		self::tweak( 'rest_user_enumeration' )->apply();
		$earlier = array( 'cached' => true );

		self::assertSame( $earlier, apply_filters( 'rest_pre_dispatch', $earlier, null, new WP_REST_Request( 'GET', '/wp/v2/users' ) ) );
	}
}
