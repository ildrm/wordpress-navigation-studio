<?php
/**
 * Classic nav-menu adapter.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Adapters;

use NavigationStudio\Domain\Navigation;
use NavigationStudio\Domain\Node;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class ClassicMenuAdapter implements AdapterInterface {
	public function supports( string $source_type ): bool {
		return 'classic' === $source_type; }

	public function read( int $source_id ): Navigation {
		$term = wp_get_nav_menu_object( $source_id );
		if ( ! $term ) {
			throw new RuntimeException( 'Classic menu not found.' );
		}

		$items  = wp_get_nav_menu_items( $term->term_id, array( 'post_status' => 'any' ) );
		$items  = is_array( $items ) ? $items : array();
		$id_map = array();
		foreach ( $items as $item ) {
			$uuid = (string) get_post_meta( $item->ID, '_navstudio_uuid', true );
			if ( '' === $uuid ) {
				$uuid = wp_generate_uuid4();
				update_post_meta( $item->ID, '_navstudio_uuid', $uuid );
			}
			$id_map[ (int) $item->ID ] = $uuid;
		}

		$nodes = array();
		foreach ( $items as $item ) {
			$extra   = get_post_meta( $item->ID, '_navstudio_meta', true );
			$extra   = is_array( $extra ) ? $extra : array();
			$classes = array_values( array_filter( array_map( 'sanitize_html_class', (array) $item->classes ) ) );
			$nodes[] = new Node(
				array_merge(
					$extra,
					array(
						'id'          => $id_map[ (int) $item->ID ],
						'parentId'    => $id_map[ (int) $item->menu_item_parent ] ?? null,
						'label'       => $item->title,
						'type'        => $item->type,
						'objectType'  => $item->object,
						'objectId'    => (int) $item->object_id,
						'url'         => $item->url,
						'description' => $item->description,
						'attributes'  => array(
							'target'    => $item->target,
							'rel'       => $item->xfn,
							'title'     => $item->attr_title,
							'className' => implode( ' ', $classes ),
						),
						'source'      => array( 'nativeId' => (int) $item->ID ),
					)
				)
			);
		}

		$locations = array_keys(
			array_filter(
				get_nav_menu_locations(),
				static function ( $id ) use ( $term ) {
					return (int) $id === (int) $term->term_id;
				}
			)
		);
		return new Navigation( 'classic:' . $term->term_id, $term->name, 'classic', (int) $term->term_id, $nodes, array( 'locations' => $locations ) );
	}

	public function publish( Navigation $navigation ): Navigation {
		$menu_id = $navigation->source_id();
		if ( 0 === $menu_id ) {
			$created = wp_create_nav_menu( $navigation->name() );
			if ( is_wp_error( $created ) ) {
				throw new RuntimeException( $created->get_error_message() );
			}
			$menu_id = (int) $created;
		} else {
			$updated = wp_update_nav_menu_object( $menu_id, array( 'menu-name' => $navigation->name() ) );
			if ( is_wp_error( $updated ) ) {
				throw new RuntimeException( $updated->get_error_message() );
			}
		}

		$existing = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
		$by_uuid  = array();
		foreach ( is_array( $existing ) ? $existing : array() as $item ) {
			$uuid = (string) get_post_meta( $item->ID, '_navstudio_uuid', true );
			if ( $uuid ) {
				$by_uuid[ $uuid ] = (int) $item->ID; }
		}

		$native_ids = array();
		$kept       = array();
		foreach ( $navigation->nodes() as $index => $node ) {
			$data       = $node->jsonSerialize();
			$native_id  = $by_uuid[ $node->id() ] ?? 0;
			$parent_id  = $node->parent_id() ? ( $native_ids[ $node->parent_id() ] ?? 0 ) : 0;
			$attributes = (array) $data['attributes'];
			$args       = array(
				'menu-item-title'       => $data['label'],
				'menu-item-url'         => $data['url'],
				'menu-item-description' => $data['description'],
				'menu-item-parent-id'   => $parent_id,
				'menu-item-position'    => $index + 1,
				'menu-item-status'      => 'publish',
				'menu-item-target'      => $attributes['target'] ?? '',
				'menu-item-attr-title'  => $attributes['title'] ?? '',
				'menu-item-xfn'         => $attributes['rel'] ?? '',
				'menu-item-classes'     => $attributes['className'] ?? '',
				'menu-item-type'        => in_array( $data['type'], array( 'post_type', 'taxonomy', 'post_type_archive', 'custom' ), true ) ? $data['type'] : 'custom',
				'menu-item-object'      => $data['objectType'],
				'menu-item-object-id'   => $data['objectId'],
			);
			$saved      = wp_update_nav_menu_item( $menu_id, $native_id, $args );
			if ( is_wp_error( $saved ) ) {
				throw new RuntimeException( $saved->get_error_message() ); }
			$native_ids[ $node->id() ] = (int) $saved;
			$kept[]                    = (int) $saved;
			update_post_meta( (int) $saved, '_navstudio_uuid', $node->id() );
			update_post_meta( (int) $saved, '_navstudio_meta', array_intersect_key( $data, array_flip( array( 'appearance', 'responsive', 'conditions', 'dynamic', 'megaMenu', 'source' ) ) ) );
		}

		foreach ( is_array( $existing ) ? $existing : array() as $item ) {
			if ( ! in_array( (int) $item->ID, $kept, true ) ) {
				wp_delete_post( (int) $item->ID, true );
			}
		}
		return $this->read( $menu_id );
	}

	public function list(): array {
		$result = array();
		foreach ( wp_get_nav_menus( array( 'hide_empty' => false ) ) as $menu ) {
			$items    = wp_get_nav_menu_items( $menu->term_id );
			$result[] = array(
				'key'        => 'classic:' . $menu->term_id,
				'id'         => (int) $menu->term_id,
				'name'       => $menu->name,
				'sourceType' => 'classic',
				'itemCount'  => is_array( $items ) ? count( $items ) : 0,
				'modified'   => null,
			);
		}
		return $result;
	}
}
