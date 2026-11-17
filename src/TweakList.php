<?php
/**
 * List of built-in tweaks.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat;

use Saizul\Debloat\Tweaks\Admin;
use Saizul\Debloat\Tweaks\Content;
use Saizul\Debloat\Tweaks\Performance;
use Saizul\Debloat\Tweaks\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Every built-in tweak, in display order within its group.
 *
 * Adding a tweak: create the class in src/Tweaks/<Group>/, add it to this
 * list, add a test, run `composer readme` and open a pull request.
 */
final class TweakList {

	/**
	 * Class names.
	 *
	 * @return array<int,class-string<Tweak\Tweak>>
	 */
	public static function classes(): array {
		return array(
			// Performance.
			Performance\Emojis::class,
			Performance\Embeds::class,
			Performance\JqueryMigrate::class,
			Performance\Dashicons::class,
			Performance\BlockLibraryCss::class,
			Performance\GlobalStyles::class,
			Performance\HeartbeatFrontEnd::class,
			Performance\HeartbeatInterval::class,
			Performance\SelfPingbacks::class,
			Performance\RevisionsLimit::class,
			Performance\AutosaveInterval::class,
			Performance\AssetQueryStrings::class,
			Performance\RsdLink::class,
			Performance\WlwManifest::class,
			Performance\Shortlink::class,
			Performance\RestApiLink::class,
			Performance\FeedLinksExtra::class,
			Performance\RemoteBlockPatterns::class,

			// Security and hardening.
			Security\XmlrpcOff::class,
			Security\XmlrpcPingbacks::class,
			Security\RestUsers::class,
			Security\AuthorQuery::class,
			Security\SitemapUsers::class,
			Security\HideVersion::class,
			Security\FileEditor::class,
			Security\LoginErrors::class,
			Security\ApplicationPasswords::class,
			Security\SecurityHeaders::class,
			Security\RestAllowlist::class,

			// Content.
			Content\Comments::class,
			Content\AttachmentPages::class,
			Content\AuthorArchives::class,
			Content\Search::class,
			Content\Feeds::class,
			Content\CoreSitemaps::class,

			// Admin.
			Admin\DashboardWidgets::class,
			Admin\DashboardNews::class,
			Admin\AdminBarFrontEnd::class,
			Admin\UpdateNags::class,
			Admin\BlockDirectory::class,
			Admin\AdminEmailCheck::class,
		);
	}
}
