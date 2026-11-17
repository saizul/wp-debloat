<?php
/**
 * Tweak: remove the REST API discovery link.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: remove the REST API discovery link.
 */
final class RestApiLink extends Tweak {

	public function id(): string {
		return 'rest_api_link';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove the REST API discovery link', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the api.w.org link from the page head and the Link HTTP header. The REST API itself keeps working; only the advertisement is removed.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	}
}
