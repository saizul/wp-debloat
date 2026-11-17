<?php
/**
 * Tweak: disable RSS and Atom feeds.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Content;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Redirects every feed to the matching page and removes the feed links.
 */
final class Feeds extends Tweak {

	/**
	 * Feed types WordPress serves.
	 */
	const TYPES = array( 'rdf', 'rss', 'rss2', 'atom', 'rss2_comments', 'atom_comments' );

	public function id(): string {
		return 'feeds';
	}

	public function group(): string {
		return Group::CONTENT;
	}

	public function title(): string {
		return __( 'Disable RSS and Atom feeds', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Redirects every feed URL to the home page and removes all feed links from the page head. Feed readers, podcast apps, newsletter tools and services that import your posts through RSS stop working. Not part of any preset.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function presets(): array {
		return array();
	}

	public function apply(): void {
		foreach ( self::TYPES as $type ) {
			add_action( 'do_feed_' . $type, array( $this, 'block' ), 1 );
		}
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}

	/**
	 * Redirect a feed request.
	 */
	public function block(): void {
		$this->redirect( home_url( '/' ) );
	}
}
