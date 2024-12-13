<?php

namespace CognitiveProcessDesigner;

use JsonSerializable;
use Title;

class CpdElement implements JsonSerializable {

	/**
	 * @var string
	 */
	private string $id;

	/**
	 * @var string
	 */
	private string $type;

	/**
	 * @var string|null
	 */
	private ?string $label;

	/**
	 * @var CpdElement|null
	 */
	private ?CpdElement $parent;

	/**
	 * @var Title|null
	 */
	private ?Title $descriptionPage;

	/**
	 * @var Title|null
	 */
	private ?Title $oldDescriptionPage;

	/**
	 * @var CpdElement[]
	 */
	private array $incomingLinks;

	/**
	 * @var CpdElement[]
	 */
	private array $outgoingLinks;

	/**
	 * @param string $id
	 * @param string $type
	 * @param string|null $label
	 * @param Title|null $descriptionPage
	 * @param Title|null $oldDescriptionPage
	 * @param array $incomingLinks
	 * @param array $outgoingLinks
	 * @param CpdElement|null $parent
	 */
	private function __construct(
		string $id,
		string $type,
		?string $label = null,
		?Title $descriptionPage = null,
		?Title $oldDescriptionPage = null,
		array $incomingLinks = [],
		array $outgoingLinks = [],
		?CpdElement $parent = null
	) {
		$this->id = $id;
		$this->type = $type;
		$this->label = $label;
		$this->parent = $parent;
		$this->descriptionPage = $descriptionPage;
		$this->oldDescriptionPage = $oldDescriptionPage;
		$this->incomingLinks = $incomingLinks;
		$this->outgoingLinks = $outgoingLinks;
	}

	/**
	 * @param array $element
	 *
	 * @return CpdElement
	 */
	public static function fromElementJson( array $element ): CpdElement {
		$parent = $element['parent'] ? self::fromElementJson( $element['parent'] ) : null;
        $incomingLinks = array_map( fn ( $link ) => self::fromElementJson( $link ), $element['incomingLinks'] );
        $outgoingLinks = array_map( fn ( $link ) => self::fromElementJson( $link ), $element['outgoingLinks'] );

		return new CpdElement(
			$element['id'],
			$element['type'],
			$element['label'],
			$element['descriptionPage'] ? Title::newFromDBkey( $element['descriptionPage'] ) : null,
			$element['oldDescriptionPage'] ? Title::newFromDBkey( $element['oldDescriptionPage'] ) : null,
            $incomingLinks,
            $outgoingLinks,
			$parent
		);
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
	 * @return string|null
	 */
	public function getLabel(): ?string {
		return $this->label;
	}

	/**
	 * @return CpdElement|null
	 */
	public function getParent(): ?CpdElement {
		return $this->parent;
	}

	/**
	 * @return Title|null
	 */
	public function getDescriptionPage(): ?Title {
		return $this->descriptionPage;
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

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'elementId' => $this->getId(),
			'page' => $this->getDescriptionPage()->getPrefixedDBkey(),
		];
	}
}
