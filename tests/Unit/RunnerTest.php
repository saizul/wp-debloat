<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Registry;
use Saizul\Debloat\Runner;
use Saizul\Debloat\Settings;
use Saizul\Debloat\Tests\Unit\Fixtures\CustomTweak;
use Saizul\Debloat\Tests\WpTestCase;
use Saizul\Debloat\Tweak\Field;

final class RunnerTest extends WpTestCase {

	public function test_applies_only_enabled_tweaks(): void {
		$registry = new Registry();
		$on       = new CustomTweak( 'on_tweak' );
		$off      = new CustomTweak( 'off_tweak' );
		$registry->add( $on );
		$registry->add( $off );

		$runner = new Runner();
		$runner->run( $registry, new Settings( array( 'on_tweak' ) ) );
		$runner->run( $registry, new Settings( array( 'on_tweak' ) ) );

		self::assertSame( 1, $on->applied, 'Applied once even when run twice.' );
		self::assertSame( 0, $off->applied );
		self::assertSame( array( 'on_tweak' ), $runner->applied() );
	}

	public function test_passes_the_stored_value_or_the_default(): void {
		$registry = new Registry();
		$tweak    = new CustomTweak( 'valued', 'shop', 'low', Field::int( 5, 0, 10 ) );
		$registry->add( $tweak );

		self::assertSame( 5, $tweak->value() );
		( new Runner() )->run( $registry, new Settings( array( 'valued' ), array( 'valued' => 8 ) ) );
		self::assertSame( 8, $tweak->value() );
	}

	public function test_skip_filter_receives_id_and_tweak(): void {
		$registry = new Registry();
		$tweak    = new CustomTweak( 'skipped_one' );
		$registry->add( $tweak );
		$seen = array();
		add_filter(
			'debloat_skip_tweak',
			static function ( $skip, $id, $object ) use ( &$seen ) {
				$seen[] = array( $id, get_class( $object ) );
				return 'skipped_one' === $id;
			},
			10,
			3
		);

		$runner = new Runner();
		$runner->run( $registry, new Settings( array( 'skipped_one' ) ) );

		self::assertSame( 0, $tweak->applied );
		self::assertSame( array( 'skipped_one' ), $runner->skipped() );
		self::assertSame( array( array( 'skipped_one', CustomTweak::class ) ), $seen );
	}

	public function test_a_failing_tweak_does_not_stop_the_others(): void {
		$registry      = new Registry();
		$broken        = new CustomTweak( 'broken' );
		$broken->throws = true;
		$fine          = new CustomTweak( 'fine' );
		$registry->add( $broken );
		$registry->add( $fine );

		$runner = new Runner();
		$runner->run( $registry, new Settings( array( 'broken', 'fine' ) ) );

		self::assertSame( array( 'broken' => 'Boom' ), $runner->errors() );
		self::assertSame( 1, $fine->applied );
	}

	public function test_every_built_in_tweak_applies_without_errors(): void {
		$registry = Registry::create();
		$runner   = new Runner();
		$runner->run( $registry, new Settings( $registry->ids() ) );

		self::assertSame( array(), $runner->errors() );
		self::assertSame( $registry->ids(), $runner->applied() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_safe_mode_constant_applies_nothing(): void {
		define( 'WP_DEBLOAT_DISABLE', true );
		$registry = new Registry();
		$tweak    = new CustomTweak( 'any_tweak' );
		$registry->add( $tweak );

		$runner = new Runner();
		$runner->run( $registry, new Settings( array( 'any_tweak' ) ) );

		self::assertTrue( Runner::safe_mode() );
		self::assertSame( 0, $tweak->applied );
		self::assertSame( array(), $runner->applied() );
	}

	public function test_safe_mode_is_off_without_the_constant(): void {
		self::assertFalse( Runner::safe_mode() );
	}
}
