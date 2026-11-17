<?php
/**
 * Tweak: do not load jQuery Migrate on the front end.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes jquery-migrate from the dependencies of jquery on the front end.
 */
final class JqueryMigrate extends Tweak {

	public function id(): string {
		return 'jquery_migrate';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove jQuery Migrate on the front end', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Loads jQuery without the jQuery Migrate compatibility layer for visitors. Old themes or plugins that use removed jQuery functions can break; check the browser console after switching it on. The admin is not changed.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function apply(): void {
		add_action( 'wp_default_scripts', array( $this, 'remove_migrate' ) );
	}

	/**
	 * Drop the dependency.
	 *
	 * @param object $scripts WP_Scripts instance.
	 */
	public function remove_migrate( $scripts ): void {
		if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
			return;
		}
		$jquery = $scripts->registered['jquery'];
		if ( is_array( $jquery->deps ) ) {
			$jquery->deps = array_values( array_diff( $jquery->deps, array( 'jquery-migrate' ) ) );
		}
	}
}
