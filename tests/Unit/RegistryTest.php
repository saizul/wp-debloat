<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Registry;
use Saizul\Debloat\Tests\Unit\Fixtures\CustomTweak;
use Saizul\Debloat\Tests\WpTestCase;
use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Risk;
use Saizul\Debloat\Tweak\Tweak;
use Saizul\Debloat\TweakList;
use WpStubs;

final class RegistryTest extends WpTestCase {

	public function test_built_in_list_has_at_least_thirty_valid_tweaks(): void {
		$registry = Registry::create();

		self::assertGreaterThanOrEqual( 30, $registry->count() );
		self::assertCount( count( TweakList::classes() ), $registry->ids(), 'Every listed class registers.' );
		self::assertSame( array(), $registry->rejected() );
	}

	public function test_every_built_in_tweak_has_complete_metadata(): void {
		foreach ( Registry::create()->all() as $id => $tweak ) {
			self::assertSame( $id, $tweak->id() );
			self::assertTrue( Registry::is_valid_id( $id ), $id );
			self::assertContains( $tweak->group(), Group::all(), $id );
			self::assertTrue( Risk::is_valid( $tweak->risk() ), $id );
			self::assertNotSame( '', trim( $tweak->title() ), $id );
			self::assertGreaterThan( 40, strlen( $tweak->description() ), $id . ' needs a useful description.' );
			self::assertStringEndsWith( '.', $tweak->description(), $id );
			foreach ( $tweak->presets() as $preset ) {
				self::assertContains( $preset, array( 'recommended', 'aggressive' ), $id );
			}
			if ( Risk::HIGH === $tweak->risk() ) {
				self::assertSame( array(), $tweak->presets(), 'High risk tweaks are never in a preset: ' . $id );
			}
			$field = $tweak->field();
			if ( $field ) {
				self::assertNotSame( '', $tweak->field_label(), $id );
				self::assertSame( $field->default_value(), $field->sanitize( $field->default_value() ), 'Default survives sanitising: ' . $id );
			}
		}
	}

	public function test_every_group_has_tweaks(): void {
		$groups = Registry::create()->by_group();

		self::assertSame( Group::all(), array_keys( $groups ) );
		self::assertGreaterThanOrEqual( 4, count( $groups[ Group::CONTENT ] ) );
	}

	public function test_tweak_classes_live_in_their_group_folder(): void {
		foreach ( TweakList::classes() as $class_name ) {
			$tweak  = new $class_name();
			$folder = ucfirst( $tweak->group() );
			self::assertStringStartsWith( 'Saizul\\Debloat\\Tweaks\\' . $folder . '\\', $class_name );
			self::assertTrue( ( new \ReflectionClass( $class_name ) )->isFinal(), $class_name . ' should be final.' );
		}
	}

	public function test_filter_adds_instances_and_class_names(): void {
		add_filter(
			'debloat_tweaks',
			static function ( array $tweaks ): array {
				$tweaks[] = new CustomTweak( 'shop_cart_fragments' );
				$tweaks[] = CustomTweak::class;
				return $tweaks;
			}
		);
		$registry = Registry::create();

		self::assertTrue( $registry->has( 'shop_cart_fragments' ) );
		self::assertTrue( $registry->has( 'custom_tweak' ) );
		self::assertSame( 'woocommerce', $registry->get( 'custom_tweak' )->group() );
		$groups = array_keys( $registry->by_group() );
		self::assertSame( 'woocommerce', end( $groups ), 'Custom groups come after the built-in ones.' );
	}

	public function test_filter_can_remove_a_tweak(): void {
		add_filter(
			'debloat_tweaks',
			static function ( array $tweaks ): array {
				return array_values(
					array_filter(
						$tweaks,
						static function ( Tweak $tweak ): bool {
							return 'emojis' !== $tweak->id();
						}
					)
				);
			}
		);

		self::assertFalse( Registry::create()->has( 'emojis' ) );
	}

	public function test_rejects_duplicates_invalid_ids_and_non_tweaks(): void {
		$registry = new Registry();

		self::assertTrue( $registry->add( new CustomTweak( 'one' ) ) );
		self::assertFalse( $registry->add( new CustomTweak( 'one' ) ) );
		self::assertFalse( $registry->add( new CustomTweak( 'Bad-ID' ) ) );
		self::assertFalse( $registry->add( new CustomTweak( 'x' ) ) );
		self::assertFalse( $registry->add( new CustomTweak( 'risky', 'admin', 'extreme' ) ) );
		self::assertFalse( $registry->add( new CustomTweak( 'grouped', 'Bad Group' ) ) );
		self::assertFalse( $registry->add( new \stdClass() ) );
		self::assertFalse( $registry->add( 'not a tweak' ) );

		self::assertSame( array( 'one' ), $registry->ids() );
		self::assertCount( 7, $registry->rejected() );
		self::assertCount( 7, WpStubs::$doing_it_wrong );
		self::assertStringContainsString( 'already used', $registry->rejected()[0] );
	}

	public function test_non_array_filter_result_gives_an_empty_registry(): void {
		add_filter( 'debloat_tweaks', '__return_false' );

		self::assertSame( 0, Registry::create()->count() );
	}

	public function test_ids_are_sorted_by_group_then_list_order(): void {
		$ids = Registry::create()->ids();

		self::assertSame( 'emojis', $ids[0] );
		self::assertLessThan( array_search( 'xmlrpc_off', $ids, true ), array_search( 'rsd_link', $ids, true ) );
		self::assertLessThan( array_search( 'dashboard_widgets', $ids, true ), array_search( 'comments', $ids, true ) );
	}
}
