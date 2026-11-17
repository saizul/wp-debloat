<?php
/**
 * Plugin Name:       Debloat & Harden
 * Plugin URI:        https://github.com/saizul/wp-debloat
 * Description:       Turn off WordPress bloat and close common information leaks with safe, individually switchable tweaks. Presets, import/export and WP-CLI included.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Saizul Amin
 * Author URI:        https://saizul.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-debloat
 * Domain Path:       /languages
 *
 * @package Saizul\Debloat
 */

defined( 'ABSPATH' ) || exit;

if ( PHP_VERSION_ID < 70400 ) {
	add_action(
		'admin_notices',
		static function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Debloat & Harden requires PHP 7.4 or newer.', 'wp-debloat' ) . '</p></div>';
			}
		}
	);
	return;
}

define( 'WP_DEBLOAT_FILE', __FILE__ );
define( 'WP_DEBLOAT_VERSION', '0.1.0' );

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// PSR-4 autoloader for installs without Composer (the release zip).
	spl_autoload_register(
		static function ( $class_name ) {
			$prefix = 'Saizul\\Debloat\\';
			if ( 0 !== strncmp( $class_name, $prefix, strlen( $prefix ) ) ) {
				return;
			}
			$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

\Saizul\Debloat\Plugin::boot( __FILE__ );
