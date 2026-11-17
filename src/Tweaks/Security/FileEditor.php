<?php
/**
 * Tweak: disable the theme and plugin file editor.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the edit_themes, edit_plugins and edit_files capabilities.
 */
final class FileEditor extends Tweak {

	/**
	 * Capabilities that DISALLOW_FILE_EDIT also removes.
	 */
	const CAPS = array( 'edit_themes', 'edit_plugins', 'edit_files' );

	public function id(): string {
		return 'file_editor';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Disable the theme and plugin file editor', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the Theme File Editor and Plugin File Editor screens for everyone, so a stolen administrator login cannot be used to write PHP code from the dashboard. Installing and updating plugins is not affected.', 'wp-debloat' );
	}

	public function notice(): string {
		if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
			return '';
		}

		return __( 'For stronger protection that also works when this plugin is off, add define( \'DISALLOW_FILE_EDIT\', true ); to wp-config.php.', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 2 );
	}

	/**
	 * Deny the capabilities.
	 *
	 * @param mixed  $caps Primitive capabilities.
	 * @param string $cap  Requested capability.
	 * @return mixed
	 */
	public function map_meta_cap( $caps, $cap = '' ) {
		return in_array( $cap, self::CAPS, true ) ? array( 'do_not_allow' ) : $caps;
	}
}
