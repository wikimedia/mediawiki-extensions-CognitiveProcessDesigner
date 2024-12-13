<?php

namespace CognitiveProcessDesigner;

class CpdNavigationConnection {

	/** @var string */
	private string $text;

	/** @var string */
	private string $link;

	/** @var bool */
	private bool $isLaneChange;

	/** @var string */
	private string $type;

	/**
	 * @param string $text
	 * @param string $link
	 * @param string $type
	 * @param bool $isLaneChange
	 */
	public function __construct( string $text, string $link, string $type, bool $isLaneChange ) {
		$this->text = $text;
		$this->link = $link;
		$this->isLaneChange = $isLaneChange;
		$this->type = $type;
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
