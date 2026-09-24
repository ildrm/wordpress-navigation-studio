<?php
/**
 * wp_navigation adapter using public block parsing and serialization APIs.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Adapters;

use NavigationStudio\Domain\Navigation;
use NavigationStudio\Domain\Node;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class BlockNavigationAdapter implements AdapterInterface {
	public function supports( string $source_type ): bool {
		return 'block' === $source_type; }

	public function read( int $source_id ): Navigation {
		$post = get_post( $source_id );
		if ( ! $post || 'wp_navigation' !== $post->post_type ) {
			throw new RuntimeException( 'Block navigation not found.' ); }
		$stored_sidecar = get_post_meta( $source_id, '_navstudio_node_map', true );
		$stored_sidecar = is_array( $stored_sidecar ) ? $stored_sidecar : array();
		$metadata       = get_post_meta( $source_id, '_navstudio_node_meta', true );
		$metadata       = is_array( $metadata ) ? $metadata : array();
		$nodes          = array();
		$sidecar        = array();
		$this->walk_blocks( parse_blocks( $post->post_content ), null, $nodes, $stored_sidecar, $sidecar, $metadata );
		if ( $sidecar !== $stored_sidecar ) {
			update_post_meta( $source_id, '_navstudio_node_map', $sidecar );
		}
		return new Navigation( 'block:' . $post->ID, get_the_title( $post ), 'block', (int) $post->ID, $nodes, array() );
	}

	public function publish( Navigation $navigation ): Navigation {
		$tree     = $this->build_tree( $navigation->nodes() );
		$sidecar  = array();
		$metadata = array();
		$blocks   = $this->serialize_nodes( $tree, $sidecar, $metadata );
		$postarr  = array(
			'ID'           => $navigation->source_id(),
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => $navigation->name(),
			'post_content' => serialize_blocks( $blocks ),
		);
		$id       = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( $id->get_error_message() ); }
		update_post_meta( (int) $id, '_navstudio_node_map', $sidecar );
		update_post_meta( (int) $id, '_navstudio_node_meta', $metadata );
		return $this->read( (int) $id );
	}

	public function list(): array {
		$posts  = get_posts(
			array(
				'post_type'   => 'wp_navigation',
				'post_status' => array( 'publish', 'draft' ),
				'numberposts' => -1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
		$result = array();
		foreach ( $posts as $post ) {
			$result[] = array(
				'key'        => 'block:' . $post->ID,
				'id'         => (int) $post->ID,
				'name'       => get_the_title( $post ),
				'sourceType' => 'block',
				'itemCount'  => $this->count_blocks( parse_blocks( $post->post_content ) ),
				'modified'   => $post->post_modified_gmt,
			);
		}
		return $result;
	}

	/** @param array<int,array<string,mixed>> $blocks Parsed blocks. */
	private function count_blocks( array $blocks ): int {
		$count = 0;
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				++$count;
			}
			$count += $this->count_blocks( (array) ( $block['innerBlocks'] ?? array() ) );
		}
		return $count;
	}

	/**
	 * Walk parsed navigation blocks without mutating unknown block markup.
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
	 * @param string|null                    $parent_id Parent node ID.
	 * @param array<int,Node>                $nodes Collected nodes.
	 * @param array<string,string>           $stored_sidecar Existing stable ID sidecar.
	 * @param array<string,string>           $sidecar Current stable ID sidecar.
	 * @param array<string,array<string,mixed>> $metadata Plugin metadata keyed by stable ID.
	 * @param string                         $path Current block path.
	 */
	private function walk_blocks( array $blocks, ?string $parent_id, array &$nodes, array $stored_sidecar, array &$sidecar, array $metadata, string $path = '' ): void {
		foreach ( $blocks as $index => $block ) {
			$current = '' === $path ? (string) $index : $path . '.' . $index;
			$name    = (string) ( $block['blockName'] ?? '' );
			$attrs   = (array) ( $block['attrs'] ?? array() );
			if ( in_array( $name, array( 'core/navigation-link', 'core/navigation-submenu', 'core/home-link' ), true ) ) {
				$uuid                = $stored_sidecar[ $current ] ?? wp_generate_uuid4();
				$nodes[]             = new Node(
					array_merge(
						(array) ( $metadata[ $uuid ] ?? array() ),
						array(
							'id'         => $uuid,
							'parentId'   => $parent_id,
							'label'      => $attrs['label'] ?? '',
							'type'       => 'block',
							'objectType' => $attrs['kind'] ?? $name,
							'objectId'   => $attrs['id'] ?? 0,
							'url'        => $attrs['url'] ?? '',
							'attributes' => array(
								'target'    => ! empty( $attrs['opensInNewTab'] ) ? '_blank' : '',
								'rel'       => $attrs['rel'] ?? '',
								'title'     => $attrs['title'] ?? '',
								'className' => $attrs['className'] ?? '',
							),
							'source'     => array(
								'blockName' => $name,
								'blockType' => $attrs['type'] ?? '',
								'path'      => $current,
							),
						)
					)
				);
				$sidecar[ $current ] = $uuid;
				$this->walk_blocks( (array) ( $block['innerBlocks'] ?? array() ), $uuid, $nodes, $stored_sidecar, $sidecar, $metadata, $current );
			} elseif ( '' !== $name ) {
				$uuid                = $stored_sidecar[ $current ] ?? wp_generate_uuid4();
				$nodes[]             = new Node(
					array_merge(
						(array) ( $metadata[ $uuid ] ?? array() ),
						array(
							'id'         => $uuid,
							'parentId'   => $parent_id,
							/* translators: %s is a registered WordPress block name. */
							'label'      => sprintf( __( 'Unsupported block: %s', 'navigation-studio' ), $name ),
							'type'       => 'block-unsupported',
							'objectType' => $name,
							'source'     => array(
								'blockName'       => $name,
								'path'            => $current,
								// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Reversible transport prevents text sanitization from corrupting preserved block JSON.
								'serializedBlock' => base64_encode( wp_json_encode( $block ) ),
							),
						)
					)
				);
				$sidecar[ $current ] = $uuid;
			} else {
				$this->walk_blocks( (array) ( $block['innerBlocks'] ?? array() ), $parent_id, $nodes, $stored_sidecar, $sidecar, $metadata, $current );
			}
		}
	}

	/**
	 * Build nested entries from flat preorder nodes.
	 *
	 * @param array<int,Node> $nodes Nodes to nest.
	 * @param string|null     $parent_id Parent node ID.
	 * @return array<int,array{node:Node,children:array<int,mixed>}>
	 */
	private function build_tree( array $nodes, ?string $parent_id = null ): array {
		$result = array();
		foreach ( $nodes as $node ) {
			if ( $node->parent_id() === $parent_id ) {
				$result[] = array(
					'node'     => $node,
					'children' => $this->build_tree( $nodes, $node->id() ),
				); }
		}
		return $result;
	}

	/**
	 * Convert nested entries to WordPress block arrays.
	 *
	 * @param array<int,array{node:Node,children:array<int,mixed>}> $tree Nested entries.
	 * @param array<string,string>                                 $sidecar Stable ID sidecar.
	 * @param array<string,array<string,mixed>>                    $metadata Plugin metadata keyed by stable ID.
	 * @param string                                               $path Current block path.
	 * @return array<int,array<string,mixed>>
	 */
	private function serialize_nodes( array $tree, array &$sidecar, array &$metadata, string $path = '' ): array {
		$blocks = array();
		foreach ( $tree as $index => $entry ) {
			$data                             = $entry['node']->jsonSerialize();
			$current                          = '' === $path ? (string) $index : $path . '.' . $index;
			$metadata[ $entry['node']->id() ] = array_intersect_key(
				$data,
				array_flip( array( 'description', 'appearance', 'responsive', 'conditions', 'dynamic', 'megaMenu' ) )
			);
			if ( 'block-unsupported' === $data['type'] ) {
				$encoded = (string) ( $data['source']['serializedBlock'] ?? '' );
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decodes bounded block JSON that is validated and KSES-filtered below.
				$raw = json_decode( (string) base64_decode( $encoded, true ), true );
				if ( is_array( $raw ) && ! empty( $raw['blockName'] ) && \WP_Block_Type_Registry::get_instance()->is_registered( $raw['blockName'] ) ) {
					$filtered            = parse_blocks( filter_block_content( serialize_block( $raw ) ) );
					$blocks[]            = $filtered[0] ?? $raw;
					$sidecar[ $current ] = $entry['node']->id();
				}
				continue;
			}
			$attrs = array(
				'label' => $data['label'],
				'url'   => $data['url'],
				'kind'  => $data['objectType'],
			);
			if ( ! empty( $data['source']['blockType'] ) ) {
				$attrs['type'] = $data['source']['blockType'];
			}
			if ( $data['objectId'] ) {
				$attrs['id'] = $data['objectId']; }
			if ( ! empty( $data['attributes']['target'] ) ) {
				$attrs['opensInNewTab'] = true; }
			foreach ( array( 'rel', 'title', 'className' ) as $attribute ) {
				if ( ! empty( $data['attributes'][ $attribute ] ) ) {
					$attrs[ $attribute ] = $data['attributes'][ $attribute ];
				}
			}
			$children            = $this->serialize_nodes( $entry['children'], $sidecar, $metadata, $current );
			$name                = $children ? 'core/navigation-submenu' : 'core/navigation-link';
			$blocks[]            = array(
				'blockName'    => $name,
				'attrs'        => $attrs,
				'innerBlocks'  => $children,
				'innerHTML'    => '',
				'innerContent' => array(),
			);
			$sidecar[ $current ] = $entry['node']->id();
		}
		return $blocks;
	}
}
