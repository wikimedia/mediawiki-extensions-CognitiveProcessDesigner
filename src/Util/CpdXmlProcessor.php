<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\CpdElementFactory;
use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use Exception;
use MediaWiki\Config\Config;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use SimpleXMLElement;

class CpdXmlProcessor {

	/** @var array */
	private array $dedicatedSubpageTypes = [];

	/** @var array */
	private array $laneTypes = [];

	public function __construct(
		Config $config,
		private readonly CpdElementFactory $cpdElementFactory
	) {
		$this->dedicatedSubpageTypes = [];
		if ( $config->has( 'CPDDedicatedSubpageTypes' ) ) {
			$this->dedicatedSubpageTypes = $config->get( 'CPDDedicatedSubpageTypes' );
		}

		$this->laneTypes = [];
		if ( $config->has( 'CPDLaneTypes' ) ) {
			$this->laneTypes = $config->get( 'CPDLaneTypes' );
		}
	}

	/**
	 * @param string $process
	 * @param string $xmlString
	 * @param string|null $oldXmlString
	 *
	 * @return CpdElement[]
	 * @throws CpdXmlProcessingException
	 * @throws CpdCreateElementException
	 */
	public function createElements( string $process, string $xmlString, ?string $oldXmlString = null ): array {
		if ( empty( $xmlString ) ) {
			return [];
		}

		$descriptionPageElements = $this->createAllElementsData( $process, $xmlString );

		if ( $oldXmlString ) {
			$this->setOldDescriptionPages( $process, $oldXmlString, $descriptionPageElements );
		}

		return $this->cpdElementFactory->makeElements( array_values( $descriptionPageElements ) );
	}

	/**
	 * @param string $process
	 * @param string $xmlString
	 *
	 * @return array
	 * @throws CpdXmlProcessingException
	 */
	private function createAllElementsData( string $process, string $xmlString ): array {
		$elementsData = [];

		try {
			$xml = new SimpleXMLElement( $xmlString );
		} catch ( Exception $e ) {
			throw new CpdXmlProcessingException( Message::newFromKey( "cpd-error-xml-parse-error" )->text() );
		}

		$xml->registerXPathNamespace( 'bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL' );
		$xmlElements = $xml->xpath( '//bpmn:*' );
		foreach ( $xmlElements as $xmlElement ) {
			$type = 'bpmn:' . ucfirst( $xmlElement->getName() );
			$elementData = [ 'type' => $type ];

			$parents = $xmlElement->xpath( ".." );
			if ( !empty( $parents ) ) {
				$parent = $parents[0];
				$attributes = $parent->attributes();
				$elementData['parentRef'] = (string)$attributes->id;
			}

			$attributes = $xmlElement->attributes();
			foreach ( $attributes as $key => $value ) {
				$elementData[$key] = (string)$value;
			}

			$elementsData[] = $elementData;
		}

		$descriptionPageElements = $this->filterByType( $elementsData, $this->dedicatedSubpageTypes );
		$parents = $this->filterByType( $elementsData, $this->laneTypes );
		$connections = $this->filterByType( $elementsData, [ 'bpmn:SequenceFlow' ] );

		// First set all description pages
		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			$this->setParent( $descriptionPageElement, $parents );
			$this->setDescriptionPage( $descriptionPageElement, $process );
		}

		// Then set all connections
		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			$this->setConnections( $descriptionPageElement, $descriptionPageElements, $connections );
			$this->cleanUpData( $descriptionPageElement );
		}

		return $descriptionPageElements;
	}

	/**
	 * @param array $elementsData
	 * @param array $type
	 *
	 * @return array
	 */
	private function filterByType( array $elementsData, array $type ): array {
		return array_filter( $elementsData, fn( $elementData ) => in_array( $elementData['type'], $type ) );
	}

	/**
	 * @param array $descriptionPageElement
	 * @param array $parents
	 *
	 * @return void
	 */
	private function setParent( array &$descriptionPageElement, array $parents ): void {
		foreach ( $parents as $parent ) {
			if ( empty( $parent['processRef'] ) ) {
				continue;
			}

			if ( $descriptionPageElement['parentRef'] === $parent['processRef'] ) {
				$descriptionPageElement['parent'] = $parent;
			}
		}
	}

	/**
	 * @param array $descriptionPageElement
	 * @param array $descriptionPageElements
	 * @param array $connections
	 *
	 * @return void
	 * @throws CpdXmlProcessingException
	 */
	private function setConnections(
		array &$descriptionPageElement,
		array $descriptionPageElements,
		array $connections
	): void {
		$this->setConnection(
			$descriptionPageElement,
			$descriptionPageElements,
			$connections,
			'incomingLinks',
			'targetRef',
			'sourceRef'
		);

		$this->setConnection(
			$descriptionPageElement,
			$descriptionPageElements,
			$connections,
			'outgoingLinks',
			'sourceRef',
			'targetRef'
		);
	}

	/**
	 * @param array $element
	 * @param array $descriptionPageElements
	 * @param array $connections
	 * @param string $connectionField
	 * @param string $sourceField
	 * @param string $targetField
	 *
	 * @return void
	 */
	private function setConnection(
		array &$element,
		array $descriptionPageElements,
		array $connections,
		string $connectionField,
		string $sourceField,
		string $targetField
	): void {
		$element[$connectionField] = [];

		foreach ( $connections as $connection ) {
			if ( empty( $connection[$sourceField] ) || empty( $connection[$targetField] ) ) {
				continue;
			}

			if ( $element['id'] !== $connection[$sourceField] ) {
				continue;
			}

			$connectionElements = array_filter(
				$descriptionPageElements,
				fn( $elementData ) => $elementData['id'] === $connection[$targetField]
			);

			if ( empty( $connectionElements ) ) {
				continue;
			}

			$element[$connectionField][] = reset( $connectionElements );
		}
	}


	/**
	 * @param array $descriptionPageElement
	 * @param string $process
	 *
	 * @return void
	 * @throws CpdXmlProcessingException
	 */
	private function setDescriptionPage(
		array &$descriptionPageElement,
		string $process,
	): void {
		$descriptionPageElement['descriptionPage'] = $this->makeDescriptionPageTitle(
			$process,
			$descriptionPageElement
		);
	}

	/**
	 * @param string $process
	 * @param string $oldXmlString
	 * @param array $descriptionPageElements
	 *
	 * @return void
	 * @throws CpdXmlProcessingException
	 */
	private function setOldDescriptionPages(
		string $process,
		string $oldXmlString,
		array &$descriptionPageElements,
	): void {
		$oldDescriptionPageElements = $this->createAllElementsData( $process, $oldXmlString );
		foreach ( $descriptionPageElements as &$element ) {
			$filteredElements = array_filter(
				$oldDescriptionPageElements,
				fn( $elementData ) => $elementData['id'] === $element['id']
			);
			$oldDescriptionPageElement = reset( $filteredElements );
			if ( $oldDescriptionPageElement ) {
				if ( $element['descriptionPage'] === $oldDescriptionPageElement['descriptionPage'] ) {
					continue;
				}

				$element['oldDescriptionPage'] = $oldDescriptionPageElement['descriptionPage'];
			}
		}
	}

	/**
	 * @param string $process
	 * @param array $element
	 *
	 * @return string
	 * @throws CpdXmlProcessingException
	 */
	private function makeDescriptionPageTitle( string $process, array $element ): string {
		if ( empty( $element['name'] ) ) {
			throw new CpdXmlProcessingException( Message::newFromKey( "cpd-error-message-missing-label", $element["id"] )->text() );
		}

		if (
			!empty( $element['parent'] ) &&
			!empty( $element['parent']['name'] ) &&
			in_array( $element['parent']['type'], $this->laneTypes )
		) {
			$titleText = "$process/{$element['parent']['name']}/{$element['name']}";
		} else {
			$titleText = "{$process}/{$element['name']}";
		}

		$title = Title::newFromText( $titleText, NS_PROCESS );

		if ( !$title ) {
			throw new CpdXmlProcessingException( Message::newFromKey( "cpd-error-could-not-create-title", $element["id"] )->text() );
		}

		return $title->getPrefixedDBkey();
	}

	/**
	 * Remove temporary and unused fields from the data
	 *
	 * @param array $element
	 * @param bool $removeParentField
	 *
	 * @return void
	 */
	private function cleanUpData( array &$element, bool $removeParentField = false ): void {
		unset( $element['parentRef'] );
		unset( $element['processRef'] );

		if ( !empty( $element['name'] ) ) {
			$element['label'] = $element['name'];
			unset( $element['name'] );
		}

		if ( !empty( $element['parent'] ) ) {
			if ( $removeParentField ) {
				unset( $element['parent'] );
			} else {
				$this->cleanUpData( $element['parent'], true );
			}
		}

		if ( !empty( $element['incomingLinks'] ) ) {
			foreach ( $element['incomingLinks'] as &$link ) {
				$this->cleanUpData( $link, true );
				unset( $link['incomingLinks'] );
				unset( $link['outgoingLinks'] );
			}
		}

		if ( !empty( $element['outgoingLinks'] ) ) {
			foreach ( $element['outgoingLinks'] as &$link ) {
				$this->cleanUpData( $link, true );
				unset( $link['incomingLinks'] );
				unset( $link['outgoingLinks'] );
			}
		}
	}
}
