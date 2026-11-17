<?php
/**
 * Stored settings.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat;

use Saizul\Debloat\Settings\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Which tweaks are on and their values. Every tweak is off by default.
 *
 * Stored in one option:
 * `array( 'version' => 1, 'enabled' => string[], 'values' => array<string,mixed> )`.
 */
final class Settings {

	const OPTION = 'wp_debloat_settings';
	const SCHEMA = 1;

	/**
	 * Enabled IDs (keys) for fast look-up.
	 *
	 * @var array<string,true>
	 */
	private $enabled = array();

	/**
	 * Values by tweak ID.
	 *
	 * @var array<string,mixed>
	 */
	private $values = array();

	/**
	 * Constructor.
	 *
	 * @param string[]            $enabled Enabled IDs.
	 * @param array<string,mixed> $values  Values by ID.
	 */
	public function __construct( array $enabled = array(), array $values = array() ) {
		foreach ( $enabled as $id ) {
			$this->enabled[ (string) $id ] = true;
		}
		$this->values = $values;
	}

	/**
	 * Load and sanitise the stored settings.
	 *
	 * @param Registry $registry Registry.
	 */
	public static function load( Registry $registry ): self {
		return self::from_array( get_option( self::OPTION, array() ), $registry );
	}

	/**
	 * Build from raw data (stored option, form or import), dropping anything
	 * that does not match a registered tweak.
	 *
	 * @param mixed    $raw      Raw data.
	 * @param Registry $registry Registry.
	 */
	public static function from_array( $raw, Registry $registry ): self {
		$clean = ( new Sanitizer( $registry ) )->sanitize( $raw );

		return new self( $clean['enabled'], $clean['values'] );
	}

	/**
	 * Store the settings.
	 */
	public function save(): bool {
		return (bool) update_option( self::OPTION, $this->to_array(), true );
	}

	/**
	 * Settings as stored.
	 *
	 * @return array{version:int,enabled:string[],values:array<string,mixed>}
	 */
	public function to_array(): array {
		$enabled = array_keys( $this->enabled );
		sort( $enabled );
		$values = $this->values;
		ksort( $values );

		return array(
			'version' => self::SCHEMA,
			'enabled' => $enabled,
			'values'  => $values,
		);
	}

	/**
	 * Whether a tweak is on.
	 *
	 * @param string $id Tweak ID.
	 */
	public function is_enabled( string $id ): bool {
		return isset( $this->enabled[ $id ] );
	}

	/**
	 * Enabled IDs.
	 *
	 * @return string[]
	 */
	public function enabled_ids(): array {
		return array_keys( $this->enabled );
	}

	/**
	 * Copy with a tweak switched on or off.
	 *
	 * @param string $id Tweak ID.
	 * @param bool   $on On or off.
	 */
	public function with_enabled( string $id, bool $on ): self {
		$copy = clone $this;
		if ( $on ) {
			$copy->enabled[ $id ] = true;
		} else {
			unset( $copy->enabled[ $id ] );
		}

		return $copy;
	}

	/**
	 * Copy with exactly these tweaks on; values are kept.
	 *
	 * @param string[] $ids Tweak IDs.
	 */
	public function with_only( array $ids ): self {
		return new self( $ids, $this->values );
	}

	/**
	 * Stored value of a tweak, or null.
	 *
	 * @param string $id Tweak ID.
	 * @return mixed
	 */
	public function value( string $id ) {
		return $this->values[ $id ] ?? null;
	}

	/**
	 * All stored values.
	 *
	 * @return array<string,mixed>
	 */
	public function values(): array {
		return $this->values;
	}

	/**
	 * Copy with a value set (pass a sanitised value).
	 *
	 * @param string $id    Tweak ID.
	 * @param mixed  $value Value.
	 */
	public function with_value( string $id, $value ): self {
		$copy                = clone $this;
		$copy->values[ $id ] = $value;

		return $copy;
	}
}
