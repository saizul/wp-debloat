<?php
/**
 * WP-CLI command.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat\Cli;

use Saizul\Debloat\Plugin;
use Saizul\Debloat\Presets;
use Saizul\Debloat\Runner;
use Saizul\Debloat\Transfer;
use Saizul\Debloat\Tweak\Field;
use Saizul\Debloat\Tweak\Tweak;

defined( 'ABSPATH' ) || exit;

/**
 * Manage Debloat & Harden tweaks.
 *
 * ## EXAMPLES
 *
 *     wp debloat list
 *     wp debloat enable emojis rsd_link
 *     wp debloat preset recommended
 *     wp debloat status --format=json
 */
final class Command {

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * List all tweaks.
	 *
	 * ## OPTIONS
	 *
	 * [--group=<group>]
	 * : Only tweaks of this group (performance, security, content, admin).
	 *
	 * [--enabled]
	 * : Only enabled tweaks.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp debloat list --group=security
	 *     wp debloat list --enabled --format=ids
	 *
	 * @subcommand list
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function list_( $args, $assoc_args ): void {
		$group   = isset( $assoc_args['group'] ) ? (string) $assoc_args['group'] : '';
		$only_on = ! empty( $assoc_args['enabled'] );
		$format  = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';
		$rows    = array();
		foreach ( $this->plugin->registry()->all() as $id => $tweak ) {
			if ( '' !== $group && $tweak->group() !== $group ) {
				continue;
			}
			$row = $this->row( $tweak );
			if ( $only_on && 'on' !== $row['status'] ) {
				continue;
			}
			$rows[] = $row;
		}
		if ( 'ids' === $format ) {
			\WP_CLI::line( implode( ' ', array_column( $rows, 'id' ) ) );
			return;
		}
		\WP_CLI\Utils\format_items( $format, $rows, array( 'id', 'group', 'risk', 'status', 'value', 'title' ) );
	}

	/**
	 * Switch tweaks on.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more tweak IDs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp debloat enable emojis embeds rsd_link
	 *
	 * @param string[]             $args       Tweak IDs.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function enable( $args, $assoc_args ): void {
		$this->toggle( $args, true );
	}

	/**
	 * Switch tweaks off.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more tweak IDs, or "all".
	 *
	 * ## EXAMPLES
	 *
	 *     wp debloat disable jquery_migrate
	 *     wp debloat disable all
	 *
	 * @param string[]             $args       Tweak IDs.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function disable( $args, $assoc_args ): void {
		if ( array( 'all' ) === array_values( $args ) ) {
			$this->plugin->save_settings( $this->plugin->settings()->with_only( array() ) );
			\WP_CLI::success( 'All tweaks are off.' );
			return;
		}
		$this->toggle( $args, false );
	}

	/**
	 * Set the value of a tweak that has one.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Tweak ID.
	 *
	 * <value>
	 * : New value. For lists, separate items with commas.
	 *
	 * ## EXAMPLES
	 *
	 *     wp debloat set revisions_limit 10
	 *     wp debloat set rest_logged_out_allowlist /oembed/1.0,/contact-form-7/v1
	 *
	 * @param string[]             $args       ID and value.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function set( $args, $assoc_args ): void {
		$id    = (string) ( $args[0] ?? '' );
		$tweak = $this->tweak( $id );
		$field = $tweak->field();
		if ( ! $field ) {
			\WP_CLI::error( sprintf( 'Tweak "%s" has no value to set.', $id ) );
			return;
		}
		$value = $field->sanitize( (string) ( $args[1] ?? '' ) );
		$this->plugin->save_settings( $this->plugin->settings()->with_value( $id, $value ) );
		\WP_CLI::success( sprintf( 'Set %s to %s.', $id, Field::format( $value ) ) );
	}

	/**
	 * Apply a preset: recommended, aggressive or off.
	 *
	 * Replaces which tweaks are on. Values are kept.
	 *
	 * ## OPTIONS
	 *
	 * <preset>
	 * : Preset name.
	 * ---
	 * options:
	 *   - recommended
	 *   - aggressive
	 *   - off
	 * ---
	 *
	 * [--dry-run]
	 * : Show which tweaks the preset switches on without saving.
	 *
	 * ## EXAMPLES
	 *
	 *     wp debloat preset recommended
	 *     wp debloat preset aggressive --dry-run
	 *
	 * @param string[]             $args       Preset.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function preset( $args, $assoc_args ): void {
		$preset = (string) ( $args[0] ?? '' );
		if ( ! Presets::exists( $preset ) ) {
			\WP_CLI::error( sprintf( 'Unknown preset "%s". Use one of: %s.', $preset, implode( ', ', Presets::all() ) ) );
			return;
		}
		$ids = Presets::ids( $preset, $this->plugin->registry() );
		if ( ! empty( $assoc_args['dry-run'] ) ) {
			\WP_CLI::line( sprintf( 'Preset "%s" would switch on %d tweaks: %s', $preset, count( $ids ), implode( ' ', $ids ) ) );
			return;
		}
		$this->plugin->save_settings( Presets::apply( $preset, $this->plugin->settings(), $this->plugin->registry() ) );
		\WP_CLI::success( sprintf( 'Preset "%s" applied: %d tweaks on.', $preset, count( $ids ) ) );
	}

	/**
	 * Show which tweaks are on and whether safe mode is active.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp debloat status --format=json
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function status( $args, $assoc_args ): void {
		$format   = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';
		$registry = $this->plugin->registry();
		$settings = $this->plugin->settings();
		$enabled  = array_values( array_intersect( $registry->ids(), $settings->enabled_ids() ) );
		$values   = array();
		foreach ( $enabled as $id ) {
			$tweak = $registry->get( $id );
			if ( $tweak && $tweak->field() ) {
				$stored        = $settings->value( $id );
				$values[ $id ] = null === $stored ? $tweak->field()->default_value() : $stored;
			}
		}
		$status = array(
			'version'   => Plugin::VERSION,
			'safe_mode' => Runner::safe_mode(),
			'total'     => $registry->count(),
			'enabled'   => $enabled,
			'values'    => (object) $values,
			'applied'   => $this->plugin->runner()->applied(),
			'skipped'   => $this->plugin->runner()->skipped(),
			'errors'    => (object) $this->plugin->runner()->errors(),
		);

		if ( 'json' === $format ) {
			\WP_CLI::line( (string) wp_json_encode( $status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			return;
		}
		if ( 'yaml' === $format ) {
			\WP_CLI\Utils\format_items( 'yaml', array( json_decode( (string) wp_json_encode( $status ), true ) ), array_keys( $status ) );
			return;
		}
		if ( $status['safe_mode'] ) {
			\WP_CLI::warning( 'Safe mode is on (WP_DEBLOAT_DISABLE): no tweak is applied.' );
		}
		\WP_CLI::line( sprintf( '%d of %d tweaks are on.', count( $enabled ), $registry->count() ) );
		$rows = array();
		foreach ( $enabled as $id ) {
			$tweak = $registry->get( $id );
			if ( $tweak ) {
				$rows[] = $this->row( $tweak );
			}
		}
		if ( array() !== $rows ) {
			\WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'group', 'risk', 'value', 'title' ) );
		}
	}

	/**
	 * Print the settings as JSON (same format as the settings screen export).
	 *
	 * ## EXAMPLES
	 *
	 *     wp debloat export > debloat.json
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function export( $args, $assoc_args ): void {
		$transfer = new Transfer( $this->plugin->registry() );
		\WP_CLI::out( $transfer->export( $this->plugin->settings(), Plugin::VERSION ) );
	}

	/**
	 * Import settings from a JSON export.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the JSON file, or "-" to read from standard input.
	 *
	 * ## EXAMPLES
	 *
	 *     wp debloat import debloat.json
	 *
	 * @param string[]             $args       File.
	 * @param array<string,string> $assoc_args Options.
	 */
	public function import( $args, $assoc_args ): void {
		$file = (string) ( $args[0] ?? '' );
		$json = '-' === $file ? (string) file_get_contents( 'php://stdin' ) : ( is_readable( $file ) ? (string) file_get_contents( $file ) : null ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file given on the command line.
		if ( null === $json ) {
			\WP_CLI::error( sprintf( 'Cannot read "%s".', $file ) );
			return;
		}
		$transfer = new Transfer( $this->plugin->registry() );
		try {
			$settings = $transfer->import( $json );
		} catch ( \InvalidArgumentException $error ) {
			\WP_CLI::error( Transfer::error_message( $error->getMessage() ) );
			return;
		}
		$this->plugin->save_settings( $settings );
		foreach ( $transfer->unknown() as $id ) {
			\WP_CLI::warning( sprintf( 'Skipped unknown tweak "%s".', $id ) );
		}
		\WP_CLI::success( sprintf( 'Imported: %d tweaks on.', count( $settings->enabled_ids() ) ) );
	}

	/**
	 * Switch IDs on or off; all IDs are checked before anything is saved.
	 *
	 * @param string[] $ids IDs.
	 * @param bool     $on  On or off.
	 */
	private function toggle( array $ids, bool $on ): void {
		if ( array() === $ids ) {
			\WP_CLI::error( 'Give at least one tweak ID. See "wp debloat list".' );
			return;
		}
		foreach ( $ids as $id ) {
			$this->tweak( (string) $id );
		}
		$settings = $this->plugin->settings();
		$changed  = array();
		foreach ( $ids as $id ) {
			$id = (string) $id;
			if ( $settings->is_enabled( $id ) !== $on ) {
				$changed[] = $id;
			}
			$settings = $settings->with_enabled( $id, $on );
		}
		$this->plugin->save_settings( $settings );
		if ( $on ) {
			foreach ( $ids as $id ) {
				$tweak = $this->plugin->registry()->get( (string) $id );
				if ( $tweak && 'high' === $tweak->risk() ) {
					\WP_CLI::warning( sprintf( '"%s" is a high-risk tweak. Test the site now.', $id ) );
				}
			}
		}
		$verb = $on ? 'Enabled' : 'Disabled';
		if ( array() === $changed ) {
			\WP_CLI::success( sprintf( 'Nothing to change: already %s.', $on ? 'on' : 'off' ) );
			return;
		}
		\WP_CLI::success( sprintf( '%s %d: %s.', $verb, count( $changed ), implode( ', ', $changed ) ) );
	}

	/**
	 * Tweak by ID or exit with an error.
	 *
	 * @param string $id ID.
	 */
	private function tweak( string $id ): Tweak {
		$tweak = $this->plugin->registry()->get( $id );
		if ( ! $tweak ) {
			\WP_CLI::error( sprintf( 'Unknown tweak "%s". See "wp debloat list".', $id ) );
		}

		return $tweak;
	}

	/**
	 * Table row.
	 *
	 * @param Tweak $tweak Tweak.
	 * @return array<string,string>
	 */
	private function row( Tweak $tweak ): array {
		$settings = $this->plugin->settings();
		$field    = $tweak->field();
		$value    = '';
		if ( $field ) {
			$stored = $settings->value( $tweak->id() );
			$value  = Field::format( null === $stored ? $field->default_value() : $stored );
		}

		return array(
			'id'     => $tweak->id(),
			'group'  => $tweak->group(),
			'risk'   => $tweak->risk(),
			'status' => $settings->is_enabled( $tweak->id() ) ? 'on' : 'off',
			'value'  => $value,
			'title'  => $tweak->title(),
		);
	}
}
