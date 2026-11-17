<?php
/**
 * Import and export of settings.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat;

use Saizul\Debloat\Settings\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Settings as a portable JSON document.
 *
 * `{"plugin":"wp-debloat","schema":1,"version":"0.1.0","enabled":[...],"values":{...}}`
 */
final class Transfer {

	const MARKER   = 'wp-debloat';
	const MAX_SIZE = 65536;

	/**
	 * Registry.
	 *
	 * @var Registry
	 */
	private $registry;

	/**
	 * IDs of the last import that are not registered on this site.
	 *
	 * @var string[]
	 */
	private $unknown = array();

	/**
	 * Constructor.
	 *
	 * @param Registry $registry Registry.
	 */
	public function __construct( Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Export settings as pretty-printed JSON.
	 *
	 * @param Settings $settings Settings.
	 * @param string   $version  Plugin version.
	 */
	public function export( Settings $settings, string $version ): string {
		$data   = $settings->to_array();
		$values = (object) $data['values'];

		return (string) wp_json_encode(
			array(
				'plugin'  => self::MARKER,
				'schema'  => Settings::SCHEMA,
				'version' => $version,
				'enabled' => $data['enabled'],
				'values'  => $values,
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . "\n";
	}

	/**
	 * Parse an exported document into settings.
	 *
	 * @param string $json JSON text.
	 * @throws \InvalidArgumentException When the text is not a valid export.
	 */
	public function import( string $json ): Settings {
		$this->unknown = array();
		$json          = trim( $json );
		if ( '' === $json ) {
			throw new \InvalidArgumentException( 'empty' );
		}
		if ( strlen( $json ) > self::MAX_SIZE ) {
			throw new \InvalidArgumentException( 'too_large' );
		}
		// Strip a UTF-8 byte order mark added by some editors.
		if ( 0 === strpos( $json, "\xEF\xBB\xBF" ) ) {
			$json = substr( $json, 3 );
		}
		$data = json_decode( $json, true, 16 );
		if ( ! is_array( $data ) ) {
			throw new \InvalidArgumentException( 'invalid_json' );
		}
		if ( isset( $data['plugin'] ) && self::MARKER !== $data['plugin'] ) {
			throw new \InvalidArgumentException( 'wrong_plugin' );
		}
		if ( ! array_key_exists( 'enabled', $data ) || ! is_array( $data['enabled'] ) ) {
			throw new \InvalidArgumentException( 'missing_enabled' );
		}
		if ( isset( $data['schema'] ) && (int) $data['schema'] > Settings::SCHEMA ) {
			throw new \InvalidArgumentException( 'newer_schema' );
		}
		$sanitizer     = new Sanitizer( $this->registry );
		$clean         = $sanitizer->sanitize( $data );
		$this->unknown = $sanitizer->unknown();

		return new Settings( $clean['enabled'], $clean['values'] );
	}

	/**
	 * Unknown IDs skipped by the last import.
	 *
	 * @return string[]
	 */
	public function unknown(): array {
		return $this->unknown;
	}

	/**
	 * Translated message for an import error code.
	 *
	 * @param string $code Code from the exception message.
	 */
	public static function error_message( string $code ): string {
		switch ( $code ) {
			case 'empty':
				return __( 'The import was empty.', 'wp-debloat' );
			case 'too_large':
				return __( 'The import file is too large.', 'wp-debloat' );
			case 'invalid_json':
				return __( 'The import is not valid JSON.', 'wp-debloat' );
			case 'wrong_plugin':
				return __( 'The import file was not exported by Debloat & Harden.', 'wp-debloat' );
			case 'missing_enabled':
				return __( 'The import has no list of enabled tweaks.', 'wp-debloat' );
			case 'newer_schema':
				return __( 'The import was made by a newer version of the plugin. Update the plugin first.', 'wp-debloat' );
			default:
				return __( 'The import failed.', 'wp-debloat' );
		}
	}
}
