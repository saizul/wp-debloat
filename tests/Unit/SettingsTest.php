<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Registry;
use Saizul\Debloat\Settings;
use Saizul\Debloat\Tests\WpTestCase;
use WpStubs;

final class SettingsTest extends WpTestCase {

	public function test_everything_is_off_by_default(): void {
		$settings = Settings::load( Registry::create() );

		self::assertSame( array(), $settings->enabled_ids() );
		self::assertNull( $settings->value( 'revisions_limit' ) );
	}

	public function test_save_and_load_round_trip(): void {
		$registry = Registry::create();
		( new Settings( array( 'rsd_link', 'emojis' ), array( 'revisions_limit' => 3 ) ) )->save();

		self::assertSame(
			array(
				'version' => 1,
				'enabled' => array( 'emojis', 'rsd_link' ),
				'values'  => array( 'revisions_limit' => 3 ),
			),
			WpStubs::$options[ Settings::OPTION ]
		);

		$loaded = Settings::load( $registry );
		self::assertTrue( $loaded->is_enabled( 'emojis' ) );
		self::assertFalse( $loaded->is_enabled( 'embeds' ) );
		self::assertSame( 3, $loaded->value( 'revisions_limit' ) );
	}

	public function test_stored_option_is_sanitised_on_load(): void {
		WpStubs::$options[ Settings::OPTION ] = array(
			'enabled' => array( 'emojis', 'removed_in_a_later_version' ),
			'values'  => array( 'revisions_limit' => -5 ),
		);
		$settings = Settings::load( Registry::create() );

		self::assertSame( array( 'emojis' ), $settings->enabled_ids() );
		self::assertSame( 0, $settings->value( 'revisions_limit' ) );
	}

	public function test_with_methods_return_copies(): void {
		$settings = new Settings( array( 'emojis' ) );
		$on       = $settings->with_enabled( 'rsd_link', true );
		$off      = $on->with_enabled( 'emojis', false );
		$valued   = $off->with_value( 'revisions_limit', 2 );

		self::assertSame( array( 'emojis' ), $settings->enabled_ids() );
		self::assertSame( array( 'emojis', 'rsd_link' ), $on->enabled_ids() );
		self::assertSame( array( 'rsd_link' ), $off->enabled_ids() );
		self::assertNull( $off->value( 'revisions_limit' ) );
		self::assertSame( 2, $valued->value( 'revisions_limit' ) );
		self::assertSame( array( 'shortlink' ), $valued->with_only( array( 'shortlink' ) )->enabled_ids() );
		self::assertSame( 2, $valued->with_only( array() )->value( 'revisions_limit' ), 'Values survive.' );
	}
}
