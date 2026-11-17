<?php
/**
 * Tweak: no Heartbeat API on the front end.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Deregisters the Heartbeat script on the front end.
 */
final class HeartbeatFrontEnd extends Tweak {

	public function id(): string {
		return 'heartbeat_front_end';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Disable the Heartbeat API on the front end', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Stops the Heartbeat script (regular admin-ajax.php requests) on front-end pages. The admin and the editor keep Heartbeat for autosave and post locking. Front-end features of some plugins (live chat, front-end editors) can rely on it.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function apply(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'deregister' ), 100 );
	}

	/**
	 * Remove the script.
	 */
	public function deregister(): void {
		wp_deregister_script( 'heartbeat' );
	}
}
