<?php
/**
 * Tweak: disable author archives.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Content;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Turns author archives into a 404 or a redirect to the home page.
 */
final class AuthorArchives extends Tweak {

	public function id(): string {
		return 'author_archives';
	}

	public function group(): string {
		return Group::CONTENT;
	}

	public function title(): string {
		return __( 'Disable author archives', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Author archive pages (/author/name/) return a 404 or redirect to the home page, and author links point to the home page. Useful for single-author sites, where the author archive duplicates the blog. Not part of any preset.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function presets(): array {
		return array();
	}

	public function field(): ?Field {
		return Field::select( '404', array( '404', 'home' ) );
	}

	public function field_label(): string {
		return __( 'Author archives', 'wp-debloat' );
	}

	public function choice_label( string $choice ): string {
		return 'home' === $choice ? __( 'Redirect to the home page', 'wp-debloat' ) : __( 'Show a 404 page', 'wp-debloat' );
	}

	public function apply(): void {
		add_action( 'template_redirect', array( $this, 'handle' ), 1 );
		add_filter( 'author_link', array( $this, 'author_link' ), 99 );
	}

	/**
	 * Handle author archive requests.
	 */
	public function handle(): void {
		if ( ! is_author() ) {
			return;
		}
		if ( 'home' === $this->value() ) {
			$this->redirect( home_url( '/' ) );
			return;
		}
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Point author links to the home page.
	 *
	 * @return string
	 */
	public function author_link() {
		return home_url( '/' );
	}
}
