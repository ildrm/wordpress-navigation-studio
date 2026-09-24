<?php
/**
 * Versioned portable import and export.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Application;

use NavigationStudio\Domain\Navigation;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class TransferService {
	public const SCHEMA_VERSION = 1;
	public const MAX_BYTES      = 5242880;
	public const MAX_NODES      = 5000;

	/** @return array<string,mixed> */
	public function export( Navigation $navigation ): array {
		return array(
			'schema'           => 'navigation-studio',
			'schemaVersion'    => self::SCHEMA_VERSION,
			'pluginVersion'    => NAVSTUDIO_VERSION,
			'wordpressVersion' => get_bloginfo( 'version' ),
			'exportedAt'       => gmdate( 'c' ),
			'sourceSite'       => hash_hmac( 'sha256', home_url( '/' ), wp_salt( 'auth' ) ),
			'requires'         => $this->required_integrations( $navigation ),
			'navigation'       => $navigation,
		);
	}

	/** @return array<string,mixed> */
	public function parse( string $json ): array {
		if ( strlen( $json ) > self::MAX_BYTES ) {
			throw new RuntimeException( 'Import exceeds the 5 MB safety limit.' ); }
		$data = json_decode( $json, true, 100 );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			throw new RuntimeException( 'Import is not valid UTF-8 JSON.' ); }
		if ( 'navigation-studio' !== ( $data['schema'] ?? '' ) ) {
			throw new RuntimeException( 'This is not a Navigation Studio export.' ); }
		$version = (int) ( $data['schemaVersion'] ?? 0 );
		if ( $version < 1 || $version > self::SCHEMA_VERSION ) {
			throw new RuntimeException( 'The export schema version is not supported.' ); }
		$navigation = (array) ( $data['navigation'] ?? array() );
		if ( count( (array) ( $navigation['nodes'] ?? array() ) ) > self::MAX_NODES ) {
			throw new RuntimeException( 'Import contains too many nodes.' ); }
		$source_type            = (string) ( $navigation['sourceType'] ?? '' );
		$navigation['key']      = $source_type . ':0';
		$navigation['sourceId'] = 0;
		return array(
			'manifest'   => array_diff_key( $data, array( 'navigation' => true ) ),
			'navigation' => Navigation::from_array( $navigation ),
		);
	}

	/** @return array<int,string> */
	private function required_integrations( Navigation $navigation ): array {
		$required = array();
		foreach ( $navigation->nodes() as $node ) {
			$data = $node->jsonSerialize();
			if ( 0 === strpos( (string) $data['objectType'], 'product' ) ) {
				$required['woocommerce'] = 'woocommerce'; }
		}
		return array_values( $required );
	}
}
