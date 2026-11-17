<?php
/**
 * Registry of all available tweaks.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the tweaks, keyed by ID, and rejects invalid ones.
 */
final class Registry {

	/**
	 * Tweaks by ID.
	 *
	 * @var array<string,Tweak>
	 */
	private $tweaks = array();

	/**
	 * Problems found while registering, by ID or position.
	 *
	 * @var string[]
	 */
	private $rejected = array();

	/**
	 * Build the registry from the built-in list and the `debloat_tweaks` filter.
	 */
	public static function create(): self {
		$tweaks = array();
		foreach ( TweakList::classes() as $class_name ) {
			$tweaks[] = new $class_name();
		}

		/**
		 * Filters the tweaks offered by Debloat & Harden.
		 *
		 * Add an instance of a class that extends \Saizul\Debloat\Tweak\Tweak
		 * (or its class name). Runs on `plugins_loaded`, so register the filter
		 * from a plugin or must-use plugin, not from a theme.
		 *
		 * @param array<int,Tweak|string> $tweaks Tweak instances.
		 */
		$tweaks = apply_filters( 'debloat_tweaks', $tweaks );

		$registry = new self();
		foreach ( is_array( $tweaks ) ? $tweaks : array() as $tweak ) {
			if ( is_string( $tweak ) && class_exists( $tweak ) && is_subclass_of( $tweak, Tweak::class ) ) {
				$tweak = new $tweak();
			}
			$registry->add( $tweak );
		}

		return $registry;
	}

	/**
	 * Register a tweak. Invalid or duplicate tweaks are rejected.
	 *
	 * @param mixed $tweak Tweak instance.
	 * @return bool Whether it was added.
	 */
	public function add( $tweak ): bool {
		if ( ! $tweak instanceof Tweak ) {
			return $this->reject( 'Entry is not an instance of ' . Tweak::class . '.' );
		}
		$id = $tweak->id();
		if ( ! self::is_valid_id( $id ) ) {
			return $this->reject( sprintf( 'Tweak ID "%s" of %s must be 2 to 60 lower case letters, digits or underscores.', $id, get_class( $tweak ) ) );
		}
		if ( isset( $this->tweaks[ $id ] ) ) {
			return $this->reject( sprintf( 'Tweak ID "%s" of %s is already used by %s.', $id, get_class( $tweak ), get_class( $this->tweaks[ $id ] ) ) );
		}
		if ( ! Risk::is_valid( $tweak->risk() ) ) {
			return $this->reject( sprintf( 'Tweak "%s" has an unknown risk level "%s".', $id, $tweak->risk() ) );
		}
		if ( ! self::is_valid_id( $tweak->group() ) ) {
			return $this->reject( sprintf( 'Tweak "%s" has an invalid group "%s".', $id, $tweak->group() ) );
		}
		$this->tweaks[ $id ] = $tweak;

		return true;
	}

	/**
	 * Whether a string can be used as a tweak ID or group key.
	 *
	 * @param string $id ID.
	 */
	public static function is_valid_id( string $id ): bool {
		return 1 === preg_match( '/^[a-z][a-z0-9_]{1,59}$/', $id );
	}

	/**
	 * All tweaks, sorted by group, then in registration order.
	 *
	 * @return array<string,Tweak>
	 */
	public function all(): array {
		$order  = array_flip( array_keys( $this->tweaks ) );
		$tweaks = $this->tweaks;
		uasort(
			$tweaks,
			static function ( Tweak $a, Tweak $b ) use ( $order ) {
				$by_group = Group::position( $a->group() ) <=> Group::position( $b->group() );
				if ( 0 !== $by_group ) {
					return $by_group;
				}
				$by_name = strcmp( $a->group(), $b->group() );

				return 0 !== $by_name ? $by_name : $order[ $a->id() ] <=> $order[ $b->id() ];
			}
		);

		return $tweaks;
	}

	/**
	 * Tweaks per group, in display order.
	 *
	 * @return array<string,array<string,Tweak>>
	 */
	public function by_group(): array {
		$groups = array();
		foreach ( $this->all() as $id => $tweak ) {
			$groups[ $tweak->group() ][ $id ] = $tweak;
		}

		return $groups;
	}

	/**
	 * Tweak by ID.
	 *
	 * @param string $id ID.
	 */
	public function get( string $id ): ?Tweak {
		return $this->tweaks[ $id ] ?? null;
	}

	/**
	 * Whether an ID is registered.
	 *
	 * @param string $id ID.
	 */
	public function has( string $id ): bool {
		return isset( $this->tweaks[ $id ] );
	}

	/**
	 * Registered IDs in display order.
	 *
	 * @return string[]
	 */
	public function ids(): array {
		return array_keys( $this->all() );
	}

	/**
	 * Number of tweaks.
	 */
	public function count(): int {
		return count( $this->tweaks );
	}

	/**
	 * Problems found while registering.
	 *
	 * @return string[]
	 */
	public function rejected(): array {
		return $this->rejected;
	}

	/**
	 * Record a rejected entry and tell developers.
	 *
	 * @param string $message Message.
	 */
	private function reject( string $message ): bool {
		$this->rejected[] = $message;
		if ( function_exists( '_doing_it_wrong' ) ) {
			_doing_it_wrong( 'debloat_tweaks', esc_html( $message ), '0.1.0' );
		}

		return false;
	}
}
