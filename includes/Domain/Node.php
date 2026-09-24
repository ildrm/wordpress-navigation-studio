<?php
/**
 * Navigation node value object.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Domain;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

final class Node implements \JsonSerializable {
	/** @var array<string,mixed> */
	private $data;

	/** @param array<string,mixed> $data Raw node data. */
	public function __construct( array $data ) {
		$uuid = isset( $data['id'] ) ? sanitize_text_field( (string) $data['id'] ) : '';
		if ( ! preg_match( '/^[a-zA-Z0-9][a-zA-Z0-9._:-]{5,190}$/', $uuid ) ) {
			throw new InvalidArgumentException( 'A stable node ID is required.' );
		}

		$url = isset( $data['url'] ) ? (string) $data['url'] : '';
		if ( '' !== $url && ! self::is_safe_url( $url ) ) {
			throw new InvalidArgumentException( 'The node URL is not allowed.' );
		}

		$this->data = array(
			'id'          => $uuid,
			'parentId'    => empty( $data['parentId'] ) ? null : sanitize_text_field( (string) $data['parentId'] ),
			'label'       => sanitize_text_field( (string) ( $data['label'] ?? '' ) ),
			'type'        => sanitize_key( (string) ( $data['type'] ?? 'custom' ) ),
			'objectType'  => sanitize_key( (string) ( $data['objectType'] ?? 'custom' ) ),
			'objectId'    => absint( $data['objectId'] ?? 0 ),
			'url'         => $url,
			'description' => sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ),
			'attributes'  => self::sanitize_attributes( (array) ( $data['attributes'] ?? array() ) ),
			'appearance'  => self::sanitize_map( (array) ( $data['appearance'] ?? array() ) ),
			'responsive'  => self::sanitize_map( (array) ( $data['responsive'] ?? array() ) ),
			'conditions'  => self::sanitize_conditions( (array) ( $data['conditions'] ?? array() ) ),
			'dynamic'     => self::sanitize_map( (array) ( $data['dynamic'] ?? array() ) ),
			'megaMenu'    => self::sanitize_map( (array) ( $data['megaMenu'] ?? array() ) ),
			'source'      => self::sanitize_map( (array) ( $data['source'] ?? array() ) ),
		);
	}

	/** @return array<string,mixed> */
	public function jsonSerialize(): array {
		return $this->data;
	}

	public function id(): string {
		return (string) $this->data['id'];
	}

	public function parent_id(): ?string {
		return null === $this->data['parentId'] ? null : (string) $this->data['parentId'];
	}

	private static function is_safe_url( string $url ): bool {
		if ( preg_match( '/^(#|\/)/', $url ) ) {
			return true;
		}
		$protocols = array( 'http', 'https', 'mailto', 'tel' );
		return '' !== esc_url_raw( $url, $protocols );
	}

	/**
	 * @param array<string,mixed> $values Values to sanitize.
	 * @return array<string,mixed>
	 */
	private static function sanitize_map( array $values ): array {
		$clean = array();
		foreach ( $values as $key => $value ) {
			$key = (string) preg_replace( '/[^A-Za-z0-9_.:-]/', '', (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
				$clean[ $key ] = $value;
			} elseif ( is_array( $value ) ) {
				$clean[ $key ] = self::sanitize_map( $value );
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			}
		}
		return $clean;
	}

	/**
	 * @param array<string,mixed> $attributes Link attributes.
	 * @return array<string,mixed>
	 */
	private static function sanitize_attributes( array $attributes ): array {
		$allowed = array( 'target', 'rel', 'title', 'className', 'ariaLabel' );
		return array_intersect_key( self::sanitize_map( $attributes ), array_flip( $allowed ) );
	}

	/**
	 * @param array<int,mixed> $conditions Visibility conditions.
	 * @return array<int,array<string,mixed>>
	 */
	private static function sanitize_conditions( array $conditions ): array {
		$clean = array();
		foreach ( array_slice( $conditions, 0, 25 ) as $condition ) {
			if ( is_array( $condition ) ) {
				$clean[] = self::sanitize_map( $condition );
			}
		}
		return $clean;
	}
}
