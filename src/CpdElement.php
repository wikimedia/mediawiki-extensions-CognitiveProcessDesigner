<?php

namespace CognitiveProcessDesigner;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
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
	 */
	private function __construct(
		private readonly string $id,
		private readonly string $type,
		private readonly ?string $label = null,
		private readonly ?Title $descriptionPage = null,
		private readonly ?Title $oldDescriptionPage = null,
		private readonly array $incomingLinks = [],
		private readonly array $outgoingLinks = []
	) {
	}

	/**
	 * @param array $element
	 *
	 * @return CpdElement
	 * @throws CpdCreateElementException
	 */
	public static function fromElementJson( array $element ): CpdElement {
		self::validateJson(
			$element['id'],
			$element['type'],
			$element['label']
		);

		$incomingLinks = !empty( $element['incomingLinks'] ) ? array_map( fn ( $link ) => self::fromElementJson( $link ),
			$element['incomingLinks'] ) : [];
		$outgoingLinks = !empty( $element['outgoingLinks'] ) ? array_map( fn ( $link ) => self::fromElementJson( $link ),
			$element['outgoingLinks'] ) : [];

		return new CpdElement(
			$element['id'],
			$element['type'],
			$element['label'] ?? null,
			!empty( $element['descriptionPage'] ) ? Title::newFromDBkey( $element['descriptionPage'] ) : null,
			!empty( $element['oldDescriptionPage'] ) ? Title::newFromDBkey( $element['oldDescriptionPage'] ) : null,
			$incomingLinks,
			$outgoingLinks
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
	 * @param string $id
	 * @param string $type
	 * @param string|null $label
	 *
	 * @return void
	 * @throws CpdCreateElementException
	 */
	private static function validateJson( string $id, string $type, ?string $label ): void {
		if ( empty( $label ) ) {
			throw new CpdCreateElementException( Message::newFromKey( 'cpd-validation-missing-label', $type, $id ) );
		}
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
