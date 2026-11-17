<?php
/**
 * Tweak: do not load patterns from WordPress.org.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: do not load patterns from WordPress.org.
 */
final class RemoteBlockPatterns extends Tweak {

	public function id(): string {
		return 'remote_block_patterns';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Do not load remote block patterns', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Stops the block editor from downloading core and featured patterns from the WordPress.org pattern directory. Patterns registered by your theme and plugins are still available.', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
	}
}
