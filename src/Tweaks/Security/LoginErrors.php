<?php
/**
 * Tweak: generic login error message.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces login errors that reveal whether a user exists.
 */
final class LoginErrors extends Tweak {

	public function id(): string {
		return 'login_errors';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Generic login error message', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Shows one generic message on the login screen instead of telling visitors whether the user name or the password was wrong, so bots cannot confirm that an account exists.', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'login_errors', array( $this, 'message' ), 99 );
	}

	/**
	 * Generic message.
	 *
	 * @param mixed $errors Error HTML.
	 * @return mixed
	 */
	public function message( $errors ) {
		if ( ! is_string( $errors ) || '' === trim( $errors ) ) {
			return $errors;
		}

		return '<strong>' . esc_html__( 'Error:', 'wp-debloat' ) . '</strong> ' . esc_html__( 'The login details are incorrect. Please try again.', 'wp-debloat' );
	}
}
