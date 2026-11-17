<?php
/**
 * Tweak: redirect ?author= enumeration.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Redirects /?author=N requests to the home page.
 */
final class AuthorQuery extends Tweak {

	public function id(): string {
		return 'author_query_enumeration';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Block ?author=N user enumeration', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Redirects requests such as /?author=1 to the home page before WordPress turns them into /author/user-name/, so bots cannot read user names by counting IDs. Author archive links themselves keep working.', 'wp-debloat' );
	}

	public function apply(): void {
		// Before redirect_canonical() at priority 10.
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 1 );
	}

	/**
	 * Redirect when the query has a numeric author.
	 */
	public function maybe_redirect(): void {
		if ( is_admin() || ! isset( $_GET['author'] ) || current_user_can( 'list_users' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check.
			return;
		}
		$author = wp_unslash( $_GET['author'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only tested for digits.
		if ( is_string( $author ) && preg_match( '/\d/', $author ) ) {
			$this->redirect( home_url( '/' ) );
		}
	}
}
