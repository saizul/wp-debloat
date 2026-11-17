<?php
/**
 * Tweak: remove the RSD link.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: remove the RSD link.
 */
final class RsdLink extends Tweak {

	public function id(): string {
		return 'rsd_link';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove the RSD link', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the Really Simple Discovery link from the page head. It only helps old blog editors find the XML-RPC endpoint.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'wp_head', 'rsd_link' );
	}
}
