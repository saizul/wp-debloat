<?php
declare(strict_types=1);

namespace Saizul\Debloat\Tests\Unit\Tweaks;

use Saizul\Debloat\Tests\WpTestCase;
use WpStubs;

final class AdminTweaksTest extends WpTestCase {

	public function test_dashboard_widgets(): void {
		add_action( 'welcome_panel', 'wp_welcome_panel' );
		self::tweak( 'dashboard_widgets' )->apply();
		do_action( 'wp_dashboard_setup' );
		do_action( 'admin_init' );

		self::assertSame( array( 'dashboard_quick_press@dashboard:side', 'dashboard_activity@dashboard:normal' ), WpStubs::calls( 'meta_box' ) );
		self::assertFalse( self::hooked( 'welcome_panel', 'wp_welcome_panel' ) );
	}

	public function test_dashboard_news_on_site_and_network(): void {
		self::tweak( 'dashboard_news' )->apply();
		do_action( 'wp_dashboard_setup' );
		do_action( 'wp_network_dashboard_setup' );

		self::assertSame( array( 'dashboard_primary@dashboard:side', 'dashboard_primary@dashboard-network:side' ), WpStubs::calls( 'meta_box' ) );
	}

	public function test_admin_bar_hidden_for_non_admins_by_default(): void {
		self::tweak( 'admin_bar_front_end' )->apply();

		WpStubs::$can = array( 'manage_options' => false );
		self::assertFalse( apply_filters( 'show_admin_bar', true ) );
		WpStubs::$can = array( 'manage_options' => true );
		self::assertTrue( apply_filters( 'show_admin_bar', true ) );
		self::assertFalse( apply_filters( 'show_admin_bar', false ), 'Never forces the bar on.' );
	}

	public function test_admin_bar_hidden_for_everyone(): void {
		WpStubs::$can = true;
		self::tweak( 'admin_bar_front_end', 'everyone' )->apply();

		self::assertFalse( apply_filters( 'show_admin_bar', true ) );
	}

	public function test_update_nags_removed_only_for_users_who_cannot_update(): void {
		add_action( 'admin_notices', 'update_nag', 3 );
		add_action( 'admin_notices', 'maintenance_nag', 10 );
		add_action( 'network_admin_notices', 'update_nag', 3 );
		$tweak = self::tweak( 'update_nags' );

		WpStubs::$can = array( 'update_core' => true );
		$tweak->remove();
		self::assertTrue( self::hooked( 'admin_notices', 'update_nag', 3 ) );

		WpStubs::$can = array( 'update_core' => false );
		$tweak->remove();
		self::assertFalse( self::hooked( 'admin_notices', 'update_nag' ) );
		self::assertFalse( self::hooked( 'admin_notices', 'maintenance_nag' ) );
		self::assertFalse( self::hooked( 'network_admin_notices', 'update_nag' ) );
	}

	public function test_block_directory_and_admin_email_check(): void {
		add_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
		self::tweak( 'block_directory' )->apply();
		self::tweak( 'admin_email_check' )->apply();

		self::assertFalse( self::hooked( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' ) );
		self::assertSame( 0, apply_filters( 'admin_email_check_interval', 15552000 ) );
	}
}
