<?php
/**
 * Optional value of a tweak.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Describes and sanitises the single value a tweak can take.
 *
 * Holds no translated text, so it can be used before `init`. Labels come from
 * Tweak::field_label() and Tweak::choice_label().
 */
final class Field {

	const TYPE_INT    = 'int';
	const TYPE_SELECT = 'select';
	const TYPE_LINES  = 'lines';

	/**
	 * Field type.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Default value.
	 *
	 * @var mixed
	 */
	private $default;

	/**
	 * Type options: min, max, unit, choices, pattern.
	 *
	 * @var array<string,mixed>
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param string              $type    Field type.
	 * @param mixed               $default_value Default value.
	 * @param array<string,mixed> $options Type options.
	 */
	private function __construct( string $type, $default_value, array $options ) {
		$this->type    = $type;
		$this->default = $default_value;
		$this->options = $options;
	}

	/**
	 * Whole number between $min and $max.
	 *
	 * @param int    $default_value Default.
	 * @param int    $min     Minimum.
	 * @param int    $max     Maximum.
	 * @param string $unit    Unit shown after the input (not translated, e.g. "s").
	 */
	public static function int( int $default_value, int $min, int $max, string $unit = '' ): self {
		return new self(
			self::TYPE_INT,
			$default_value,
			array(
				'min'  => $min,
				'max'  => $max,
				'unit' => $unit,
			)
		);
	}

	/**
	 * One of a fixed set of keys.
	 *
	 * @param string   $default_value Default key.
	 * @param string[] $choices Allowed keys.
	 */
	public static function select( string $default_value, array $choices ): self {
		return new self( self::TYPE_SELECT, $default_value, array( 'choices' => array_values( $choices ) ) );
	}

	/**
	 * List of short strings, one per line, each matching $pattern.
	 *
	 * @param string[] $default_value Default lines.
	 * @param string   $pattern Regular expression every line must match.
	 * @param int      $max     Maximum number of lines.
	 */
	public static function lines( array $default_value, string $pattern = '#^[\w\-./]+$#', int $max = 100 ): self {
		return new self(
			self::TYPE_LINES,
			array_values( $default_value ),
			array(
				'pattern' => $pattern,
				'max'     => $max,
			)
		);
	}

	/**
	 * Field type.
	 */
	public function type(): string {
		return $this->type;
	}

	/**
	 * Default value.
	 *
	 * @return mixed
	 */
	public function default_value() {
		return $this->default;
	}

	/**
	 * Type option.
	 *
	 * @param string $name     Option name.
	 * @param mixed  $fallback Value when unset.
	 * @return mixed
	 */
	public function option( string $name, $fallback = null ) {
		return array_key_exists( $name, $this->options ) ? $this->options[ $name ] : $fallback;
	}

	/**
	 * Allowed choices of a select field.
	 *
	 * @return string[]
	 */
	public function choices(): array {
		return (array) $this->option( 'choices', array() );
	}

	/**
	 * Turn raw input (form, JSON import or CLI) into a valid value.
	 * Invalid input gives the default, numbers are clamped.
	 *
	 * @param mixed $raw Raw value.
	 * @return mixed
	 */
	public function sanitize( $raw ) {
		switch ( $this->type ) {
			case self::TYPE_INT:
				if ( is_bool( $raw ) || ! is_scalar( $raw ) || ! is_numeric( trim( (string) $raw ) ) ) {
					return $this->default;
				}
				$number = (int) round( (float) $raw );

				return max( (int) $this->option( 'min' ), min( (int) $this->option( 'max' ), $number ) );

			case self::TYPE_SELECT:
				$raw = is_scalar( $raw ) ? (string) $raw : '';

				return in_array( $raw, $this->choices(), true ) ? $raw : $this->default;

			case self::TYPE_LINES:
				if ( is_string( $raw ) ) {
					$raw = preg_split( '/[\r\n,]+/', $raw );
				}
				if ( ! is_array( $raw ) ) {
					return $this->default;
				}
				$clean = array();
				foreach ( $raw as $line ) {
					if ( ! is_scalar( $line ) ) {
						continue;
					}
					$line = trim( (string) $line );
					if ( '' === $line || strlen( $line ) > 200 || ! preg_match( (string) $this->option( 'pattern' ), $line ) ) {
						continue;
					}
					$clean[ $line ] = $line;
				}

				return array_slice( array_values( $clean ), 0, (int) $this->option( 'max' ) );
		}

		return $this->default;
	}

	/**
	 * Value as text, for WP-CLI and the README.
	 *
	 * @param mixed $value Value.
	 */
	public static function format( $value ): string {
		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return (string) $value;
	}
}
