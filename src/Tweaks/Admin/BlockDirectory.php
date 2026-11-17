<?php
/**
 * Tweak: disable the block directory.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Admin;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: disable the block directory.
 */
final class BlockDirectory extends Tweak {

	public function id(): string {
		return 'block_directory';
	}

	public function group(): string {
		return Group::ADMIN;
	}

	public function title(): string {
		return __( 'Disable the block directory in the editor', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Stops the block editor from suggesting and installing blocks from WordPress.org when a search in the inserter finds nothing. Blocks can still be installed as plugins.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	}
}
