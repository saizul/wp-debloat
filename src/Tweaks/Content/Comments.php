<?php
/**
 * Tweak: disable comments sitewide.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Content;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Turns comments and pings off everywhere: front end, admin, REST API, feeds
 * and XML-RPC. Existing comments stay in the database.
 */
final class Comments extends Tweak {

	/**
	 * REST routes of the comments controller.
	 */
	const REST_ROUTES = array( '/wp/v2/comments', '/wp/v2/comments/(?P<id>[\d]+)' );

	public function id(): string {
		return 'comments';
	}

	public function group(): string {
		return Group::CONTENT;
	}

	public function title(): string {
		return __( 'Disable comments everywhere', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Closes comments and pings on all content, hides existing comments, removes the Comments admin menu, admin bar item, dashboard and discussion screens, the comments feed and its links, the comments REST API routes, the Recent Comments widget and the comment-reply script. Existing comments are not deleted and come back when the tweak is switched off.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function apply(): void {
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'comments_array', '__return_empty_array', 20 );
		add_filter( 'get_comments_number', '__return_zero', 20 );
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
		add_filter( 'rest_endpoints', array( $this, 'rest_endpoints' ) );
		add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc_methods' ) );
		add_filter( 'wp_headers', array( $this, 'headers' ) );
		add_action( 'init', array( $this, 'remove_post_type_support' ), 100 );
		add_action( 'widgets_init', array( $this, 'unregister_widget' ), 20 );
		add_action( 'template_redirect', array( $this, 'comment_feed' ), 9 );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_script' ), 100 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 999 );
		add_action( 'admin_init', array( $this, 'admin_screens' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 999 );
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard' ), 999 );
	}

	/**
	 * Remove comment and trackback support from every post type.
	 */
	public function remove_post_type_support(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
			}
			if ( post_type_supports( $post_type, 'trackbacks' ) ) {
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	/**
	 * Drop the comments REST routes.
	 *
	 * @param mixed $endpoints Endpoints.
	 * @return mixed
	 */
	public function rest_endpoints( $endpoints ) {
		if ( is_array( $endpoints ) ) {
			foreach ( self::REST_ROUTES as $route ) {
				unset( $endpoints[ $route ] );
			}
		}

		return $endpoints;
	}

	/**
	 * Drop the XML-RPC comment methods.
	 *
	 * @param mixed $methods Methods.
	 * @return mixed
	 */
	public function xmlrpc_methods( $methods ) {
		if ( is_array( $methods ) ) {
			foreach ( array( 'wp.newComment', 'wp.editComment', 'wp.deleteComment', 'wp.getComment', 'wp.getComments', 'wp.getCommentCount', 'wp.getCommentStatusList' ) as $method ) {
				unset( $methods[ $method ] );
			}
		}

		return $methods;
	}

	/**
	 * Remove X-Pingback.
	 *
	 * @param mixed $headers Headers.
	 * @return mixed
	 */
	public function headers( $headers ) {
		if ( is_array( $headers ) ) {
			unset( $headers['X-Pingback'] );
		}

		return $headers;
	}

	/**
	 * Remove the Recent Comments widget.
	 */
	public function unregister_widget(): void {
		unregister_widget( 'WP_Widget_Recent_Comments' );
	}

	/**
	 * Send comment feeds to the home page.
	 */
	public function comment_feed(): void {
		if ( is_comment_feed() ) {
			$this->redirect( home_url( '/' ) );
		}
	}

	/**
	 * Remove the comment-reply script.
	 */
	public function dequeue_script(): void {
		wp_dequeue_script( 'comment-reply' );
	}

	/**
	 * Remove the admin menus.
	 */
	public function admin_menu(): void {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	/**
	 * Redirect away from the comment screens.
	 */
	public function admin_screens(): void {
		global $pagenow;
		if ( in_array( $pagenow, array( 'edit-comments.php', 'comment.php', 'options-discussion.php' ), true ) ) {
			$this->redirect( admin_url(), 302 );
		}
	}

	/**
	 * Remove the admin bar item.
	 *
	 * @param object $admin_bar WP_Admin_Bar.
	 */
	public function admin_bar( $admin_bar ): void {
		if ( is_object( $admin_bar ) && method_exists( $admin_bar, 'remove_node' ) ) {
			$admin_bar->remove_node( 'comments' );
		}
	}

	/**
	 * Remove the Recent Comments dashboard box.
	 */
	public function dashboard(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}
}
