<?php

namespace CognitiveProcessDesigner;

use JsonSerializable;
use MediaWiki\Title\Title;

class CpdElement implements JsonSerializable {

	/**
	 * @param string $id
	 * @param string $type
	 * @param string $label
	 * @param Title|null $descriptionPage
	 * @param Title|null $oldDescriptionPage
	 * @param array $incomingLinks
	 * @param array $outgoingLinks
	 * @param string|null $invalidDescriptionPageWarning
	 */
	public function __construct(
		private readonly string $id,
		private readonly string $type,
		private readonly string $label,
		private readonly ?Title $descriptionPage = null,
		private readonly ?Title $oldDescriptionPage = null,
		private readonly array $incomingLinks = [],
		private readonly array $outgoingLinks = [],
		private readonly ?string $invalidDescriptionPageWarning = null,
	) {
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * @return Title|null
	 */
	public function getDescriptionPage(): ?Title {
		return $this->descriptionPage;
	}

	/**
	 * @return string|null
	 */
	public function getInvalidDescriptionPageWarning(): ?string {
		return $this->invalidDescriptionPageWarning;
	}

	/**
	 * @return Title|null
	 */
	public function getOldDescriptionPage(): ?Title {
		return $this->oldDescriptionPage;
	}

	/**
	 * @return CpdElement[]
	 */
	public function getIncomingLinks(): array {
		return $this->incomingLinks;
	}

	/**
	 * @return CpdElement[]
	 */
	public function getOutgoingLinks(): array {
		return $this->outgoingLinks;
	}

	public function jsonSerialize(): array {
		$element = [
			'id' => $this->id,
			'type' => $this->type,
			'label' => $this->label,
		];

		if ( $this->descriptionPage && $this->descriptionPage->exists() ) {
			$element['descriptionPage'] = $this->descriptionPage->getPrefixedDBkey();
		}

		return $element;
	}
}
