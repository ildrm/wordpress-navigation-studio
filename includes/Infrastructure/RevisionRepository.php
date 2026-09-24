<?php
/**
 * Immutable recovery snapshots.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Infrastructure;

use NavigationStudio\Domain\Navigation;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class RevisionRepository {
	public function create( Navigation $navigation, int $user_id, string $label ): int {
		global $wpdb;
		$result = $wpdb->insert(
			$wpdb->prefix . 'navstudio_revisions',
			array(
				'navigation_key' => $navigation->key(),
				'user_id'        => $user_id,
				'label'          => sanitize_text_field( $label ),
				'payload'        => wp_json_encode( $navigation, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
				'checksum'       => $navigation->checksum(),
				'created_at'     => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s' )
		);
		if ( false === $result ) {
			throw new RuntimeException( 'The recovery revision could not be created.' ); }
		$this->prune( $navigation->key() );
		return (int) $wpdb->insert_id;
	}

	/** @return array<int,array<string,mixed>> */
	public function list( string $navigation_key, int $limit = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'navstudio_revisions';
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, label, checksum, created_at FROM $table WHERE navigation_key = %s ORDER BY id DESC LIMIT %d", $navigation_key, min( 100, max( 1, $limit ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array_map(
			static function ( $row ) {
				$row['id']         = (int) $row['id'];
				$row['user_id']    = (int) $row['user_id'];
				$row['created_at'] = mysql_to_rfc3339( $row['created_at'] );
				return $row;
			},
			$rows ? $rows : array()
		);
	}

	public function get( int $id ): Navigation {
		global $wpdb;
		$table   = $wpdb->prefix . 'navstudio_revisions';
		$payload = $wpdb->get_var( $wpdb->prepare( "SELECT payload FROM $table WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data    = json_decode( (string) $payload, true );
		if ( ! is_array( $data ) ) {
			throw new RuntimeException( 'Revision not found.' ); }
		return Navigation::from_array( $data );
	}

	private function prune( string $navigation_key ): void {
		global $wpdb;
		$retention = min( 500, max( 10, (int) apply_filters( 'navstudio_revision_retention', 100, $navigation_key ) ) );
		$table     = $wpdb->prefix . 'navstudio_revisions';
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE navigation_key = %s AND id NOT IN (SELECT id FROM (SELECT id FROM $table WHERE navigation_key = %s ORDER BY id DESC LIMIT %d) kept)", $navigation_key, $navigation_key, $retention ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
