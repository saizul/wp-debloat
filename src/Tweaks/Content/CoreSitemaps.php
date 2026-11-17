<?php
/**
 * Tweak: disable the core XML sitemaps.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Tweaks\Content;

use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Tweak: disable the core XML sitemaps.
 */
final class CoreSitemaps extends Tweak {

	public function id(): string {
		return 'core_sitemaps';
	}

	public function group(): string {
		return Group::CONTENT;
	}

	public function title(): string {
		return __( 'Disable the core XML sitemaps', 'wp-debloat' );
	}

	public function description(): string {
		return __( 'Turns off wp-sitemap.xml. Only switch this on when an SEO plugin provides its own sitemap or you do not want search engines to get one. Not part of any preset.', 'wp-debloat' );
	}

	public function risk(): string {
		return Risk::MEDIUM;
	}

	public function presets(): array {
		return array();
	}

	public function apply(): void {
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
	}
}
