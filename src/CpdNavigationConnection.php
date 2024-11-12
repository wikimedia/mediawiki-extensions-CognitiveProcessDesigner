<?php

namespace CognitiveProcessDesigner;

class CpdNavigationConnection {

	/** @var string */
	private string $title;

	/** @var string */
	private string $link;

	/** @var bool */
	private bool $isLaneChange;

	/**
	 * @param string $title
	 * @param string $link
	 * @param bool $isLaneChange
	 */
	public function __construct( string $title, string $link, bool $isLaneChange ) {
		$this->title = $title;
		$this->link = $link;
		$this->isLaneChange = $isLaneChange;
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		return [
			'title' => $this->title,
			'link' => $this->link,
			'isLaneChange' => $this->isLaneChange
		];
	}
}
