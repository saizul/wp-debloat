<?php
/**
 * Write the table of tweaks into README.md, from the tweak classes.
 *
 * Usage:
 *   php bin/gen-readme-table.php          Update README.md.
 *   php bin/gen-readme-table.php --check  Exit with 1 if README.md is out of date (CI).
 *
 * The table goes between the `<!-- tweaks:start -->` and `<!-- tweaks:end -->`
 * markers. Runs without WordPress: the few functions the tweak metadata needs
 * are defined here when missing.
 *
 * @package Saizul\Debloat
 */

// phpcs:ignoreFile -- Development script, not shipped in the release zip.

use Saizul\Debloat\Presets;
use Saizul\Debloat\Registry;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Group;
use Saizul\Debloat\Tweak\Tweak;

$root = dirname( __DIR__ );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/' );
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $name, $value, ...$args ) {
		return $value;
	}
}

spl_autoload_register(
	static function ( $class_name ) use ( $root ) {
		$prefix = 'Saizul\\Debloat\\';
		if ( 0 === strpos( $class_name, $prefix ) ) {
			$file = $root . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}
);

/**
 * Markdown-safe cell text.
 */
function wp_debloat_cell( string $text ): string {
	return str_replace( array( '|', "\n" ), array( '\\|', ' ' ), $text );
}

/**
 * Value column of a tweak.
 */
function wp_debloat_value( Tweak $tweak ): string {
	$field = $tweak->field();
	if ( ! $field ) {
		return '-';
	}
	$default = '`' . Field::format( $field->default_value() ) . '`';
	switch ( $field->type() ) {
		case Field::TYPE_INT:
			$unit = (string) $field->option( 'unit', '' );
			return sprintf( '%s (%d to %d%s)', $default, $field->option( 'min' ), $field->option( 'max' ), '' !== $unit ? ' ' . $unit : '' );
		case Field::TYPE_SELECT:
			return $default . ' (' . implode( ', ', array_map( static function ( $c ) { return '`' . $c . '`'; }, $field->choices() ) ) . ')';
		default:
			return $default . ' (list)';
	}
}

$registry = Registry::create();
$labels   = array(
	'low'    => 'Low',
	'medium' => 'Medium',
	'high'   => '**High**',
);
$lines    = array();
$lines[]  = sprintf( '%d tweaks. Every tweak is **off** until you switch it on.', $registry->count() );
foreach ( $registry->by_group() as $group => $tweaks ) {
	$lines[] = '';
	$lines[] = '### ' . Group::plain_label( $group ) . ' (' . count( $tweaks ) . ')';
	$lines[] = '';
	$lines[] = '| ID | Tweak | Risk | Presets | Value (default) |';
	$lines[] = '| --- | --- | --- | --- | --- |';
	foreach ( $tweaks as $id => $tweak ) {
		$presets = array_map(
			static function ( $preset ) {
				return ucfirst( $preset );
			},
			array_intersect( Presets::all(), $tweak->presets() )
		);
		$lines[] = sprintf(
			'| `%s` | **%s**<br>%s | %s | %s | %s |',
			$id,
			wp_debloat_cell( $tweak->title() ),
			wp_debloat_cell( $tweak->description() ),
			$labels[ $tweak->risk() ] ?? $tweak->risk(),
			array() === $presets ? '-' : implode( ', ', $presets ),
			wp_debloat_value( $tweak )
		);
	}
}
$table = implode( "\n", $lines );

$readme  = $root . '/README.md';
$current = (string) file_get_contents( $readme );
$start   = '<!-- tweaks:start -->';
$end     = '<!-- tweaks:end -->';
$from    = strpos( $current, $start );
$to      = strpos( $current, $end );
if ( false === $from || false === $to || $to < $from ) {
	fwrite( STDERR, "README.md has no {$start} ... {$end} markers.\n" );
	exit( 2 );
}
$updated = substr( $current, 0, $from + strlen( $start ) ) . "\n" . $table . "\n" . substr( $current, $to );

if ( in_array( '--check', $argv, true ) ) {
	if ( $updated !== $current ) {
		fwrite( STDERR, "README.md tweak table is out of date. Run: composer readme\n" );
		exit( 1 );
	}
	fwrite( STDOUT, "README.md tweak table is up to date.\n" );
	exit( 0 );
}

file_put_contents( $readme, $updated );
fwrite( STDOUT, sprintf( "Wrote %d tweaks to README.md.\n", $registry->count() ) );
