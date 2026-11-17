<?php
/**
 * Tweak: remove the WordPress Events and News widget.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Admin;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the Events and News dashboard box, which loads remote feeds.
 */
final class DashboardNews extends Tweak {

	public function id(): string {
		return 'dashboard_news';
	}

	public function group(): string {
		return Group::ADMIN;
	}

	public function title(): string {
		return __( 'Remove the WordPress Events and News widget', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the WordPress Events and News box from the dashboard and the network dashboard, so the dashboard no longer fetches remote news feeds and nearby events.', 'wp-debloat' );
	}

	public function apply(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'remove_box' ), 999 );
		add_action( 'wp_network_dashboard_setup', array( $this, 'remove_network_box' ), 999 );
	}

	/**
	 * Remove the site dashboard box.
	 */
	public function remove_box(): void {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	}

	/**
	 * Remove the network dashboard box.
	 */
	public function remove_network_box(): void {
		remove_meta_box( 'dashboard_primary', 'dashboard-network', 'side' );
	}
}
