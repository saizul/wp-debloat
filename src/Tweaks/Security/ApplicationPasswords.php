<?php
/**
 * Tweak: turn off application passwords.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Disables application passwords.
 */
final class ApplicationPasswords extends Tweak {

	public function id(): string {
		return 'application_passwords';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Disable application passwords', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Turns off application passwords, which let external apps log in to the REST API and XML-RPC with a separate password. Existing application passwords stop working. Breaks integrations that use them (some mobile apps, automation services, remote publishing tools).', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function apply(): void {
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
	}
}
