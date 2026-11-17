<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit\Tweaks;

use Saizul\Debloat\Tests\WpTestCase;
use WP_Admin_Bar;
use WP_Query;
use WpRedirectException;
use WpStubs;

final class ContentTweaksTest extends WpTestCase {

	public function test_comments_are_closed_and_hidden(): void {
		self::tweak( 'comments' )->apply();

		self::assertFalse( apply_filters( 'comments_open', true, 1 ) );
		self::assertFalse( apply_filters( 'pings_open', true, 1 ) );
		self::assertSame( array(), apply_filters( 'comments_array', array( 'a comment' ), 1 ) );
		self::assertSame( 0, apply_filters( 'get_comments_number', 4, 1 ) );
		self::assertFalse( apply_filters( 'feed_links_show_comments_feed', true ) );
	}

	public function test_comments_removes_routes_methods_support_and_menus(): void {
		self::tweak( 'comments' )->apply();

		$routes = apply_filters( 'rest_endpoints', array( '/wp/v2/posts' => 1, '/wp/v2/comments' => 1, '/wp/v2/comments/(?P<id>[\d]+)' => 1 ) );
		self::assertSame( array( '/wp/v2/posts' => 1 ), $routes );
		self::assertSame( array( 'wp.getPosts' => 1 ), apply_filters( 'xmlrpc_methods', array( 'wp.getPosts' => 1, 'wp.newComment' => 1 ) ) );

		do_action( 'init' );
		self::assertFalse( post_type_supports( 'post', 'comments' ) );
		self::assertFalse( post_type_supports( 'post', 'trackbacks' ) );
		self::assertFalse( post_type_supports( 'page', 'comments' ) );

		do_action( 'widgets_init' );
		self::assertSame( array( 'WP_Widget_Recent_Comments' ), WpStubs::calls( 'widget' ) );

		do_action( 'admin_menu' );
		self::assertSame( array( 'edit-comments.php' ), WpStubs::calls( 'menu' ) );
		self::assertSame( array( 'options-general.php>options-discussion.php' ), WpStubs::calls( 'submenu' ) );

		$bar = new WP_Admin_Bar();
		do_action( 'admin_bar_menu', $bar );
		self::assertSame( array( 'comments' ), $bar->removed );

		do_action( 'wp_enqueue_scripts' );
		self::assertSame( array( 'comment-reply' ), WpStubs::calls( 'dequeue_script' ) );
	}

	public function test_comments_redirects_comment_screens_and_feeds(): void {
		$tweak = self::tweak( 'comments' );
		$GLOBALS['pagenow'] = 'edit-comments.php';
		try {
			$tweak->admin_screens();
			self::fail( 'No redirect.' );
		} catch ( WpRedirectException $redirect ) {
			self::assertSame( 'https://example.com/wp-admin/', $redirect->getMessage() );
			self::assertSame( 302, $redirect->status );
		}

		$GLOBALS['pagenow'] = 'edit.php';
		$tweak->admin_screens();

		$tweak->comment_feed();
		WpStubs::$is['comment_feed'] = true;
		$this->expectException( WpRedirectException::class );
		$tweak->comment_feed();
	}

	public function test_attachment_pages_redirect_to_parent_or_file(): void {
		$tweak = self::tweak( 'attachment_pages' );
		$tweak->apply();
		self::assertSame( '0', apply_filters( 'pre_option_wp_attachment_pages_enabled', false ) );

		WpStubs::post( 10, array( 'post_status' => 'publish' ) );
		WpStubs::post( 11, array( 'post_status' => 'draft' ) );
		WpStubs::post( 20, array( 'post_type' => 'attachment', 'post_parent' => 10 ) );
		WpStubs::post( 21, array( 'post_type' => 'attachment', 'post_parent' => 11 ) );
		WpStubs::post( 22, array( 'post_type' => 'attachment' ) );

		self::assertSame( 'https://example.com/?p=10', $tweak->target( 20 ) );
		self::assertSame( 'https://example.com/wp-content/uploads/file-21.jpg', $tweak->target( 21 ), 'Draft parent is not revealed.' );
		self::assertSame( 'https://example.com/wp-content/uploads/file-22.jpg', $tweak->target( 22 ) );
		self::assertSame( '', $tweak->target( 99 ) );

		$tweak->maybe_redirect();
		WpStubs::$is['attachment'] = true;
		WpStubs::$queried          = WpStubs::$posts[20];
		try {
			$tweak->maybe_redirect();
			self::fail( 'No redirect.' );
		} catch ( WpRedirectException $redirect ) {
			self::assertSame( 'https://example.com/?p=10', $redirect->getMessage() );
			self::assertSame( 301, $redirect->status );
		}
	}

	public function test_author_archives_404_by_default(): void {
		$tweak = self::tweak( 'author_archives' );
		$tweak->apply();
		self::assertSame( 'https://example.com/', apply_filters( 'author_link', 'https://example.com/author/admin/' ) );

		$GLOBALS['wp_query'] = new WP_Query();
		$tweak->handle();
		self::assertSame( array(), WpStubs::calls( 'status' ) );

		WpStubs::$is['author'] = true;
		$tweak->handle();
		self::assertTrue( $GLOBALS['wp_query']->is_404 );
		self::assertSame( array( '404' ), WpStubs::calls( 'status' ) );
	}

	public function test_author_archives_can_redirect_home(): void {
		WpStubs::$is['author'] = true;
		$this->expectException( WpRedirectException::class );
		self::tweak( 'author_archives', 'home' )->handle();
	}

	public function test_search_becomes_a_404_on_the_front_end(): void {
		$tweak = self::tweak( 'search' );
		$tweak->apply();
		self::assertSame( '', apply_filters( 'get_search_form', '<form></form>' ) );

		$query            = new WP_Query();
		$query->is_search = true;
		do_action( 'parse_query', $query );
		self::assertTrue( $query->is_404 );
		self::assertFalse( $query->is_search );

		$_GET['s'] = 'spam';
		self::assertTrue( apply_filters( 'pre_handle_404', false, $query ) );
		self::assertSame( array( '404' ), WpStubs::calls( 'status' ) );

		$secondary            = new WP_Query();
		$secondary->is_search = true;
		$secondary->main      = false;
		do_action( 'parse_query', $secondary );
		self::assertTrue( $secondary->is_search, 'Secondary queries are untouched.' );

		WpStubs::$admin    = true;
		$admin             = new WP_Query();
		$admin->is_search  = true;
		do_action( 'parse_query', $admin );
		self::assertTrue( $admin->is_search, 'Admin search is untouched.' );

		do_action( 'widgets_init' );
		self::assertSame( array( 'WP_Widget_Search' ), WpStubs::calls( 'widget' ) );
		$bar = new WP_Admin_Bar();
		do_action( 'admin_bar_menu', $bar );
		self::assertSame( array( 'search' ), $bar->removed );
	}

	public function test_feeds_redirect_and_links_are_removed(): void {
		add_action( 'wp_head', 'feed_links', 2 );
		add_action( 'wp_head', 'feed_links_extra', 3 );
		$tweak = self::tweak( 'feeds' );
		$tweak->apply();

		self::assertFalse( self::hooked( 'wp_head', 'feed_links' ) );
		self::assertFalse( self::hooked( 'wp_head', 'feed_links_extra' ) );
		foreach ( array( 'rss2', 'atom', 'rdf', 'rss', 'rss2_comments', 'atom_comments' ) as $type ) {
			self::assertTrue( self::hooked( 'do_feed_' . $type, array( $tweak, 'block' ), 1 ), $type );
		}
		$this->expectException( WpRedirectException::class );
		do_action( 'do_feed_rss2', false, 'rss2' );
	}

	public function test_core_sitemaps(): void {
		self::tweak( 'core_sitemaps' )->apply();

		self::assertFalse( apply_filters( 'wp_sitemaps_enabled', true ) );
	}
}
