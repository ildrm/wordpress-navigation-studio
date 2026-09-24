<?php
/** Verify the packaged ZIP in a disposable WordPress container. */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$archive = new ZipArchive();
if ( true !== $archive->open( '/tmp/navigation-studio.zip' ) ) {
	throw new RuntimeException( 'Could not open the release archive.' );
}
if ( ! $archive->extractTo( '/var/www/html/wp-content/plugins' ) ) {
	throw new RuntimeException( 'Could not extract the release archive.' );
}
$archive->close();

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$result = activate_plugin( 'navigation-studio/navigation-studio.php' );
if ( is_wp_error( $result ) ) {
	throw new RuntimeException( $result->get_error_message() );
}

global $wpdb;
$tables = array();
foreach ( array( 'navstudio_drafts', 'navstudio_revisions', 'navstudio_templates' ) as $suffix ) {
	$table             = $wpdb->prefix . $suffix;
	$tables[ $suffix ] = $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
}

echo wp_json_encode(
	array(
		'wordpress'    => get_bloginfo( 'version' ),
		'php'          => PHP_VERSION,
		'pluginActive' => is_plugin_active( 'navigation-studio/navigation-studio.php' ),
		'tables'       => $tables,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;
