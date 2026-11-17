<?php
/**
 * Tweak: remove the extra feed links.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Performance;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: remove the extra feed links.
 */
final class FeedLinksExtra extends Tweak {

	public function id(): string {
		return 'feed_links_extra';
	}

	public function group(): string {
		return Group::PERFORMANCE;
	}

	public function title(): string {
		return __( 'Remove comment and archive feed links', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the head links to the comments feed and to category, tag, author and search feeds. The main posts feed link stays and all feeds keep working.', 'wp-debloat' );
	}

	public function apply(): void {
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
	}
}
