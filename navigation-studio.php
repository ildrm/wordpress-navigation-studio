<?php
/**
 * Plugin Name:       Navigation Studio
 * Description:       A visual, accessible navigation management system for classic and block WordPress sites.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Author:            Navigation Studio Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       navigation-studio
 * Domain Path:       /languages
 *
 * @package NavigationStudio
 */

defined( 'ABSPATH' ) || exit;

define( 'NAVSTUDIO_VERSION', '1.0.0' );
define( 'NAVSTUDIO_SCHEMA_VERSION', '1' );
define( 'NAVSTUDIO_FILE', __FILE__ );
define( 'NAVSTUDIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'NAVSTUDIO_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'NavigationStudio\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = NAVSTUDIO_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( 'NavigationStudio\\Infrastructure\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'NavigationStudio\\Infrastructure\\Activator', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		NavigationStudio\Plugin::instance()->boot();
	}
);
