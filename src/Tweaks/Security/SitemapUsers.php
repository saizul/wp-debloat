<?php
/**
 * Tweak: remove users from the core XML sitemap.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Security;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Removes the users provider from wp-sitemap.xml.
 */
final class SitemapUsers extends Tweak {

	public function id(): string {
		return 'sitemap_users';
	}

	public function group(): string {
		return Group::SECURITY;
	}

	public function title(): string {
		return __( 'Remove users from the core XML sitemap', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Removes the wp-sitemap-users-1.xml sitemap, which lists the author archive URL (and so the user name) of everyone who published a post. Posts, pages and terms stay in the sitemap. Has no effect when an SEO plugin replaces the core sitemap.', 'wp-debloat' );
	}

	public function apply(): void {
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'provider' ), 10, 2 );
	}

	/**
	 * Drop the users provider.
	 *
	 * @param mixed  $provider Provider.
	 * @param string $name     Provider name.
	 * @return mixed
	 */
	public function provider( $provider, $name = '' ) {
		return 'users' === $name ? false : $provider;
	}
}
