<?php
/**
 * Disposable runtime smoke test for the isolated Docker site.
 *
 * Run only from the command line inside a WordPress container.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$wp_load = '/var/www/html/wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
}
if ( ! is_readable( $wp_load ) ) {
	throw new RuntimeException( 'WordPress bootstrap was not found.' );
}

require $wp_load;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

function navstudio_smoke_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

/** @param array<string,mixed>|null $body */
function navstudio_smoke_request( string $method, string $route, ?array $body = null ): WP_REST_Response {
	$request = new WP_REST_Request( $method, $route );
	if ( null !== $body ) {
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $body ) );
	}
	return rest_do_request( $request );
}

$plugin = 'navigation-studio/navigation-studio.php';
if ( ! is_plugin_active( $plugin ) ) {
	$result = activate_plugin( $plugin );
	navstudio_smoke_assert( ! is_wp_error( $result ), is_wp_error( $result ) ? $result->get_error_message() : 'Activation failed.' );
}

NavigationStudio\Plugin::instance()->boot();
$admin = get_user_by( 'login', 'admin' );
navstudio_smoke_assert( false !== $admin, 'Administrator account was not found.' );
wp_set_current_user( $admin->ID );

$condition_engine = new NavigationStudio\Application\ConditionEngine();
navstudio_smoke_assert( ! $condition_engine->visible( array( array( 'type' => 'unknown-provider', 'value' => true ) ) ), 'Unknown visibility conditions did not fail closed.' );

global $wpdb;
foreach ( array( 'navstudio_drafts', 'navstudio_revisions', 'navstudio_templates' ) as $suffix ) {
	$table = $wpdb->prefix . $suffix;
	navstudio_smoke_assert( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ), "Missing table: {$suffix}" );
}

$run_id = substr( wp_generate_uuid4(), 0, 8 );
$node = new NavigationStudio\Domain\Node(
	array(
		'id'         => 'smoke-node-0001',
		'label'      => 'Documentation',
		'url'        => 'https://example.test/docs',
		'attributes' => array( 'className' => 'docs-link' ),
		'appearance' => array( 'badgeText' => 'New' ),
		'responsive' => array( 'desktop' => true, 'tablet' => true, 'mobile' => false ),
	)
);
$native = new NavigationStudio\Infrastructure\NativeRepository();
$classic = $native->publish( new NavigationStudio\Domain\Navigation( 'classic:0', "Smoke Classic {$run_id}", 'classic', 0, array( $node ) ) );
$classic_data = $classic->jsonSerialize();
navstudio_smoke_assert( 1 === count( $classic->nodes() ), 'Classic menu item was not persisted.' );
navstudio_smoke_assert( 'New' === $classic_data['nodes'][0]->jsonSerialize()['appearance']['badgeText'], 'Camel-case metadata did not round trip.' );

$drafts = new NavigationStudio\Infrastructure\DraftRepository();
$draft = $drafts->save( $classic, $admin->ID, null );
navstudio_smoke_assert( 1 === $draft['version'], 'Draft version was not initialized.' );
navstudio_smoke_assert( isset( $drafts->keys_with_drafts()[ $classic->key() ] ), 'Bulk draft lookup did not include the saved draft.' );

$revisions = new NavigationStudio\Infrastructure\RevisionRepository();
$revision_id = $revisions->create( $classic, $admin->ID, 'Smoke revision' );
navstudio_smoke_assert( $revision_id > 0, 'Revision was not saved.' );
navstudio_smoke_assert( $classic->checksum() === $revisions->get( $revision_id )->checksum(), 'Revision did not round trip.' );

$block = $native->publish( new NavigationStudio\Domain\Navigation( 'block:0', "Smoke Block {$run_id}", 'block', 0, array( $node ) ) );
navstudio_smoke_assert( 1 === count( $block->nodes() ), 'Block navigation item was not persisted.' );
$block_data = $block->jsonSerialize();
navstudio_smoke_assert( 'New' === $block_data['nodes'][0]->jsonSerialize()['appearance']['badgeText'], 'Block navigation metadata did not round trip.' );

$classic_item_id = $classic_data['nodes'][0]->jsonSerialize()['source']['nativeId'];
$classic_item    = get_post( $classic_item_id );
$renderer        = new NavigationStudio\Frontend\Renderer();
update_option( 'navstudio_settings', array( 'enhancedRendering' => false ), false );
$plain_attributes = $renderer->link_attributes( array( 'href' => '#' ), $classic_item, null, 0 );
navstudio_smoke_assert( ! isset( $plain_attributes['data-navstudio-badge'] ), 'Frontend decoration was not opt-in.' );
update_option( 'navstudio_settings', array( 'enhancedRendering' => true ), false );
$enhanced_attributes = $renderer->link_attributes( array( 'href' => '#' ), $classic_item, null, 0 );
navstudio_smoke_assert( 'New' === $enhanced_attributes['data-navstudio-badge'], 'Enhanced badge decoration failed.' );
navstudio_smoke_assert( 'mobile' === $enhanced_attributes['data-navstudio-hidden'], 'Responsive decoration failed.' );

do_action( 'rest_api_init' );
$routes = rest_get_server()->get_routes();
navstudio_smoke_assert( isset( $routes['/navigation-studio/v1/menus'] ), 'REST routes were not registered.' );

$menu_response = navstudio_smoke_request( 'GET', '/navigation-studio/v1/menus' );
navstudio_smoke_assert( 200 === $menu_response->get_status(), 'Menu collection endpoint failed.' );
$draft_response = navstudio_smoke_request(
	'PUT',
	'/navigation-studio/v1/menus/' . $classic->key() . '/draft',
	array(
		'navigation' => json_decode( (string) wp_json_encode( $classic ), true ),
		'version'    => 1,
	)
);
navstudio_smoke_assert( 200 === $draft_response->get_status(), 'Draft endpoint failed.' );
navstudio_smoke_assert( 2 === $draft_response->get_data()['version'], 'Draft endpoint did not enforce optimistic versioning.' );
$publish_response = navstudio_smoke_request(
	'POST',
	'/navigation-studio/v1/menus/' . $classic->key() . '/publish',
	array(
		'navigation'        => json_decode( (string) wp_json_encode( $classic ), true ),
		'publishedChecksum' => $classic->checksum(),
	)
);
navstudio_smoke_assert( 200 === $publish_response->get_status(), 'Publish endpoint failed.' );
navstudio_smoke_assert( null === $drafts->get( $classic->key(), $admin->ID ), 'Publish did not clear the user draft.' );
$content_response = navstudio_smoke_request( 'GET', '/navigation-studio/v1/content' );
navstudio_smoke_assert( 200 === $content_response->get_status(), 'Content-search endpoint failed.' );
wp_set_current_user( 0 );
$forbidden_response = navstudio_smoke_request( 'GET', '/navigation-studio/v1/menus' );
navstudio_smoke_assert( $forbidden_response->get_status() >= 400, 'Anonymous REST access was not denied.' );
wp_set_current_user( $admin->ID );

echo wp_json_encode(
	array(
		'wordpress'       => get_bloginfo( 'version' ),
		'php'             => PHP_VERSION,
		'pluginActive'    => is_plugin_active( $plugin ),
		'classicKey'      => $classic->key(),
		'blockKey'        => $block->key(),
		'draftVersion'    => $draft['version'],
		'revisionId'      => $revision_id,
		'restRouteCount'  => count( array_filter( array_keys( $routes ), static fn( $route ) => 0 === strpos( $route, '/navigation-studio/v1/' ) ) ),
		'restPublish'     => $publish_response->get_status(),
		'anonymousDenied' => $forbidden_response->get_status(),
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;
