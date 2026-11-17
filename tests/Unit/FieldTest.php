<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Tests\WpTestCase;
use Saizul\Debloat\Tweak\Field;

final class FieldTest extends WpTestCase {

	public function test_int_is_clamped_and_falls_back_to_default(): void {
		$field = Field::int( 60, 15, 120, 's' );

		self::assertSame( 90, $field->sanitize( '90' ) );
		self::assertSame( 15, $field->sanitize( 3 ) );
		self::assertSame( 120, $field->sanitize( '9999' ) );
		self::assertSame( 31, $field->sanitize( '30.6' ) );
		self::assertSame( 60, $field->sanitize( 'abc' ) );
		self::assertSame( 60, $field->sanitize( array( 1 ) ) );
		self::assertSame( 60, $field->sanitize( true ) );
		self::assertSame( 60, $field->sanitize( null ) );
		self::assertSame( 's', $field->option( 'unit' ) );
	}

	public function test_select_only_accepts_known_choices(): void {
		$field = Field::select( 'a', array( 'a', 'b' ) );

		self::assertSame( 'b', $field->sanitize( 'b' ) );
		self::assertSame( 'a', $field->sanitize( 'c' ) );
		self::assertSame( 'a', $field->sanitize( array( 'b' ) ) );
		self::assertSame( array( 'a', 'b' ), $field->choices() );
	}

	public function test_lines_are_split_trimmed_deduplicated_and_validated(): void {
		$field = Field::lines( array( '/oembed/1.0' ), '#^/?[A-Za-z0-9_\-./]+$#', 3 );

		self::assertSame( array( '/a/v1', '/b' ), $field->sanitize( " /a/v1 \r\n\n/b\n/a/v1" ) );
		self::assertSame( array( '/a', '/b' ), $field->sanitize( '/a,/b' ) );
		self::assertSame( array( '/ok' ), $field->sanitize( array( '/ok', '<script>', array( 'x' ), '/with space' ) ) );
		self::assertSame( array( '/1', '/2', '/3' ), $field->sanitize( "/1\n/2\n/3\n/4" ) );
		self::assertSame( array( '/oembed/1.0' ), $field->sanitize( 42 ) );
		self::assertSame( array(), $field->sanitize( '' ) );
	}

	public function test_format(): void {
		self::assertSame( '/a, /b', Field::format( array( '/a', '/b' ) ) );
		self::assertSame( '5', Field::format( 5 ) );
		self::assertSame( 'false', Field::format( false ) );
	}
}
