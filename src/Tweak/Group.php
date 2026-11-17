<?php
/**
 * Tweak groups.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Groups shown as sections on the settings screen.
 */
final class Group {

	const PERFORMANCE = 'performance';
	const SECURITY    = 'security';
	const CONTENT     = 'content';
	const ADMIN       = 'admin';

	/**
	 * Built-in groups in display order.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array( self::PERFORMANCE, self::SECURITY, self::CONTENT, self::ADMIN );
	}

	/**
	 * Sort position of a group; unknown (third-party) groups go last.
	 *
	 * @param string $group Group key.
	 */
	public static function position( string $group ): int {
		$index = array_search( $group, self::all(), true );

		return false === $index ? count( self::all() ) : (int) $index;
	}

	/**
	 * Translated label.
	 *
	 * @param string $group Group key.
	 */
	public static function label( string $group ): string {
		switch ( $group ) {
			case self::PERFORMANCE:
				return __( 'Performance', 'wp-debloat' );
			case self::SECURITY:
				return __( 'Security and hardening', 'wp-debloat' );
			case self::CONTENT:
				return __( 'Content', 'wp-debloat' );
			case self::ADMIN:
				return __( 'Admin', 'wp-debloat' );
			default:
				return ucwords( str_replace( array( '_', '-' ), ' ', $group ) );
		}
	}

	/**
	 * English label without translation, for the README table.
	 *
	 * @param string $group Group key.
	 */
	public static function plain_label( string $group ): string {
		$labels = array(
			self::PERFORMANCE => 'Performance',
			self::SECURITY    => 'Security and hardening',
			self::CONTENT     => 'Content',
			self::ADMIN       => 'Admin',
		);

		return $labels[ $group ] ?? ucwords( str_replace( array( '_', '-' ), ' ', $group ) );
	}
}
