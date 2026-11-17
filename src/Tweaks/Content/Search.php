<?php
/**
 * Tweak: disable the front-end search.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Content;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Turns front-end searches into 404s and removes the search form.
 */
final class Search extends Tweak {

	public function id(): string {
		return 'search';
	}

	public function group(): string {
		return Group::CONTENT;
	}

	public function title(): string {
		return __( 'Disable the front-end search', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Search requests (?s=) on the front end return a 404, the search form and the Search widget are removed and the admin bar search box is hidden. Spam searches no longer cost database queries. Search in the admin and in the REST API is not changed, and the Search block in block themes still renders. Not part of any preset.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function presets(): array {
		return array();
	}

	public function apply(): void {
		add_action( 'parse_query', array( $this, 'parse_query' ) );
		add_filter( 'pre_handle_404', array( $this, 'handle_404' ), 10, 2 );
		add_filter( 'get_search_form', '__return_empty_string', 99 );
		add_action( 'widgets_init', array( $this, 'unregister_widget' ), 20 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 999 );
	}

	/**
	 * Turn a main front-end search query into a 404.
	 *
	 * @param object $query WP_Query.
	 */
	public function parse_query( $query ): void {
		if ( is_admin() || ! is_object( $query ) || ! method_exists( $query, 'is_main_query' ) || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}
		$query->is_search       = false;
		$query->query_vars['s'] = '';
		$query->set_404();
	}

	/**
	 * Send the 404 status ourselves; WordPress skips it for queries already marked 404.
	 *
	 * @param mixed  $preempt Whether to short-circuit.
	 * @param object $query   WP_Query.
	 * @return mixed
	 */
	public function handle_404( $preempt, $query = null ) {
		if ( is_object( $query ) && method_exists( $query, 'is_404' ) && $query->is_404() && isset( $_GET['s'] ) && ! is_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence check only.
			status_header( 404 );
			nocache_headers();

			return true;
		}

		return $preempt;
	}

	/**
	 * Remove the Search widget.
	 */
	public function unregister_widget(): void {
		unregister_widget( 'WP_Widget_Search' );
	}

	/**
	 * Remove the admin bar search box.
	 *
	 * @param object $admin_bar WP_Admin_Bar.
	 */
	public function admin_bar( $admin_bar ): void {
		if ( is_object( $admin_bar ) && method_exists( $admin_bar, 'remove_node' ) ) {
			$admin_bar->remove_node( 'search' );
		}
	}
}
