<?php
/**
 * Tweak: limit post revisions.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Caps the number of revisions kept per post.
 */
final class RevisionsLimit extends Tweak {

	public function id(): string {
		return 'revisions_limit';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Limit post revisions', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Keeps at most this many revisions per post instead of an unlimited number, which keeps the posts table small. Older revisions are removed the next time a post is saved. A lower WP_POST_REVISIONS value in wp-config.php is respected; 0 turns revisions off.', 'wp-debloat' );
	}

	public function field(): ?Field {
		return Field::int( 5, 0, 100 );
	}

	public function field_label(): string {
		return __( 'Revisions to keep', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'wp_revisions_to_keep', array( $this, 'limit' ), 20 );
	}

	/**
	 * Limit.
	 *
	 * @param mixed $num Revisions WordPress would keep (-1 is unlimited).
	 */
	public function limit( $num ): int {
		$num   = (int) $num;
		$limit = (int) $this->value();

		return $num < 0 ? $limit : min( $num, $limit );
	}
}
