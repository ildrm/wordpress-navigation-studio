<?php
/**
 * Optional uninstall cleanup for Navigation Studio.
 *
 * @package NavigationStudio
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! get_option( 'navstudio_delete_data_on_uninstall', false ) ) {
	return;
}

global $wpdb;

$tables = array( 'navstudio_drafts', 'navstudio_revisions', 'navstudio_templates' );
foreach ( $tables as $suffix ) {
	$table = $wpdb->prefix . $suffix;
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted prefix and constant allowlist.
	$wpdb->query( "DROP TABLE IF EXISTS `$table`" );
}

delete_option( 'navstudio_schema_version' );
delete_option( 'navstudio_settings' );
delete_option( 'navstudio_delete_data_on_uninstall' );
delete_site_option( 'navstudio_schema_version' );
