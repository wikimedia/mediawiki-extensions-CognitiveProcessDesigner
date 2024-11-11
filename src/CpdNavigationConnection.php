<?php

namespace CognitiveProcessDesigner;

class CpdNavigationConnection {

	/** @var string */
	private string $title;

	/** @var string */
	private string $link;

	/** @var bool */
	private bool $isLaneChange;

	/** @var bool */
	private bool $isEnd;

	/**
	 * @param string $title
	 * @param string $link
	 * @param bool $isLaneChange
	 * @param bool $isEnd
	 */
	public function __construct( string $title, string $link, bool $isLaneChange, bool $isEnd ) {
		$this->title = $title;
		$this->link = $link;
		$this->isLaneChange = $isLaneChange;
		$this->isEnd = $isEnd;
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		return [
			'title' => $this->title,
			'link' => $this->link,
			'isLaneChange' => $this->isLaneChange,
			'isEnd' => $this->isEnd,
		];
	}
}
