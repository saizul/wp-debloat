<?php
/**
 * Tweak: longer autosave interval.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the editor autosave interval.
 */
final class AutosaveInterval extends Tweak {

	public function id(): string {
		return 'autosave_interval';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Change the autosave interval', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Autosaves posts every this many seconds instead of every 60 seconds, which means fewer requests and autosave revisions. If AUTOSAVE_INTERVAL is already defined in wp-config.php, the block editor still uses this value but the classic editor uses the constant.', 'wp-debloat' );
	}

	public function field(): ?Field {
		return Field::int( 120, 30, 3600, 's' );
	}

	public function field_label(): string {
		return __( 'Interval in seconds', 'wp-debloat' );
	}

	public function apply(): void {
		// Tweaks apply on plugins_loaded, before WordPress defines the default.
		if ( ! defined( 'AUTOSAVE_INTERVAL' ) ) {
			define( 'AUTOSAVE_INTERVAL', (int) $this->value() );
		}
		add_filter( 'block_editor_settings_all', array( $this, 'editor_settings' ) );
	}

	/**
	 * Block editor setting.
	 *
	 * @param mixed $settings Editor settings.
	 * @return mixed
	 */
	public function editor_settings( $settings ) {
		if ( is_array( $settings ) ) {
			$settings['autosaveInterval'] = (int) $this->value();
		}

		return $settings;
	}
}
