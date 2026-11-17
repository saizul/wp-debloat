<?php
/**
 * Plugin bootstrap.
 *
 * @package Saizul\Debloat
 */

namespace Saizul\Debloat;

use Saizul\Debloat\Admin\SettingsPage;
use Saizul\Debloat\Cli\Command;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the registry, settings, runner, admin screen and WP-CLI command.
 */
final class Plugin {

	const VERSION = '0.1.0';

	/**
	 * Running instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance;

	/**
	 * Main plugin file.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Registry, built on `plugins_loaded`.
	 *
	 * @var Registry|null
	 */
	private $registry;

	/**
	 * Settings.
	 *
	 * @var Settings|null
	 */
	private $settings;

	/**
	 * Runner.
	 *
	 * @var Runner
	 */
	private $runner;

	/**
	 * Constructor.
	 *
	 * @param string $file Main plugin file.
	 */
	public function __construct( string $file ) {
		$this->file   = $file;
		$this->runner = new Runner();
	}

	/**
	 * Create the instance and register the hooks.
	 *
	 * @param string $file Main plugin file.
	 */
	public static function boot( string $file ): self {
		if ( null === self::$instance ) {
			self::$instance = new self( $file );
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Running instance, if booted.
	 */
	public static function instance(): ?self {
		return self::$instance;
	}

	/**
	 * Forget the instance (tests).
	 */
	public static function reset(): void {
		self::$instance = null;
	}

	/**
	 * Register hooks.
	 */
	public function hooks(): void {
		// Priority 20: after other plugins added their `debloat_tweaks` filters.
		add_action( 'plugins_loaded', array( $this, 'load' ), 20 );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( is_admin() ) {
			( new SettingsPage( $this ) )->register();
		}
		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command( 'debloat', new Command( $this ) );
		}
	}

	/**
	 * Build the registry, load the settings and apply the enabled tweaks.
	 */
	public function load(): void {
		if ( null !== $this->registry ) {
			return;
		}
		$this->registry = Registry::create();
		$this->settings = Settings::load( $this->registry );
		$this->runner->run( $this->registry, $this->settings );
	}

	/**
	 * Load translations for installs outside WordPress.org.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'wp-debloat', false, dirname( plugin_basename( $this->file ) ) . '/languages' );
	}

	/**
	 * Registry (built on first use).
	 */
	public function registry(): Registry {
		if ( null === $this->registry ) {
			$this->registry = Registry::create();
		}

		return $this->registry;
	}

	/**
	 * Current settings.
	 */
	public function settings(): Settings {
		if ( null === $this->settings ) {
			$this->settings = Settings::load( $this->registry() );
		}

		return $this->settings;
	}

	/**
	 * Store new settings. Changes take effect on the next request.
	 *
	 * @param Settings $settings Settings.
	 */
	public function save_settings( Settings $settings ): void {
		$settings->save();
		$this->settings = $settings;
	}

	/**
	 * Runner of this request.
	 */
	public function runner(): Runner {
		return $this->runner;
	}

	/**
	 * Main plugin file.
	 */
	public function file(): string {
		return $this->file;
	}
}
