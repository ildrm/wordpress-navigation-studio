<?php
/**
 * Per-user draft persistence with optimistic versions.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Infrastructure;

use NavigationStudio\Domain\Navigation;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class DraftRepository {
	/** @return array<string,mixed>|null */
	public function get( string $navigation_key, int $user_id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'navstudio_drafts';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT version, payload, checksum, updated_at FROM $table WHERE navigation_key = %s AND user_id = %d", $navigation_key, $user_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $row ) {
			return null; }
		$payload = json_decode( (string) $row['payload'], true );
		return is_array( $payload ) ? array(
			'version'    => (int) $row['version'],
			'checksum'   => $row['checksum'],
			'updatedAt'  => mysql_to_rfc3339( $row['updated_at'] ),
			'navigation' => $payload,
		) : null;
	}

	/** @return array<string,mixed> */
	public function save( Navigation $navigation, int $user_id, ?int $expected_version ): array {
		global $wpdb;
		$table   = $wpdb->prefix . 'navstudio_drafts';
		$current = $this->get( $navigation->key(), $user_id );
		if (
			( null === $current && null !== $expected_version ) ||
			( null !== $current && $expected_version !== $current['version'] )
		) {
			throw new RuntimeException( 'Draft version conflict.', 409 );
		}
		$version = $current ? (int) $current['version'] + 1 : 1;
		$payload = wp_json_encode( $navigation, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$data    = array(
			'navigation_key' => $navigation->key(),
			'user_id'        => $user_id,
			'version'        => $version,
			'payload'        => $payload,
			'checksum'       => $navigation->checksum(),
			'updated_at'     => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%d', '%d', '%s', '%s', '%s' );
		if ( $current ) {
			$result = $wpdb->update(
				$table,
				$data,
				array(
					'navigation_key' => $navigation->key(),
					'user_id'        => $user_id,
					'version'        => $expected_version,
				),
				$formats,
				array( '%s', '%d', '%d' )
			);
			if ( 1 !== $result ) {
				throw new RuntimeException( 'Draft version conflict.', 409 );
			}
		} else {
			$result = $wpdb->insert( $table, $data, $formats );
			if ( false === $result ) {
				throw new RuntimeException( 'The draft could not be saved.' );
			}
		}
		return $this->get( $navigation->key(), $user_id ) ?? array();
	}

	public function delete( string $navigation_key, int $user_id ): void {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'navstudio_drafts',
			array(
				'navigation_key' => $navigation_key,
				'user_id'        => $user_id,
			),
			array( '%s', '%d' )
		);
	}

	public function has_any( string $navigation_key ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'navstudio_drafts';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM $table WHERE navigation_key = %s LIMIT 1", $navigation_key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Return every navigation key that currently has at least one draft.
	 *
	 * @return array<string,true>
	 */
	public function keys_with_drafts(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'navstudio_drafts';
		$keys  = $wpdb->get_col( "SELECT DISTINCT navigation_key FROM $table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

		return array_fill_keys( array_map( 'strval', $keys ), true );
	}
}
