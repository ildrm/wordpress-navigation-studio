<?php
/**
 * Activation and schema lifecycle.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Infrastructure;

defined( 'ABSPATH' ) || exit;

final class Activator {
	public static function maybe_upgrade(): void {
		if ( NAVSTUDIO_SCHEMA_VERSION !== (string) get_option( 'navstudio_schema_version', '' ) ) {
			self::install_site();
		}
	}

	public static function activate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::install_site();
				restore_current_blog();
			}
			return;
		}
		self::install_site();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'navstudio_daily_maintenance' );
	}

	private static function install_site(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$drafts  = $wpdb->prefix . 'navstudio_drafts';
		$history = $wpdb->prefix . 'navstudio_revisions';
		$tpls    = $wpdb->prefix . 'navstudio_templates';

		dbDelta(
			"CREATE TABLE $drafts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			navigation_key varchar(191) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			payload longtext NOT NULL,
			checksum char(64) NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY navigation_user (navigation_key,user_id),
			KEY updated_at (updated_at)
		) $charset;"
		);

		dbDelta(
			"CREATE TABLE $history (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			navigation_key varchar(191) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			label varchar(255) NOT NULL DEFAULT '',
			payload longtext NOT NULL,
			checksum char(64) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY navigation_created (navigation_key,created_at)
		) $charset;"
		);

		dbDelta(
			"CREATE TABLE $tpls (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			scope varchar(20) NOT NULL DEFAULT 'site',
			payload longtext NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) $charset;"
		);

		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'manage_navigation_studio' );
			$role->add_cap( 'publish_navigation_studio' );
			$role->add_cap( 'manage_navigation_templates' );
		}

		update_option( 'navstudio_schema_version', NAVSTUDIO_SCHEMA_VERSION, false );
		if ( ! wp_next_scheduled( 'navstudio_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'navstudio_daily_maintenance' );
		}
	}
}
