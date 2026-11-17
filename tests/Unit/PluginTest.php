<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Plugin;
use Saizul\Debloat\Settings;
use Saizul\Debloat\Tests\WpTestCase;
use WpStubs;

final class PluginTest extends WpTestCase {

	private const FILE = '/var/www/wp-content/plugins/wp-debloat/wp-debloat.php';

	public function test_boot_registers_hooks_once(): void {
		$plugin = Plugin::boot( self::FILE );

		self::assertSame( $plugin, Plugin::boot( self::FILE ) );
		self::assertSame( $plugin, Plugin::instance() );
		self::assertTrue( self::hooked( 'plugins_loaded', array( $plugin, 'load' ), 20 ) );
		self::assertTrue( self::hooked( 'init', array( $plugin, 'load_textdomain' ) ) );
		self::assertFalse( has_action( 'admin_menu' ), 'No admin hooks on the front end.' );
	}

	public function test_admin_screen_is_registered_in_the_admin(): void {
		WpStubs::$admin = true;
		Plugin::boot( self::FILE );

		self::assertTrue( has_action( 'admin_menu' ) );
		self::assertTrue( has_action( 'admin_post_wp_debloat_save' ) );
		self::assertTrue( has_filter( 'plugin_action_links_wp-debloat/wp-debloat.php' ) );
	}

	public function test_load_applies_enabled_tweaks_once(): void {
		add_action( 'wp_head', 'rsd_link' );
		add_action( 'wp_head', 'wp_generator' );
		WpStubs::$options[ Settings::OPTION ] = array( 'enabled' => array( 'rsd_link' ) );
		$plugin                               = new Plugin( self::FILE );
		$plugin->load();
		$plugin->load();

		self::assertFalse( self::hooked( 'wp_head', 'rsd_link' ) );
		self::assertTrue( self::hooked( 'wp_head', 'wp_generator' ) );
		self::assertSame( array( 'rsd_link' ), $plugin->runner()->applied() );
	}

	public function test_save_settings_updates_the_option_and_the_instance(): void {
		$plugin = new Plugin( self::FILE );
		$plugin->save_settings( new Settings( array( 'emojis' ) ) );

		self::assertSame( array( 'emojis' ), WpStubs::$options[ Settings::OPTION ]['enabled'] );
		self::assertTrue( $plugin->settings()->is_enabled( 'emojis' ) );
	}

	public function test_textdomain_path(): void {
		( new Plugin( self::FILE ) )->load_textdomain();

		self::assertSame( 'wp-debloat/languages', WpStubs::$registry['textdomain:wp-debloat'] );
	}
}
