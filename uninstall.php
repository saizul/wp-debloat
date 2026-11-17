<?php
/**
 * Removes the plugin's data when it is deleted from the Plugins screen.
 *
 * Tweaks only add hooks at run time, so deleting the single settings option
 * restores WordPress defaults completely.
 *
 * @package Saizul\Debloat
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete the options of the current site.
 */
function wp_debloat_uninstall_site() {
	delete_option( 'wp_debloat_settings' );
}

if ( is_multisite() ) {
	$wp_debloat_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $wp_debloat_site_ids as $wp_debloat_site_id ) {
		switch_to_blog( (int) $wp_debloat_site_id );
		wp_debloat_uninstall_site();
		restore_current_blog();
	}
} else {
	wp_debloat_uninstall_site();
}
