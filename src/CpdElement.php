<?php

namespace CognitiveProcessDesigner;

use InvalidArgumentException;
use JsonSerializable;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;

class CpdElement implements JsonSerializable {

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
		private readonly string $id,
		private readonly string $type,
		private readonly ?string $label = null,
		private readonly ?Title $descriptionPage = null,
		private readonly ?Title $oldDescriptionPage = null,
		private readonly array $incomingLinks = [],
		private readonly array $outgoingLinks = [],
		private readonly ?CpdElement $parent = null
	) {
	}

	/**
	 * @param array $element
	 * @param bool $isParent
	 *
	 * @return CpdElement
	 */
	public static function fromElementJson( array $element, bool $isParent = false ): CpdElement {
		// Validate the JSON data only if it is not a parent element
		if ( !$isParent ) {
			self::validateJson( $element );
		}

		$parent = $element['parent'] ? self::fromElementJson( $element['parent'], true ) : null;
		$incomingLinks = array_map( fn( $link ) => self::fromElementJson( $link ), $element['incomingLinks'] );
		$outgoingLinks = array_map( fn( $link ) => self::fromElementJson( $link ), $element['outgoingLinks'] );

		return new CpdElement(
			$element['id'],
			$element['type'],
			$element['label'],
			!empty( $element['descriptionPage'] ) ? Title::newFromDBkey( $element['descriptionPage'] ) : null,
			!empty( $element['oldDescriptionPage'] ) ? Title::newFromDBkey( $element['oldDescriptionPage'] ) : null,
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

	/**
	 * @param array $element
	 *
	 * @return void
	 */
	private static function validateJson( array $element ): void {
		$id = $element['id'];
		$type = $element['type'];
		if ( empty( $element['label'] ) ) {
			throw new InvalidArgumentException( Message::newFromKey( 'cpd-validation-missing-label', $type, $id ) );
		}
	}
}
