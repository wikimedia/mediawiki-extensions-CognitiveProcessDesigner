<?php

namespace CognitiveProcessDesigner;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use MediaWiki\Message\Message;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Title\TitleParser;

class CpdElementFactory {

	/**
	 * @param TitleFactory $titleFactory
	 * @param TitleParser $titleParser
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory,
		private readonly TitleParser $titleParser
	) {
	}

	/**
	 * @param array $elements
	 *
	 * @return CpdElement[]
	 * @throws CpdCreateElementException
	 */
	public function makeElements( array $elements ): array {
		return array_map( function ( $element ) {
			return $this->makeElement( $element );
		}, $elements );
	}

	/**
	 * @param array $element
	 * @param bool $skipLinks
	 *
	 * @return CpdElement
	 * @throws CpdCreateElementException
	 */
	public function makeElement( array $element, $skipLinks = false ): CpdElement {
		self::validateJson( $element );

		$descriptionPage = null;
		$invalidDescriptionPageWarning = null;
		$oldDescriptionPage = null;

		if ( !empty( $element['descriptionPage'] ) ) {
			$descriptionPage = $this->titleFactory->newFromDBkey( $element['descriptionPage'] );

			if ( !$descriptionPage ) {
				try {
					$this->titleParser->splitTitleString( $element['descriptionPage'] );
				} catch ( MalformedTitleException $ex ) {
					$invalidDescriptionPageWarning = Message::newFromKey(
						$ex->getErrorMessage(),
						...$ex->getErrorMessageParameters()
					)->escaped();
				}
			}
		}

		if ( !empty( $element['oldDescriptionPage'] ) ) {
			$oldDescriptionPage = $this->titleFactory->newFromDBkey( $element['oldDescriptionPage'] );
		}

		if ( !$skipLinks ) {
			$incomingLinks = !empty( $element['incomingLinks'] ) ? array_map(
				fn ( $link ) => $this->makeElement( $link, true ),
				$element['incomingLinks']
			) : [];
			$outgoingLinks = !empty( $element['outgoingLinks'] ) ? array_map(
				fn ( $link ) => $this->makeElement( $link, true ),
				$element['outgoingLinks']
			) : [];
		} else {
			$incomingLinks = [];
			$outgoingLinks = [];
		}

		return new CpdElement(
			$element['id'],
			$element['type'],
			$element['label'],
			$descriptionPage,
			$oldDescriptionPage,
			$incomingLinks,
			$outgoingLinks,
			$invalidDescriptionPageWarning
		);
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 * @throws CpdCreateElementException
	 */
	private static function validateJson( array $data ): void {
		if ( empty( $data['id'] ) || empty( $data['type'] ) ) {
			throw new CpdCreateElementException(
				Message::newFromKey( 'cpd-validation-missing-data' )
			);
		}

		if ( empty( $data['label'] ) ) {
			throw new CpdCreateElementException(
				Message::newFromKey( 'cpd-validation-missing-label', $data['type'], $data['id'] )
			);
		}
	}
}
