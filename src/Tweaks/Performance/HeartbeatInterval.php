<?php
/**
 * Tweak: slow down the Heartbeat API.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the Heartbeat interval.
 */
final class HeartbeatInterval extends Tweak {

	public function id(): string {
		return 'heartbeat_interval';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Slow down the Heartbeat API', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Sets how often the browser sends Heartbeat requests (WordPress uses 15 to 60 seconds). Fewer requests mean less server load on busy admin screens. Kept at 120 seconds or less so post locks keep working.', 'wp-debloat' );
	}

	public function field(): ?Field {
		return Field::int( 60, 15, 120, 's' );
	}

	public function field_label(): string {
		return __( 'Interval in seconds', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'heartbeat_settings', array( $this, 'settings' ) );
	}

	/**
	 * Set the interval.
	 *
	 * @param mixed $settings Heartbeat settings.
	 * @return array<string,mixed>
	 */
	public function settings( $settings ): array {
		$settings             = is_array( $settings ) ? $settings : array();
		$settings['interval'] = (int) $this->value();

		return $settings;
	}
}
