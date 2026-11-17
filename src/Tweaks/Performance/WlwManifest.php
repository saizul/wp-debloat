<?php
/**
 * Tweak: remove the Windows Live Writer manifest link.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: remove the Windows Live Writer manifest link.
 */
final class WlwManifest extends Tweak {

	public function id(): string {
		return 'wlw_manifest';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove the Windows Live Writer manifest link', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the wlwmanifest link for the discontinued Windows Live Writer editor. WordPress 6.3 and later no longer print it; the tweak is kept for older versions.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}
}
