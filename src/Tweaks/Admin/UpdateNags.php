<?php
/**
 * Tweak: hide update nags from non-admins.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Admin;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the core update and maintenance notices for users who cannot update.
 */
final class UpdateNags extends Tweak {

	public function id(): string {
		return 'update_nags';
	}

	public function group(): string {
		return Group::ADMIN;
	}

	public function title(): string {
		return __( 'Hide update notices from non-administrators', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Editors, authors and other users who cannot update WordPress no longer see the "WordPress x.y is available" and maintenance notices. Administrators still see them and updates are not affected.', 'wp-debloat' );
	}

	public function apply(): void {
		add_action( 'admin_init', array( $this, 'remove' ) );
	}

	/**
	 * Remove the notices for users without update_core.
	 */
	public function remove(): void {
		if ( current_user_can( 'update_core' ) ) {
			return;
		}
		remove_action( 'admin_notices', 'update_nag', 3 );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
		remove_action( 'admin_notices', 'maintenance_nag', 10 );
		remove_action( 'network_admin_notices', 'maintenance_nag', 10 );
	}
}
