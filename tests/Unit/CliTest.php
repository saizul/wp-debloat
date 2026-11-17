<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Cli\Command;
use Saizul\Debloat\Plugin;
use Saizul\Debloat\Settings;
use Saizul\Debloat\Tests\WpTestCase;
use WP_CLI;
use WpCliHalt;
use WpStubs;

final class CliTest extends WpTestCase {

	/** @var Plugin */
	private $plugin;

	/** @var Command */
	private $command;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin  = new Plugin( '/var/www/wp-content/plugins/wp-debloat/wp-debloat.php' );
		$this->command = new Command( $this->plugin );
	}

	/** @return string[] */
	private function enabled(): array {
		return WpStubs::$options[ Settings::OPTION ]['enabled'] ?? array();
	}

	private function last( string $type ): string {
		$messages = array_values(
			array_filter(
				WP_CLI::$log,
				static function ( array $entry ) use ( $type ): bool {
					return $type === $entry[0];
				}
			)
		);

		return $messages ? (string) end( $messages )[1] : '';
	}

	public function test_list_formats_every_tweak(): void {
		$this->command->list_( array(), array( 'format' => 'json' ) );

		self::assertSame( 'json', WP_CLI::$items['format'] );
		self::assertCount( $this->plugin->registry()->count(), WP_CLI::$items['items'] );
		self::assertSame( array( 'id', 'group', 'risk', 'status', 'value', 'title' ), WP_CLI::$items['fields'] );
		self::assertSame( 'emojis', WP_CLI::$items['items'][0]['id'] );
		self::assertSame( 'off', WP_CLI::$items['items'][0]['status'] );
	}

	public function test_list_filters_by_group_and_enabled(): void {
		$this->plugin->save_settings( new Settings( array( 'xmlrpc_off', 'emojis' ) ) );
		$this->command->list_( array(), array( 'group' => 'security', 'enabled' => true ) );

		self::assertSame( array( 'xmlrpc_off' ), array_column( WP_CLI::$items['items'], 'id' ) );

		$this->command->list_( array(), array( 'enabled' => true, 'format' => 'ids' ) );
		self::assertSame( 'emojis xmlrpc_off', $this->last( 'line' ) );
	}

	public function test_enable_and_disable(): void {
		$this->command->enable( array( 'emojis', 'rsd_link' ), array() );
		self::assertSame( array( 'emojis', 'rsd_link' ), $this->enabled() );
		self::assertStringContainsString( 'Enabled 2', $this->last( 'success' ) );

		$this->command->enable( array( 'emojis' ), array() );
		self::assertStringContainsString( 'Nothing to change', $this->last( 'success' ) );

		$this->command->disable( array( 'emojis' ), array() );
		self::assertSame( array( 'rsd_link' ), $this->enabled() );

		$this->command->disable( array( 'all' ), array() );
		self::assertSame( array(), $this->enabled() );
	}

	public function test_enable_validates_all_ids_before_saving(): void {
		try {
			$this->command->enable( array( 'emojis', 'nope' ), array() );
			self::fail( 'No error.' );
		} catch ( WpCliHalt $halt ) {
			self::assertStringContainsString( 'Unknown tweak "nope"', $this->last( 'error' ) );
			self::assertArrayNotHasKey( Settings::OPTION, WpStubs::$options );
		}
	}

	public function test_enable_warns_about_high_risk(): void {
		$this->command->enable( array( 'rest_logged_out_allowlist' ), array() );

		self::assertStringContainsString( 'high-risk', $this->last( 'warning' ) );
	}

	public function test_enable_without_ids_errors(): void {
		$this->expectException( WpCliHalt::class );
		$this->command->enable( array(), array() );
	}

	public function test_preset(): void {
		$this->command->preset( array( 'aggressive' ), array( 'dry-run' => true ) );
		self::assertArrayNotHasKey( Settings::OPTION, WpStubs::$options );
		self::assertStringContainsString( 'would switch on', $this->last( 'line' ) );

		$this->command->preset( array( 'recommended' ), array() );
		self::assertContains( 'emojis', $this->enabled() );
		self::assertNotContains( 'xmlrpc_off', $this->enabled() );

		$this->command->preset( array( 'off' ), array() );
		self::assertSame( array(), $this->enabled() );

		$this->expectException( WpCliHalt::class );
		$this->command->preset( array( 'max' ), array() );
	}

	public function test_set_value(): void {
		$this->command->set( array( 'revisions_limit', '500' ), array() );
		self::assertSame( 100, WpStubs::$options[ Settings::OPTION ]['values']['revisions_limit'] );
		self::assertSame( 'Set revisions_limit to 100.', $this->last( 'success' ) );

		$this->command->set( array( 'rest_logged_out_allowlist', '/oembed/1.0,/wc/store/v1' ), array() );
		self::assertSame( array( '/oembed/1.0', '/wc/store/v1' ), WpStubs::$options[ Settings::OPTION ]['values']['rest_logged_out_allowlist'] );

		$this->expectException( WpCliHalt::class );
		$this->command->set( array( 'emojis', '1' ), array() );
	}

	public function test_status_json(): void {
		$this->plugin->save_settings( new Settings( array( 'emojis', 'heartbeat_interval' ) ) );
		$this->command->status( array(), array( 'format' => 'json' ) );
		$status = json_decode( $this->last( 'line' ), true );

		self::assertSame( Plugin::VERSION, $status['version'] );
		self::assertFalse( $status['safe_mode'] );
		self::assertSame( $this->plugin->registry()->count(), $status['total'] );
		self::assertSame( array( 'emojis', 'heartbeat_interval' ), $status['enabled'] );
		self::assertSame( array( 'heartbeat_interval' => 60 ), $status['values'] );
		self::assertSame( array(), $status['errors'] );
	}

	public function test_status_table(): void {
		$this->plugin->save_settings( new Settings( array( 'emojis' ) ) );
		$this->command->status( array(), array() );

		self::assertStringContainsString( '1 of ', $this->last( 'line' ) );
		self::assertSame( array( 'emojis' ), array_column( WP_CLI::$items['items'], 'id' ) );
	}

	public function test_export_and_import(): void {
		$this->plugin->save_settings( new Settings( array( 'emojis' ), array( 'revisions_limit' => 3 ) ) );
		$this->command->export( array(), array() );
		$json = WP_CLI::$out;
		self::assertSame( array( 'emojis' ), json_decode( $json, true )['enabled'] );

		$file = tempnam( sys_get_temp_dir(), 'debloat' );
		file_put_contents( $file, str_replace( '"emojis"', '"rsd_link", "unknown_x"', $json ) );
		$this->plugin->save_settings( new Settings() );
		$this->command->import( array( $file ), array() );
		unlink( $file );

		self::assertSame( array( 'rsd_link' ), $this->enabled() );
		self::assertSame( 3, WpStubs::$options[ Settings::OPTION ]['values']['revisions_limit'] );
		self::assertStringContainsString( 'unknown_x', $this->last( 'warning' ) );
	}

	public function test_import_errors(): void {
		$this->expectException( WpCliHalt::class );
		$this->command->import( array( '/does/not/exist.json' ), array() );
	}
}
