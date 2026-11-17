<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Presets;
use Saizul\Debloat\Registry;
use Saizul\Debloat\Settings;
use Saizul\Debloat\Tests\Unit\Fixtures\CustomTweak;
use Saizul\Debloat\Tests\WpTestCase;
use Saizul\Debloat\Tweak\Risk;

final class PresetsTest extends WpTestCase {

	public function test_recommended_contains_only_low_risk_tweaks(): void {
		$registry = Registry::create();
		$ids      = Presets::ids( Presets::RECOMMENDED, $registry );

		self::assertGreaterThanOrEqual( 20, count( $ids ) );
		foreach ( $ids as $id ) {
			self::assertSame( Risk::LOW, $registry->get( $id )->risk(), $id );
		}
		self::assertContains( 'emojis', $ids );
		self::assertContains( 'xmlrpc_pingbacks', $ids );
		self::assertNotContains( 'xmlrpc_off', $ids );
	}

	public function test_aggressive_is_a_superset_without_high_risk_or_opt_in_tweaks(): void {
		$registry    = Registry::create();
		$recommended = Presets::ids( Presets::RECOMMENDED, $registry );
		$aggressive  = Presets::ids( Presets::AGGRESSIVE, $registry );

		self::assertSame( array(), array_diff( $recommended, $aggressive ) );
		self::assertContains( 'xmlrpc_off', $aggressive );
		self::assertContains( 'comments', $aggressive );
		foreach ( array( 'rest_logged_out_allowlist', 'asset_query_strings', 'search', 'feeds', 'author_archives', 'core_sitemaps' ) as $opt_in ) {
			self::assertNotContains( $opt_in, $aggressive, $opt_in );
		}
		foreach ( $aggressive as $id ) {
			self::assertNotSame( Risk::HIGH, $registry->get( $id )->risk(), $id );
		}
	}

	public function test_off_is_empty(): void {
		self::assertSame( array(), Presets::ids( Presets::OFF, Registry::create() ) );
	}

	public function test_apply_replaces_enabled_and_keeps_values(): void {
		$registry = Registry::create();
		$settings = new Settings( array( 'rest_logged_out_allowlist' ), array( 'revisions_limit' => 9 ) );
		$applied  = Presets::apply( Presets::RECOMMENDED, $settings, $registry );

		self::assertFalse( $applied->is_enabled( 'rest_logged_out_allowlist' ) );
		self::assertTrue( $applied->is_enabled( 'emojis' ) );
		self::assertSame( 9, $applied->value( 'revisions_limit' ) );
		self::assertSame( array(), Presets::apply( Presets::OFF, $applied, $registry )->enabled_ids() );
	}

	public function test_third_party_tweaks_follow_their_risk(): void {
		$registry = new Registry();
		$registry->add( new CustomTweak( 'low_one', 'shop', Risk::LOW ) );
		$registry->add( new CustomTweak( 'medium_one', 'shop', Risk::MEDIUM ) );
		$registry->add( new CustomTweak( 'high_one', 'shop', Risk::HIGH ) );

		self::assertSame( array( 'low_one' ), Presets::ids( Presets::RECOMMENDED, $registry ) );
		self::assertSame( array( 'low_one', 'medium_one' ), Presets::ids( Presets::AGGRESSIVE, $registry ) );
	}

	public function test_unknown_preset_throws(): void {
		$this->expectException( \InvalidArgumentException::class );
		Presets::ids( 'everything', Registry::create() );
	}

	public function test_labels(): void {
		foreach ( Presets::all() as $preset ) {
			self::assertNotSame( '', Presets::label( $preset ) );
			self::assertNotSame( '', Presets::description( $preset ) );
		}
	}
}
