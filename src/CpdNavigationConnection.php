<?php

namespace CognitiveProcessDesigner;

class CpdNavigationConnection {

	/** @var string */
	private string $type;

	/**
	 * @param string $text
	 * @param string $link
	 * @param string $type
	 * @param bool $isLaneChange
	 */
	public function __construct(
		private readonly string $text,
		private readonly string $link,
		string $type,
		private readonly bool $isLaneChange
	) {
		$this->type = $this->mapTypeToCls( $type );
	}

	/**
	 * Class names are derived by type by convention:
	 * - without bpmn:
	 * - all lowercase
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private function mapTypeToCls( string $type ): string {
		return str_replace( 'bpmn:', '', strtolower( $type ) );
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		return [
			'text' => $this->text,
			'link' => $this->link,
			'type' => $this->type,
			'isLaneChange' => $this->isLaneChange
		];
	}
}
