<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Registry;
use Saizul\Debloat\Settings\Sanitizer;
use Saizul\Debloat\Tests\WpTestCase;

final class SanitizerTest extends WpTestCase {

	private function sanitizer(): Sanitizer {
		return new Sanitizer( Registry::create() );
	}

	public function test_accepts_a_list_of_ids_and_drops_unknown_ones(): void {
		$sanitizer = $this->sanitizer();
		$clean     = $sanitizer->sanitize( array( 'enabled' => array( 'rsd_link', 'emojis', 'nope', ' Shortlink ', 'emojis', 7 ) ) );

		self::assertSame( array( 'emojis', 'rsd_link', 'shortlink' ), $clean['enabled'], 'Registry order, unique, lower-cased.' );
		self::assertSame( array( 'nope' ), $sanitizer->unknown() );
	}

	public function test_accepts_a_map_from_a_form(): void {
		$clean = $this->sanitizer()->sanitize(
			array(
				'enabled' => array(
					'emojis'      => '1',
					'rsd_link'    => 'on',
					'shortlink'   => '0',
					'embeds'      => true,
					'file_editor' => '',
					'comments'    => 1,
				),
			)
		);

		self::assertSame( array( 'emojis', 'embeds', 'rsd_link', 'comments' ), $clean['enabled'] );
	}

	public function test_values_are_sanitised_by_the_field(): void {
		$sanitizer = $this->sanitizer();
		$clean     = $sanitizer->sanitize(
			array(
				'enabled' => array(),
				'values'  => array(
					'revisions_limit'           => '500',
					'heartbeat_interval'        => 'fast',
					'admin_bar_front_end'       => 'everyone',
					'author_archives'           => 'elsewhere',
					'rest_logged_out_allowlist' => "/oembed/1.0\n/wc/store/v1\n<bad>",
					'emojis'                    => 'no field, dropped',
					'unknown_tweak'             => 1,
				),
			)
		);

		self::assertSame(
			array(
				'admin_bar_front_end'       => 'everyone',
				'author_archives'           => '404',
				'heartbeat_interval'        => 60,
				'rest_logged_out_allowlist' => array( '/oembed/1.0', '/wc/store/v1' ),
				'revisions_limit'           => 100,
			),
			$clean['values']
		);
		self::assertSame( array( 'unknown_tweak' ), $sanitizer->unknown() );
	}

	/**
	 * @dataProvider garbage
	 *
	 * @param mixed $raw Input.
	 */
	public function test_garbage_gives_empty_settings( $raw ): void {
		self::assertSame(
			array(
				'enabled' => array(),
				'values'  => array(),
			),
			$this->sanitizer()->sanitize( $raw )
		);
	}

	/** @return array<string,array{0:mixed}> */
	public static function garbage(): array {
		return array(
			'null'           => array( null ),
			'string'         => array( 'emojis' ),
			'empty array'    => array( array() ),
			'scalar enabled' => array( array( 'enabled' => 'emojis', 'values' => 'x' ) ),
			'nested arrays'  => array( array( 'enabled' => array( array( 'emojis' ) ) ) ),
		);
	}
}
