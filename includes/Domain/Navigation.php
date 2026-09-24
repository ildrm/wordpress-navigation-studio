<?php
/**
 * Unified navigation aggregate.
 *
 * @package NavigationStudio
 */

namespace NavigationStudio\Domain;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

final class Navigation implements \JsonSerializable {
	/** @var string */ private $key;
	/** @var string */ private $name;
	/** @var string */ private $source_type;
	/** @var int */ private $source_id;
	/** @var Node[] */ private $nodes;
	/** @var array<string,mixed> */ private $settings;

	/**
	 * @param array<int,Node>       $nodes Navigation nodes.
	 * @param array<string,mixed>   $settings Navigation settings.
	 */
	public function __construct( string $key, string $name, string $source_type, int $source_id, array $nodes, array $settings = array() ) {
		if ( ! in_array( $source_type, array( 'classic', 'block' ), true ) ) {
			throw new InvalidArgumentException( 'Unknown navigation source type.' );
		}
		$this->key         = sanitize_text_field( $key );
		$this->name        = sanitize_text_field( $name );
		$this->source_type = $source_type;
		$this->source_id   = absint( $source_id );
		$this->nodes       = array_values( $nodes );
		$this->settings    = $settings;
		Validator::assert_valid( $this->nodes );
	}

	/** @param array<string,mixed> $data */
	public static function from_array( array $data ): self {
		$nodes = array_map(
			static function ( $node ) {
				return new Node( (array) $node );
			},
			(array) ( $data['nodes'] ?? array() )
		);
		return new self(
			(string) ( $data['key'] ?? '' ),
			(string) ( $data['name'] ?? '' ),
			(string) ( $data['sourceType'] ?? '' ),
			absint( $data['sourceId'] ?? 0 ),
			$nodes,
			(array) ( $data['settings'] ?? array() )
		);
	}

	/** @return array<string,mixed> */
	public function jsonSerialize(): array {
		return array(
			'key'        => $this->key,
			'name'       => $this->name,
			'sourceType' => $this->source_type,
			'sourceId'   => $this->source_id,
			'nodes'      => $this->nodes,
			'settings'   => $this->settings,
			'checksum'   => $this->checksum(),
		);
	}

	public function key(): string {
		return $this->key; }
	public function name(): string {
		return $this->name; }
	public function source_type(): string {
		return $this->source_type; }
	public function source_id(): int {
		return $this->source_id; }
	/** @return Node[] */ public function nodes(): array {
		return $this->nodes; }
	public function checksum(): string {
		return hash( 'sha256', wp_json_encode( array( $this->name, $this->source_type, $this->source_id, $this->nodes, $this->settings ) ) ); }
}
