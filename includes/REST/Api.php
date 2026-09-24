<?php
/**
 * Versioned REST API.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\REST;

use NavigationStudio\Application\ContentSearch;
use NavigationStudio\Application\HealthChecker;
use NavigationStudio\Application\TransferService;
use NavigationStudio\Domain\Navigation;
use NavigationStudio\Infrastructure\DraftRepository;
use NavigationStudio\Infrastructure\LockManager;
use NavigationStudio\Infrastructure\NativeRepository;
use NavigationStudio\Infrastructure\RevisionRepository;
use NavigationStudio\Infrastructure\TemplateRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class Api {
	private const NS = 'navigation-studio/v1';
	/** @var NativeRepository */ private $native;
	/** @var DraftRepository */ private $drafts;
	/** @var RevisionRepository */ private $revisions;
	/** @var LockManager */ private $locks;

	public function __construct( NativeRepository $native, DraftRepository $drafts, RevisionRepository $revisions, LockManager $locks ) {
		$this->native    = $native;
		$this->drafts    = $drafts;
		$this->revisions = $revisions;
		$this->locks     = $locks;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) ); }

	public function routes(): void {
		$read  = array( $this, 'can_read' );
		$edit  = array( $this, 'can_edit' );
		$admin = array( $this, 'can_admin' );

		register_rest_route(
			self::NS,
			'/menus',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'menus' ),
					'permission_callback' => $read,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_menu' ),
					'permission_callback' => $edit,
					'args'                => array(
						'name'       => array(
							'required'  => true,
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 191,
						),
						'sourceType' => array(
							'required' => true,
							'type'     => 'string',
							'enum'     => array( 'classic', 'block' ),
						),
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<key>(classic|block):\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'menu' ),
					'permission_callback' => $read,
				),
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<key>(classic|block):\d+)/draft',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'draft' ),
					'permission_callback' => $edit,
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'save_draft' ),
					'permission_callback' => $edit,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'discard_draft' ),
					'permission_callback' => $edit,
				),
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<key>(classic|block):\d+)/publish',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'publish' ),
				'permission_callback' => array( $this, 'can_publish' ),
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<key>(classic|block):\d+)/revisions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'revision_list' ),
				'permission_callback' => $read,
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<key>(classic|block):\d+)/revisions/(?P<id>\d+)/restore',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'restore_revision' ),
				'permission_callback' => array( $this, 'can_publish' ),
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<key>(classic|block):\d+)/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'health' ),
				'permission_callback' => $read,
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<key>(classic|block):\d+)/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export' ),
				'permission_callback' => $read,
			)
		);
		register_rest_route(
			self::NS,
			'/menus/(?P<key>(classic|block):\d+)/lock',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'lock' ),
					'permission_callback' => $edit,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'unlock' ),
					'permission_callback' => $edit,
				),
			)
		);
		register_rest_route(
			self::NS,
			'/content',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'content' ),
				'permission_callback' => $read,
				'args'                => array(
					'search'   => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'kind'     => array(
						'type'    => 'string',
						'default' => 'all',
						'enum'    => array( 'all', 'post', 'taxonomy' ),
					),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 20,
						'minimum' => 1,
						'maximum' => 50,
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/import/preview',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_preview' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/import/commit',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_commit' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/templates',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'templates' ),
					'permission_callback' => $read,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_template' ),
					'permission_callback' => $admin,
				),
			)
		);
		register_rest_route(
			self::NS,
			'/templates/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_template' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'settings' ),
					'permission_callback' => $admin,
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => $admin,
				),
			)
		);
		register_rest_route(
			self::NS,
			'/diagnostics',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'diagnostics' ),
				'permission_callback' => $admin,
			)
		);
	}

	public function can_read(): bool {
		return current_user_can( 'edit_theme_options' ) || current_user_can( 'manage_navigation_studio' ); }
	public function can_edit(): bool {
		return current_user_can( 'edit_theme_options' ) || current_user_can( 'manage_navigation_studio' ); }
	public function can_publish(): bool {
		return current_user_can( 'edit_theme_options' ) || current_user_can( 'publish_navigation_studio' ); }
	public function can_admin(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_navigation_templates' ); }

	public function menus(): WP_REST_Response {
		$draft_keys = $this->drafts->keys_with_drafts();
		$items      = array_map(
			static function ( $item ) use ( $draft_keys ) {
				$item['hasDraft'] = isset( $draft_keys[ $item['key'] ] );
				return $item;
			},
			$this->native->all()
		);
		return new WP_REST_Response( array( 'items' => $items ), 200 );
	}

	/** @return WP_REST_Response|WP_Error */
	public function create_menu( WP_REST_Request $request ) {
		try {
			$type = (string) $request['sourceType'];
			$nav  = new Navigation( $type . ':0', (string) $request['name'], $type, 0, array() );
			return new WP_REST_Response( $this->native->publish( $nav ), 201 );
		} catch ( \Throwable $error ) {
			return $this->error( $error ); }
	}

	/** @return WP_REST_Response|WP_Error */
	public function menu( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->native->get( (string) $request['key'] ), 200 ); } catch ( \Throwable $error ) {
			return $this->error( $error, 404 ); }
	}

	public function draft( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( $this->drafts->get( (string) $request['key'], get_current_user_id() ), 200 );
	}

	/** @return WP_REST_Response|WP_Error */
	public function save_draft( WP_REST_Request $request ) {
		try {
			$data = (array) $request->get_json_params();
			$nav  = Navigation::from_array( (array) ( $data['navigation'] ?? array() ) );
			if ( $nav->key() !== (string) $request['key'] ) {
				return new WP_Error( 'navstudio_key_mismatch', __( 'The payload does not match this navigation.', 'navigation-studio' ), array( 'status' => 400 ) ); }
			return new WP_REST_Response( $this->drafts->save( $nav, get_current_user_id(), isset( $data['version'] ) ? (int) $data['version'] : null ), 200 );
		} catch ( \Throwable $error ) {
			return $this->error( $error ); }
	}

	public function discard_draft( WP_REST_Request $request ): WP_REST_Response {
		$this->drafts->delete( (string) $request['key'], get_current_user_id() );
		return new WP_REST_Response( null, 204 ); }

	/** @return WP_REST_Response|WP_Error */
	public function publish( WP_REST_Request $request ) {
		try {
			$key       = (string) $request['key'];
			$published = $this->native->get( $key );
			$data      = (array) $request->get_json_params();
			$expected  = (string) ( $data['publishedChecksum'] ?? '' );
			if ( $expected && ! hash_equals( $published->checksum(), $expected ) ) {
				return new WP_Error(
					'navstudio_publish_conflict',
					__( 'This navigation changed after you opened it. Your draft is still safe.', 'navigation-studio' ),
					array(
						'status'    => 409,
						'published' => $published,
					)
				); }
			$nav = ! empty( $data['navigation'] ) ? Navigation::from_array( (array) $data['navigation'] ) : $this->draft_navigation( $key );
			$this->revisions->create( $published, get_current_user_id(), __( 'Before publish', 'navigation-studio' ) );
			$saved = $this->native->publish( $nav );
			$this->revisions->create( $saved, get_current_user_id(), __( 'Published', 'navigation-studio' ) );
			$this->drafts->delete( $key, get_current_user_id() );
			do_action( 'navstudio_navigation_published', $saved, $published );
			return new WP_REST_Response( $saved, 200 );
		} catch ( \Throwable $error ) {
			return $this->error( $error ); }
	}

	public function revision_list( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( array( 'items' => $this->revisions->list( (string) $request['key'] ) ), 200 ); }
	/** @return WP_REST_Response|WP_Error */
	public function restore_revision( WP_REST_Request $request ) {
		try {
			$current = $this->native->get( (string) $request['key'] );
			$restore = $this->revisions->get( (int) $request['id'] );
			if ( $current->key() !== $restore->key() ) {
				return new WP_Error( 'navstudio_revision_mismatch', __( 'Revision belongs to another navigation.', 'navigation-studio' ), array( 'status' => 400 ) ); }
			$this->revisions->create( $current, get_current_user_id(), __( 'Before revision restore', 'navigation-studio' ) );
			return new WP_REST_Response( $this->native->publish( $restore ), 200 );
		} catch ( \Throwable $error ) {
			return $this->error( $error ); }
	}

	/** @return WP_REST_Response|WP_Error */
	public function health( WP_REST_Request $request ) {
		try {
			$issues = ( new HealthChecker() )->check( $this->native->get( (string) $request['key'] ) );
			return new WP_REST_Response(
				array(
					'issues' => $issues,
					'counts' => array_count_values( array_column( $issues, 'severity' ) ),
				),
				200
			);
		} catch ( \Throwable $error ) {
			return $this->error( $error ); } }
	/** @return WP_REST_Response|WP_Error */
	public function export( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( ( new TransferService() )->export( $this->native->get( (string) $request['key'] ) ), 200 );
		} catch ( \Throwable $error ) {
			return $this->error( $error ); } }
	public function lock( WP_REST_Request $request ): WP_REST_Response {
		$lock = $this->locks->acquire( (string) $request['key'], get_current_user_id() );
		return new WP_REST_Response( $lock, $lock['owned'] ? 200 : 409 ); }
	public function unlock( WP_REST_Request $request ): WP_REST_Response {
		$this->locks->release( (string) $request['key'], get_current_user_id() );
		return new WP_REST_Response( null, 204 ); }

	public function content( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( ( new ContentSearch() )->search( (string) $request['search'], (string) $request['kind'], (int) $request['page'], (int) $request['per_page'] ), 200 ); }
	/** @return WP_REST_Response|WP_Error */
	public function import_preview( WP_REST_Request $request ) {
		try {
			$parsed = ( new TransferService() )->parse( (string) $request->get_param( 'content' ) );
			$nav    = $parsed['navigation'];
			return new WP_REST_Response(
				array(
					'manifest'   => $parsed['manifest'],
					'summary'    => array(
						'name'       => $nav->name(),
						'sourceType' => $nav->source_type(),
						'itemCount'  => count( $nav->nodes() ),
					),
					'navigation' => $nav,
				),
				200
			); } catch ( \Throwable $error ) {
			return $this->error( $error ); }
	}
	/** @return WP_REST_Response|WP_Error */
	public function import_commit( WP_REST_Request $request ) {
		try {
			$parsed            = ( new TransferService() )->parse( (string) $request->get_param( 'content' ) );
			$requested_type    = $request->get_param( 'sourceType' );
			$source_type       = sanitize_key( (string) ( $requested_type ? $requested_type : $parsed['navigation']->source_type() ) );
			$raw               = $parsed['navigation']->jsonSerialize();
			$raw['key']        = $source_type . ':0';
			$raw['sourceType'] = $source_type;
			$raw['sourceId']   = 0;
			return new WP_REST_Response( $this->native->publish( Navigation::from_array( $raw ) ), 201 ); } catch ( \Throwable $error ) {
			return $this->error( $error ); }
	}

	public function templates(): WP_REST_Response {
		return new WP_REST_Response( array( 'items' => ( new TemplateRepository() )->all() ), 200 ); }
	/** @return WP_REST_Response|WP_Error */
	public function save_template( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( ( new TemplateRepository() )->save( (string) $request->get_param( 'name' ), (array) $request->get_param( 'payload' ), get_current_user_id() ), 201 );
		} catch ( \Throwable $error ) {
			return $this->error( $error ); } }
	public function delete_template( WP_REST_Request $request ): WP_REST_Response {
		( new TemplateRepository() )->delete( (int) $request['id'] );
		return new WP_REST_Response( null, 204 ); }

	public function settings(): WP_REST_Response {
		return new WP_REST_Response( $this->get_settings(), 200 ); }
	public function save_settings( WP_REST_Request $request ): WP_REST_Response {
		$raw      = (array) $request->get_json_params();
		$settings = array(
			'enhancedRendering'     => ! empty( $raw['enhancedRendering'] ),
			'externalLinkChecks'    => ! empty( $raw['externalLinkChecks'] ),
			'revisionRetention'     => min( 500, max( 10, absint( $raw['revisionRetention'] ?? 100 ) ) ),
			'deleteDataOnUninstall' => ! empty( $raw['deleteDataOnUninstall'] ),
		);
		update_option( 'navstudio_settings', $settings, false );
		update_option( 'navstudio_delete_data_on_uninstall', $settings['deleteDataOnUninstall'], false );
		return new WP_REST_Response( $settings, 200 );
	}
	public function diagnostics(): WP_REST_Response {
		global $wpdb;
		return new WP_REST_Response(
			array(
				'pluginVersion'      => NAVSTUDIO_VERSION,
				'schemaVersion'      => get_option( 'navstudio_schema_version' ),
				'wordpressVersion'   => get_bloginfo( 'version' ),
				'phpVersion'         => PHP_VERSION,
				'multisite'          => is_multisite(),
				'theme'              => wp_get_theme()->get( 'Name' ),
				'permalinkStructure' => get_option( 'permalink_structure' ),
				'tableStatus'        => array(
					'drafts'    => (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'navstudio_drafts' ) ) ),
					'revisions' => (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'navstudio_revisions' ) ) ),
				),
				'integrations'       => array(
					'woocommerce' => class_exists( 'WooCommerce' ),
					'polylang'    => function_exists( 'pll_current_language' ),
					'wpml'        => defined( 'ICL_SITEPRESS_VERSION' ),
				),
			),
			200
		);
	}

	private function draft_navigation( string $key ): Navigation {
		$draft = $this->drafts->get( $key, get_current_user_id() );
		if ( ! $draft ) {
			throw new \RuntimeException( 'No draft is available to publish.' ); }
		return Navigation::from_array( (array) $draft['navigation'] );
	}
	/** @return array<string,mixed> */
	private function get_settings(): array {
		return wp_parse_args(
			get_option( 'navstudio_settings', array() ),
			array(
				'enhancedRendering'     => false,
				'externalLinkChecks'    => false,
				'revisionRetention'     => 100,
				'deleteDataOnUninstall' => false,
			)
		); }
	private function error( \Throwable $error, int $fallback = 400 ): WP_Error {
		$status = in_array( (int) $error->getCode(), array( 400, 404, 409, 422, 500 ), true ) ? (int) $error->getCode() : $fallback;
		return new WP_Error( 'navstudio_request_failed', $error->getMessage(), array( 'status' => $status ) ); }
}
