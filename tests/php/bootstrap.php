<?php
/**
 * WordPress test bootstrap.
 *
 * @package NavigationStudio
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI-only test bootstrap error.
	fwrite( STDERR, "WordPress test library not found. Set WP_TESTS_DIR or run inside wp-env tests-cli.\n" );
	exit( 1 );
}
require_once $_tests_dir . '/includes/functions.php';
tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__, 2 ) . '/navigation-studio.php';
	}
);
require $_tests_dir . '/includes/bootstrap.php';
