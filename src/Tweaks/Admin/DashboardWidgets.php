<?php
/**
 * Tweak: clean up the dashboard.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Admin;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the Welcome panel, Quick Draft and Activity boxes.
 */
final class DashboardWidgets extends Tweak {

	/**
	 * Meta box ID => context.
	 */
	const BOXES = array(
		'dashboard_quick_press' => 'side',
		'dashboard_activity'    => 'normal',
	);

	public function id(): string {
		return 'dashboard_widgets';
	}

	public function group(): string {
		return Group::ADMIN;
	}

	public function title(): string {
		return __( 'Clean up the dashboard', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the Welcome panel, the Quick Draft box and the Activity box from the dashboard. At a Glance and Site Health stay. Boxes added by other plugins are not touched.', 'wp-debloat' );
	}

	public function apply(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'remove_boxes' ), 999 );
		// The welcome panel hook is added after plugins load.
		add_action( 'admin_init', array( $this, 'remove_welcome_panel' ) );
	}

	/**
	 * Remove the boxes.
	 */
	public function remove_boxes(): void {
		foreach ( self::BOXES as $box => $context ) {
			remove_meta_box( $box, 'dashboard', $context );
		}
	}

	/**
	 * Remove the welcome panel.
	 */
	public function remove_welcome_panel(): void {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
}
