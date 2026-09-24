<?php
/**
 * Reusable navigation template repository.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Infrastructure;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class TemplateRepository {
	/** @return array<int,array<string,mixed>> */
	public function all(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'navstudio_templates';
		$rows  = $wpdb->get_results( "SELECT id, name, slug, scope, user_id, created_at, updated_at FROM $table ORDER BY name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $rows ? $rows : array();
	}
	/**
	 * @param string              $name Template name.
	 * @param array<string,mixed> $payload Template payload.
	 * @param int                 $user_id Owner ID.
	 * @param int                 $id Existing template ID.
	 * @return array<string,mixed>
	 */
	public function save( string $name, array $payload, int $user_id, int $id = 0 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'navstudio_templates';
		$row   = array(
			'name'       => sanitize_text_field( $name ),
			'slug'       => sanitize_title( $name ),
			'scope'      => 'site',
			'payload'    => wp_json_encode( $payload ),
			'user_id'    => $user_id,
			'updated_at' => current_time( 'mysql', true ),
		);
		if ( $id ) {
			$result = $wpdb->update( $table, $row, array( 'id' => $id ), array( '%s', '%s', '%s', '%s', '%d', '%s' ), array( '%d' ) ); } else {
			$row['created_at'] = current_time( 'mysql', true );
			$result            = $wpdb->insert( $table, $row, array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' ) );
			$id                = (int) $wpdb->insert_id; }
			if ( false === $result ) {
				throw new RuntimeException( 'Template could not be saved.' ); }
			return $this->get( $id );
	}
	/** @return array<string,mixed> */
	public function get( int $id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'navstudio_templates';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $row ) {
			throw new RuntimeException( 'Template not found.' ); }
		$row['payload'] = json_decode( $row['payload'], true );
		return $row;
	}
	public function delete( int $id ): void {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'navstudio_templates', array( 'id' => $id ), array( '%d' ) ); }
}
