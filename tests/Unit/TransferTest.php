<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Registry;
use Saizul\Debloat\Settings;
use Saizul\Debloat\Tests\WpTestCase;
use Saizul\Debloat\Transfer;

final class TransferTest extends WpTestCase {

	public function test_export_then_import_round_trip(): void {
		$transfer = new Transfer( Registry::create() );
		$settings = new Settings( array( 'emojis', 'revisions_limit' ), array( 'revisions_limit' => 7 ) );
		$json     = $transfer->export( $settings, '0.1.0' );
		$data     = json_decode( $json, true );

		self::assertSame( 'wp-debloat', $data['plugin'] );
		self::assertSame( 1, $data['schema'] );
		self::assertSame( '0.1.0', $data['version'] );
		self::assertSame( array( 'emojis', 'revisions_limit' ), $data['enabled'] );
		self::assertSame( array( 'revisions_limit' => 7 ), $data['values'] );

		$imported = $transfer->import( $json );
		self::assertSame( $settings->to_array(), $imported->to_array() );
		self::assertSame( array(), $transfer->unknown() );
	}

	public function test_empty_values_export_as_an_object(): void {
		$json = ( new Transfer( Registry::create() ) )->export( new Settings(), '0.1.0' );

		self::assertStringContainsString( '"values": {}', $json );
		self::assertStringContainsString( '"enabled": []', $json );
	}

	public function test_import_skips_unknown_ids_and_sanitises_values(): void {
		$transfer = new Transfer( Registry::create() );
		$settings = $transfer->import( "\xEF\xBB\xBF" . '{"enabled":["emojis","future_tweak"],"values":{"heartbeat_interval":5,"other":1}}' );

		self::assertSame( array( 'emojis' ), $settings->enabled_ids() );
		self::assertSame( 15, $settings->value( 'heartbeat_interval' ) );
		self::assertSame( array( 'future_tweak', 'other' ), $transfer->unknown() );
	}

	/**
	 * @dataProvider invalid
	 */
	public function test_invalid_imports_throw_a_code( string $json, string $code ): void {
		try {
			( new Transfer( Registry::create() ) )->import( $json );
			self::fail( 'No exception for ' . $code );
		} catch ( \InvalidArgumentException $error ) {
			self::assertSame( $code, $error->getMessage() );
			self::assertNotSame( Transfer::error_message( 'whatever' ), Transfer::error_message( $code ) );
		}
	}

	/** @return array<string,array{0:string,1:string}> */
	public static function invalid(): array {
		return array(
			'empty'           => array( "  \n", 'empty' ),
			'not json'        => array( '{enabled: [emojis]}', 'invalid_json' ),
			'scalar json'     => array( '"emojis"', 'invalid_json' ),
			'other plugin'    => array( '{"plugin":"other","enabled":[]}', 'wrong_plugin' ),
			'missing enabled' => array( '{"plugin":"wp-debloat"}', 'missing_enabled' ),
			'enabled string'  => array( '{"enabled":"emojis"}', 'missing_enabled' ),
			'newer schema'    => array( '{"schema":2,"enabled":[]}', 'newer_schema' ),
			'too deep'        => array( str_repeat( '[', 40 ) . str_repeat( ']', 40 ), 'invalid_json' ),
			'too large'       => array( '{"enabled":[],"pad":"' . str_repeat( 'x', 70000 ) . '"}', 'too_large' ),
		);
	}
}
