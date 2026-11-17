<?php
/**
 * Presets.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat;

defined( 'ABSPATH' ) || exit;

/**
 * Named sets of tweaks. Each tweak lists the presets it belongs to in
 * Tweak::presets(), so a new tweak never needs changes here.
 */
final class Presets {

	const RECOMMENDED = 'recommended';
	const AGGRESSIVE  = 'aggressive';
	const OFF         = 'off';

	/**
	 * Preset keys.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array( self::RECOMMENDED, self::AGGRESSIVE, self::OFF );
	}

	/**
	 * Whether a preset exists.
	 *
	 * @param string $preset Preset key.
	 */
	public static function exists( string $preset ): bool {
		return in_array( $preset, self::all(), true );
	}

	/**
	 * Translated label.
	 *
	 * @param string $preset Preset key.
	 */
	public static function label( string $preset ): string {
		switch ( $preset ) {
			case self::RECOMMENDED:
				return __( 'Recommended', 'wp-debloat' );
			case self::AGGRESSIVE:
				return __( 'Aggressive', 'wp-debloat' );
			case self::OFF:
				return __( 'Off', 'wp-debloat' );
			default:
				return $preset;
		}
	}

	/**
	 * Translated description.
	 *
	 * @param string $preset Preset key.
	 */
	public static function description( string $preset ): string {
		switch ( $preset ) {
			case self::RECOMMENDED:
				return __( 'Low-risk tweaks only. Safe for almost every site.', 'wp-debloat' );
			case self::AGGRESSIVE:
				return __( 'Low and medium risk tweaks. Test your site afterwards.', 'wp-debloat' );
			case self::OFF:
				return __( 'Switch every tweak off.', 'wp-debloat' );
			default:
				return '';
		}
	}

	/**
	 * IDs switched on by a preset, in registry order.
	 *
	 * @param string   $preset   Preset key.
	 * @param Registry $registry Registry.
	 * @return string[]
	 * @throws \InvalidArgumentException For an unknown preset.
	 */
	public static function ids( string $preset, Registry $registry ): array {
		if ( ! self::exists( $preset ) ) {
			throw new \InvalidArgumentException( sprintf( 'Unknown preset "%s". Use one of: %s.', $preset, implode( ', ', self::all() ) ) );
		}
		if ( self::OFF === $preset ) {
			return array();
		}
		$ids = array();
		foreach ( $registry->all() as $id => $tweak ) {
			if ( in_array( $preset, $tweak->presets(), true ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Settings with exactly the preset's tweaks on. Values are kept.
	 *
	 * @param string   $preset   Preset key.
	 * @param Settings $settings Current settings.
	 * @param Registry $registry Registry.
	 */
	public static function apply( string $preset, Settings $settings, Registry $registry ): Settings {
		return $settings->with_only( self::ids( $preset, $registry ) );
	}
}
