<?php
/**
 * Applies the enabled tweaks.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat;

defined( 'ABSPATH' ) || exit;

/**
 * Calls apply() on every enabled tweak, once per request.
 */
final class Runner {

	/**
	 * Applied IDs.
	 *
	 * @var string[]
	 */
	private $applied = array();

	/**
	 * Skipped IDs (by `debloat_skip_tweak`).
	 *
	 * @var string[]
	 */
	private $skipped = array();

	/**
	 * Errors by ID.
	 *
	 * @var array<string,string>
	 */
	private $errors = array();

	/**
	 * Whether WP_DEBLOAT_DISABLE is set to a truthy value.
	 */
	public static function safe_mode(): bool {
		return defined( 'WP_DEBLOAT_DISABLE' ) && (bool) constant( 'WP_DEBLOAT_DISABLE' );
	}

	/**
	 * Apply the enabled tweaks.
	 *
	 * @param Registry $registry Registry.
	 * @param Settings $settings Settings.
	 */
	public function run( Registry $registry, Settings $settings ): void {
		if ( self::safe_mode() ) {
			return;
		}
		foreach ( $registry->all() as $id => $tweak ) {
			if ( ! $settings->is_enabled( $id ) || in_array( $id, $this->applied, true ) ) {
				continue;
			}

			/**
			 * Filters whether an enabled tweak is skipped on this request.
			 *
			 * @param bool                         $skip  Whether to skip. Default false.
			 * @param string                       $id    Tweak ID.
			 * @param \Saizul\Debloat\Tweak\Tweak $tweak Tweak.
			 */
			if ( apply_filters( 'debloat_skip_tweak', false, $id, $tweak ) ) {
				$this->skipped[] = $id;
				continue;
			}
			$tweak->set_value( $settings->value( $id ) );
			try {
				$tweak->apply();
				$this->applied[] = $id;
			} catch ( \Throwable $error ) {
				// One broken tweak must not take the site down.
				$this->errors[ $id ] = $error->getMessage();
			}
		}
	}

	/**
	 * Applied IDs.
	 *
	 * @return string[]
	 */
	public function applied(): array {
		return $this->applied;
	}

	/**
	 * Skipped IDs.
	 *
	 * @return string[]
	 */
	public function skipped(): array {
		return $this->skipped;
	}

	/**
	 * Errors by ID.
	 *
	 * @return array<string,string>
	 */
	public function errors(): array {
		return $this->errors;
	}
}
