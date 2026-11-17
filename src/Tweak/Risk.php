<?php
/**
 * Risk levels.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * How likely a tweak is to break something.
 */
final class Risk {

	const LOW    = 'low';
	const MEDIUM = 'medium';
	const HIGH   = 'high';

	/**
	 * All levels, from safest to riskiest.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array( self::LOW, self::MEDIUM, self::HIGH );
	}

	/**
	 * Whether a value is a known level.
	 *
	 * @param string $risk Risk level.
	 */
	public static function is_valid( string $risk ): bool {
		return in_array( $risk, self::all(), true );
	}

	/**
	 * Translated label.
	 *
	 * @param string $risk Risk level.
	 */
	public static function label( string $risk ): string {
		switch ( $risk ) {
			case self::LOW:
				return __( 'Low risk', 'wp-debloat' );
			case self::MEDIUM:
				return __( 'Medium risk', 'wp-debloat' );
			case self::HIGH:
				return __( 'High risk', 'wp-debloat' );
			default:
				return $risk;
		}
	}
}
