<?php

namespace CognitiveProcessDesigner;

class CpdNavigationConnection {

	/** @var string */
	private string $text;

	/** @var string */
	private string $link;

	/** @var bool */
	private bool $isLaneChange;

	/**
	 * @param string $text
	 * @param string $link
	 * @param bool $isLaneChange
	 */
	public function __construct( string $text, string $link, bool $isLaneChange ) {
		$this->text = $text;
		$this->link = $link;
		$this->isLaneChange = $isLaneChange;
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		return [
			'text' => $this->text,
			'link' => $this->link,
			'isLaneChange' => $this->isLaneChange
		];
	}
}
