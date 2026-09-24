<?php
/**
 * Paginated cross-content discovery.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Application;

defined( 'ABSPATH' ) || exit;

final class ContentSearch {
	/** @return array<string,mixed> */
	public function search( string $query, string $kind, int $page, int $per_page ): array {
		$page       = max( 1, $page );
		$per_page   = min( 50, max( 1, $per_page ) );
		$offset     = ( $page - 1 ) * $per_page;
		$results    = array();
		$post_total = 0;
		$term_total = 0;

		if ( 'all' === $kind || 'post' === $kind ) {
			$post_types = get_post_types( array( 'show_in_nav_menus' => true ), 'names' );
			$post_query = new \WP_Query(
				array(
					's'              => $query,
					'post_type'      => $post_types,
					'post_status'    => array( 'publish', 'private', 'draft' ),
					'posts_per_page' => $per_page,
					'offset'         => $offset,
					'orderby'        => 'relevance title',
					'no_found_rows'  => false,
				)
			);
			foreach ( $post_query->posts as $post ) {
				$post_type = get_post_type_object( $post->post_type );
				$context   = $post_type ? $post_type->labels->singular_name : $post->post_type;
				$results[] = array(
					'id'         => 'post:' . $post->ID,
					'objectId'   => (int) $post->ID,
					'title'      => get_the_title( $post ),
					'type'       => 'post_type',
					'objectType' => $post->post_type,
					'status'     => $post->post_status,
					'url'        => get_permalink( $post ),
					'context'    => $context,
				);
			}
			$post_total = (int) $post_query->found_posts;
		}

		if ( 'all' === $kind || 'taxonomy' === $kind ) {
			$taxonomies  = get_taxonomies( array( 'show_in_nav_menus' => true ), 'names' );
			$count       = wp_count_terms(
				array(
					'taxonomy'   => $taxonomies,
					'search'     => $query,
					'hide_empty' => false,
				)
			);
			$term_total  = is_wp_error( $count ) ? 0 : (int) $count;
			$remaining   = $per_page - count( $results );
			$term_offset = 'all' === $kind ? max( 0, $offset - $post_total ) : $offset;
			if ( $remaining > 0 ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomies,
						'search'     => $query,
						'hide_empty' => false,
						'number'     => $remaining,
						'offset'     => $term_offset,
					)
				);
			} else {
				$terms = array();
			}
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$url       = get_term_link( $term );
					$results[] = array(
						'id'         => 'term:' . $term->term_id,
						'objectId'   => (int) $term->term_id,
						'title'      => $term->name,
						'type'       => 'taxonomy',
						'objectType' => $term->taxonomy,
						'status'     => 'publish',
						'url'        => is_wp_error( $url ) ? '' : $url,
						'context'    => $term->taxonomy,
					); }
			}
		}
		$total = $post_total + $term_total;

		return array(
			'items' => $results,
			'total' => $total,
			'page'  => $page,
			'pages' => max( 1, (int) ceil( $total / $per_page ) ),
		);
	}
}
