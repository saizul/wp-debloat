<?php
/**
 * Base class for every tweak.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * A tweak is one small, self-contained change to WordPress.
 *
 * To add a tweak, extend this class, fill in the metadata methods and put the
 * hooks in apply(). apply() runs on `plugins_loaded` (priority 20), only when
 * the site owner switched the tweak on, so it must only add or remove hooks
 * and never print output or query the current user directly.
 *
 * Do not call translation functions outside title(), description(),
 * field_label(), choice_label() and notice(): those are only called on the
 * settings screen and in WP-CLI, after `init`.
 */
abstract class Tweak {

	/**
	 * Value of the tweak's option, or null when the default is used.
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Unique ID: lower case letters, digits and underscores, e.g. `emojis`.
	 * Stored in the settings, used by WP-CLI and in import files, so never
	 * change it after a release.
	 */
	abstract public function id(): string;

	/**
	 * One of the Group constants (or a custom group key for third-party tweaks).
	 */
	abstract public function group(): string;

	/**
	 * Short, translated title.
	 */
	abstract public function title(): string;

	/**
	 * Translated description: what it does and what can break.
	 */
	abstract public function description(): string;

	/**
	 * Add or remove hooks. Called once per request when the tweak is on.
	 */
	abstract public function apply(): void;

	/**
	 * One of the Risk constants. Low: no visible change for visitors.
	 * Medium: can break a feature some sites use. High: can break the site
	 * or integrations and needs testing.
	 */
	public function risk(): string {
		return Risk::LOW;
	}

	/**
	 * Presets that switch this tweak on. Defaults follow the risk level:
	 * low risk tweaks are in "recommended" and "aggressive", medium risk
	 * tweaks only in "aggressive", high risk tweaks in none.
	 *
	 * @return string[]
	 */
	public function presets(): array {
		switch ( $this->risk() ) {
			case Risk::LOW:
				return array( 'recommended', 'aggressive' );
			case Risk::MEDIUM:
				return array( 'aggressive' );
			default:
				return array();
		}
	}

	/**
	 * Optional value the site owner can set (a number, a choice or a list).
	 */
	public function field(): ?Field {
		return null;
	}

	/**
	 * Translated label of the field.
	 */
	public function field_label(): string {
		return '';
	}

	/**
	 * Translated label of a choice of a select field.
	 *
	 * @param string $choice Choice key.
	 */
	public function choice_label( string $choice ): string {
		return $choice;
	}

	/**
	 * Optional translated hint shown on the settings screen while the tweak
	 * is on, e.g. a wp-config.php constant that would be stronger.
	 */
	public function notice(): string {
		return '';
	}

	/**
	 * Whether the request is a normal front-end page view.
	 */
	protected function is_front_end(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		return ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! ( defined( 'WP_CLI' ) && WP_CLI );
	}

	/**
	 * Redirect to a URL on this site and stop.
	 *
	 * @param string $url    Target URL.
	 * @param int    $status HTTP status.
	 */
	protected function redirect( string $url, int $status = 301 ): void {
		wp_safe_redirect( $url, $status, 'Debloat & Harden' );
		exit;
	}

	/**
	 * Set the configured value (already sanitised by the field).
	 *
	 * @param mixed $value Value, or null for the default.
	 */
	final public function set_value( $value ): void {
		$this->value = $value;
	}

	/**
	 * Configured value or the field default.
	 *
	 * @return mixed
	 */
	final public function value() {
		if ( null !== $this->value ) {
			return $this->value;
		}
		$field = $this->field();

		return $field ? $field->default_value() : null;
	}
}
