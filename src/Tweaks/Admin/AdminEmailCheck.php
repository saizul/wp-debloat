<?php
/**
 * Tweak: disable the admin e-mail check.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Admin;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: disable the admin e-mail check.
 */
final class AdminEmailCheck extends Tweak {

	public function id(): string {
		return 'admin_email_check';
	}

	public function group(): string {
		return Group::ADMIN;
	}

	public function title(): string {
		return __( 'Disable the admin e-mail confirmation screen', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Stops WordPress from asking administrators every six months, right after login, to confirm that the site admin e-mail address is still correct.', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'admin_email_check_interval', '__return_zero' );
	}
}
