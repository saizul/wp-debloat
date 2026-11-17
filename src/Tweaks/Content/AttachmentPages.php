<?php
/**
 * Tweak: redirect attachment pages.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Content;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Sends attachment pages to the parent post, or to the file itself.
 */
final class AttachmentPages extends Tweak {

	public function id(): string {
		return 'attachment_pages';
	}

	public function group(): string {
		return Group::CONTENT;
	}

	public function title(): string {
		return __( 'Redirect attachment pages', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Redirects the thin attachment pages WordPress creates for every upload to the post the file is attached to, or to the file itself when it is not attached or the parent is not public. Also switches the "attachment pages" option of WordPress 6.4+ off.', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'pre_option_wp_attachment_pages_enabled', array( $this, 'option' ) );
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 1 );
	}

	/**
	 * Report the option as off.
	 *
	 * @return string
	 */
	public function option() {
		return '0';
	}

	/**
	 * Redirect.
	 */
	public function maybe_redirect(): void {
		if ( ! is_attachment() ) {
			return;
		}
		$url = $this->target( (int) get_queried_object_id() );
		if ( '' !== $url ) {
			$this->redirect( $url );
		}
	}

	/**
	 * Where an attachment should go.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function target( int $attachment_id ): string {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return '';
		}
		$parent = (int) $attachment->post_parent;
		if ( $parent > 0 && 'publish' === get_post_status( $parent ) ) {
			$link = get_permalink( $parent );
			if ( $link ) {
				return (string) $link;
			}
		}
		$file = wp_get_attachment_url( $attachment_id );

		return $file ? (string) $file : '';
	}
}
