<?php
/**
 * Settings sanitiser.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Settings;

use Saizul\Debloat\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Validates settings from any source against the registry.
 *
 * Accepts `enabled` as a list of IDs (`array( 'emojis', 'rsd_link' )`) or as a
 * map of ID to a truthy value (`array( 'emojis' => '1' )`, as a form posts it).
 * Unknown IDs and values of tweaks without a field are dropped. Values are
 * passed through the tweak's Field.
 */
final class Sanitizer {

	/**
	 * Registry.
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * IDs found in the input that are not registered.
	 *
	 * @var string[]
	 */
	private $unknown = array();

	/**
	 * Constructor.
	 *
	 * @param Registry $registry Registry.
	 */
	public function __construct( Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Sanitise.
	 *
	 * @param mixed $raw Raw settings.
	 * @return array{enabled:string[],values:array<string,mixed>}
	 */
	public function sanitize( $raw ): array {
		$this->unknown = array();
		$raw           = is_array( $raw ) ? $raw : array();

		return array(
			'enabled' => $this->enabled( $raw['enabled'] ?? array() ),
			'values'  => $this->values( $raw['values'] ?? array() ),
		);
	}

	/**
	 * Unknown IDs seen by the last sanitize() call.
	 *
	 * @return string[]
	 */
	public function unknown(): array {
		return array_values( array_unique( $this->unknown ) );
	}

	/**
	 * Sanitise the enabled list.
	 *
	 * @param mixed $raw Raw list or map.
	 * @return string[]
	 */
	private function enabled( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$is_list = array_keys( $raw ) === range( 0, count( $raw ) - 1 );
		$ids     = array();
		foreach ( $raw as $key => $item ) {
			if ( $is_list ) {
				$id = is_string( $item ) ? $item : '';
			} else {
				if ( ! self::truthy( $item ) ) {
					continue;
				}
				$id = (string) $key;
			}
			$id = strtolower( trim( $id ) );
			if ( '' === $id ) {
				continue;
			}
			if ( ! $this->registry->has( $id ) ) {
				$this->unknown[] = $id;
				continue;
			}
			$ids[ $id ] = $id;
		}

		// Keep registry order so stored settings are stable.
		return array_values( array_intersect( $this->registry->ids(), $ids ) );
	}

	/**
	 * Sanitise the values.
	 *
	 * @param mixed $raw Raw map of ID to value.
	 * @return array<string,mixed>
	 */
	private function values( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$values = array();
		foreach ( $raw as $id => $value ) {
			$id    = strtolower( (string) $id );
			$tweak = $this->registry->get( $id );
			if ( ! $tweak ) {
				$this->unknown[] = $id;
				continue;
			}
			$field = $tweak->field();
			if ( ! $field ) {
				continue;
			}
			$values[ $id ] = $field->sanitize( $value );
		}
		ksort( $values );

		return $values;
	}

	/**
	 * Truthy form or JSON value.
	 *
	 * @param mixed $value Value.
	 */
	private static function truthy( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) || is_float( $value ) ) {
			return 0 != $value; // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- 1 and 1.0 alike.
		}

		return is_string( $value ) && in_array( strtolower( trim( $value ) ), array( '1', 'on', 'yes', 'true' ), true );
	}
}
