<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit;

use Saizul\Debloat\Admin\SettingsPage;
use Saizul\Debloat\Plugin;
use Saizul\Debloat\Settings;
use Saizul\Debloat\Tests\WpTestCase;
use WpDieException;
use WpNonceException;
use WpRedirectException;
use WpStubs;

final class SettingsPageTest extends WpTestCase {

	/** @var Plugin */
	private $plugin;

	/** @var SettingsPage */
	private $page;

	protected function setUp(): void {
		parent::setUp();
		WpStubs::$admin = true;
		$this->plugin   = new Plugin( '/var/www/wp-content/plugins/wp-debloat/wp-debloat.php' );
		$this->page     = new SettingsPage( $this->plugin );
		$this->page->register();
	}

	private function post( string $action, array $fields ): void {
		$_POST    = array_merge( array( '_wpnonce' => 'nonce-' . $action ), $fields );
		$_REQUEST = $_POST;
	}

	private function expect_redirect( callable $callback ): string {
		try {
			$callback();
		} catch ( WpRedirectException $redirect ) {
			return $redirect->getMessage();
		}
		self::fail( 'No redirect.' );
	}

	public function test_page_is_added_under_settings_with_manage_options(): void {
		do_action( 'admin_menu' );

		self::assertSame( 'manage_options', WpStubs::$registry['page:wp-debloat'][1] );
	}

	public function test_assets_load_only_on_the_screen(): void {
		do_action( 'admin_menu' );
		do_action( 'admin_enqueue_scripts', 'index.php' );
		self::assertSame( array(), WpStubs::calls( 'enqueue_style' ) );

		do_action( 'admin_enqueue_scripts', 'settings_page_wp-debloat' );
		self::assertSame( array( 'wp-debloat-admin' ), WpStubs::calls( 'enqueue_style' ) );
		self::assertSame( array( 'wp-debloat-admin' ), WpStubs::calls( 'enqueue_script' ) );
		self::assertArrayHasKey( 'confirmPreset', WpStubs::$registry['l10n:wp-debloat-admin'] );
	}

	public function test_save_requires_the_capability(): void {
		WpStubs::$can = false;
		$this->post( 'wp_debloat_save', array( 'wp_debloat' => array( 'enabled' => array( 'emojis' => '1' ) ) ) );

		$this->expectException( WpDieException::class );
		$this->page->handle_save();
	}

	public function test_save_requires_a_valid_nonce(): void {
		$this->post( 'wp_debloat_save', array( 'wp_debloat' => array( 'enabled' => array( 'emojis' => '1' ) ) ) );
		$_REQUEST['_wpnonce'] = 'forged';

		try {
			$this->page->handle_save();
			self::fail( 'Saved without a nonce.' );
		} catch ( WpNonceException $error ) {
			self::assertArrayNotHasKey( Settings::OPTION, WpStubs::$options );
		}
	}

	public function test_save_stores_sanitised_toggles_and_values(): void {
		$this->post(
			'wp_debloat_save',
			array(
				'wp_debloat' => array(
					'enabled' => array(
						'emojis'          => '1',
						'revisions_limit' => '1',
						'not_a_tweak'     => '1',
					),
					'values'  => array(
						'revisions_limit'     => '12',
						'admin_bar_front_end' => 'nobody',
					),
				),
			)
		);
		$url = $this->expect_redirect( array( $this->page, 'handle_save' ) );

		self::assertStringContainsString( 'debloat_notice=saved', $url );
		self::assertSame(
			array(
				'version' => 1,
				'enabled' => array( 'emojis', 'revisions_limit' ),
				'values'  => array(
					'admin_bar_front_end' => 'non_admins',
					'revisions_limit'     => 12,
				),
			),
			WpStubs::$options[ Settings::OPTION ]
		);
	}

	public function test_unchecked_tweaks_are_switched_off_but_keep_values(): void {
		$this->plugin->save_settings( new Settings( array( 'emojis', 'revisions_limit' ), array( 'revisions_limit' => 4 ) ) );
		$settings = $this->page->settings_from_form( array( 'enabled' => array( 'rsd_link' => '1' ) ) );

		self::assertSame( array( 'rsd_link' ), $settings->enabled_ids() );
		self::assertSame( 4, $settings->value( 'revisions_limit' ) );
		self::assertSame( array(), $this->page->settings_from_form( 'junk' )->enabled_ids() );
	}

	public function test_preset_handler(): void {
		$this->post( 'wp_debloat_preset', array( 'preset' => 'recommended' ) );
		$url = $this->expect_redirect( array( $this->page, 'handle_preset' ) );

		self::assertStringContainsString( 'preset=recommended', $url );
		self::assertContains( 'emojis', WpStubs::$options[ Settings::OPTION ]['enabled'] );
		self::assertNotContains( 'xmlrpc_off', WpStubs::$options[ Settings::OPTION ]['enabled'] );
	}

	public function test_unknown_preset_dies(): void {
		$this->post( 'wp_debloat_preset', array( 'preset' => 'everything' ) );

		$this->expectException( WpDieException::class );
		$this->page->handle_preset();
	}

	public function test_import_from_pasted_json(): void {
		$this->post( 'wp_debloat_import', array( 'debloat_import' => '{"plugin":"wp-debloat","enabled":["emojis","gone"],"values":{}}' ) );
		$url = $this->expect_redirect( array( $this->page, 'handle_import' ) );

		self::assertStringContainsString( 'debloat_notice=imported', $url );
		self::assertStringContainsString( 'skipped=1', $url );
		self::assertSame( array( 'emojis' ), WpStubs::$options[ Settings::OPTION ]['enabled'] );
	}

	public function test_failed_import_keeps_settings(): void {
		$this->plugin->save_settings( new Settings( array( 'rsd_link' ) ) );
		$args = $this->page->import_json( '{"plugin":"other","enabled":[]}' );

		self::assertSame( array( 'debloat_notice' => 'import_error', 'code' => 'wrong_plugin' ), $args );
		self::assertSame( array( 'rsd_link' ), WpStubs::$options[ Settings::OPTION ]['enabled'] );
	}

	public function test_import_requires_the_capability(): void {
		WpStubs::$can = false;
		$this->post( 'wp_debloat_import', array( 'debloat_import' => '{"enabled":["emojis"]}' ) );

		$this->expectException( WpDieException::class );
		$this->page->handle_import();
	}

	public function test_export_json(): void {
		$this->plugin->save_settings( new Settings( array( 'emojis' ) ) );
		$data = json_decode( $this->page->export_json(), true );

		self::assertSame( array( 'emojis' ), $data['enabled'] );
		self::assertSame( Plugin::VERSION, $data['version'] );
	}

	public function test_export_requires_the_capability(): void {
		WpStubs::$can = false;

		$this->expectException( WpDieException::class );
		$this->page->handle_export();
	}

	public function test_render_lists_every_tweak_with_risk_badges_and_nonces(): void {
		$this->plugin->save_settings( new Settings( array( 'file_editor' ) ) );
		ob_start();
		$this->page->render();
		$html = (string) ob_get_clean();

		foreach ( $this->plugin->registry()->ids() as $id ) {
			self::assertStringContainsString( 'name="wp_debloat[enabled][' . $id . ']"', $html, $id );
		}
		self::assertStringContainsString( 'wp-debloat-risk-high', $html );
		self::assertStringContainsString( 'value="nonce-wp_debloat_save"', $html );
		self::assertStringContainsString( 'value="nonce-wp_debloat_preset"', $html );
		self::assertStringContainsString( 'value="nonce-wp_debloat_import"', $html );
		self::assertStringContainsString( '_wpnonce=nonce-wp_debloat_export', $html );
		self::assertStringContainsString( 'id="wp-debloat-search"', $html );
		self::assertMatchesRegularExpression( '/id="wp-debloat-tweak-file-editor"[^>]*checked/', $html );
		self::assertStringContainsString( 'DISALLOW_FILE_EDIT', $html, 'Notice of an enabled tweak.' );
		self::assertStringContainsString( 'name="wp_debloat[values][revisions_limit]" value="5"', $html );
		self::assertStringContainsString( '<option value="everyone"', $html );
		self::assertStringContainsString( '1 of ' . $this->plugin->registry()->count() . ' tweaks are on', $html );
	}

	public function test_render_shows_escaped_result_notices(): void {
		$_GET = array(
			'debloat_notice' => 'import_error',
			'code'           => 'invalid_json<script>',
		);
		ob_start();
		$this->page->render();
		$html = (string) ob_get_clean();

		self::assertStringNotContainsString( '<script>', $html );
		self::assertStringContainsString( 'notice-error', $html );
	}

	public function test_render_prints_nothing_without_the_capability(): void {
		WpStubs::$can = false;
		ob_start();
		$this->page->render();

		self::assertSame( '', ob_get_clean() );
	}

	public function test_action_link(): void {
		$links = apply_filters( 'plugin_action_links_wp-debloat/wp-debloat.php', array( '<a>Deactivate</a>' ) );

		self::assertCount( 2, $links );
		self::assertStringContainsString( 'options-general.php?page=wp-debloat', $links[0] );
	}
}
