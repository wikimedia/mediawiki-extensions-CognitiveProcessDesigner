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

	/**
	 * bpmn:Definitions
	 * bpmn:Process
	 * bpmn:Outgoing
	 * bpmn:Incoming
	 * bpmn:Collaboration
	 * bpmn:LaneSet
	 * bpmn:FlowNodeRef
	 *
	 * @var array|string[]
	 */
	private array $unusedTypes = [
		'definitions',
		'collaboration',
		'process',
		'outgoing',
		'incoming',
		'laneSet',
		'flowNodeRef'
	];

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

		$elementsData = $this->createAllElementsData( $xmlString );
		$descriptionPageElements = $this->updateDescriptionPageElements( $elementsData, $process );

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
	private function createAllElementsData( string $xmlString ): array {
		$elementsData = [];

		try {
			$xml = new SimpleXMLElement( $xmlString );
		} catch ( Exception $e ) {
			throw new CpdXmlProcessingException( Message::newFromKey( "cpd-error-xml-parse-error" )->text() );
		}

		$xml->registerXPathNamespace( 'bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL' );

		$excludeTypes = implode( ' and ', array_map(
			static fn ( $type ) => 'local-name() !="' . str_replace( 'bpmn:', '', $type ) . '"',
			$this->unusedTypes
		) );

		$xmlElements = $xml->xpath( '//bpmn:*[' . $excludeTypes . ']' );
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
				$elementData[ $key ] = (string)$value;
			}

			$elementsData[] = $elementData;
		}

		return $elementsData;
	}

	/**
	 * @param array $elementsData
	 * @param string $process
	 *
	 * @return array
	 * @throws CpdXmlProcessingException
	 */
	private function updateDescriptionPageElements( array $elementsData, string $process ): array {
		$descriptionPageElements = $this->filterByType( $elementsData, $this->dedicatedSubpageTypes );
		$parents = $this->filterByType( $elementsData, $this->laneTypes );

		// First set all description pages
		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			$this->setParent( $descriptionPageElement, $parents );
			$this->setDescriptionPage( $descriptionPageElement, $process );
		}

		// Then set all connections
		$sequenceFlows = CpdSequenceFlowUtil::createSubpageSequenceFlows( $elementsData, $this->dedicatedSubpageTypes );

		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			$this->setConnections(
				$descriptionPageElement,
				$descriptionPageElements,
				$sequenceFlows,
				'incomingLinks',
				'targetRef',
				'sourceRef'
			);

			$this->setConnections(
				$descriptionPageElement,
				$descriptionPageElements,
				$sequenceFlows,
				'outgoingLinks',
				'sourceRef',
				'targetRef'
			);

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
		return array_values(
			array_filter( $elementsData, static fn ( $elementData ) => in_array( $elementData['type'], $type ) )
		);
	}

	/**
	 * @param array &$descriptionPageElement
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
	 * @param array &$element
	 * @param array $descriptionPageElements
	 * @param array $sequenceFlows
	 * @param string $connectionField
	 * @param string $sourceField
	 * @param string $targetField
	 *
	 * @return void
	 */
	private function setConnections(
		array &$element,
		array $descriptionPageElements,
		array $sequenceFlows,
		string $connectionField,
		string $sourceField,
		string $targetField
	): void {
		$element[ $connectionField ] = [];

		foreach ( $sequenceFlows as $sequenceFlow ) {
			if ( empty( $sequenceFlow[ $sourceField ] ) || empty( $sequenceFlow[ $targetField ] ) ) {
				continue;
			}

			if ( $element['id'] !== $sequenceFlow[ $sourceField ] ) {
				continue;
			}

			$connections = array_filter(
				$descriptionPageElements,
				static fn ( $elementData ) => $elementData['id'] === $sequenceFlow[ $targetField ]
			);

			if ( empty( $connections ) ) {
				continue;
			}

			$element[ $connectionField ][] = reset( $connections );
		}
	}

	/**
	 * @param array &$descriptionPageElement
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
	 * @param array &$descriptionPageElements
	 *
	 * @return void
	 */
	private function setOldDescriptionPages(
		string $process,
		string $oldXmlString,
		array &$descriptionPageElements,
	): void {
		try {
			$elementsData = $this->createAllElementsData( $oldXmlString );
			$oldDescriptionPageElements = $this->updateDescriptionPageElements( $elementsData, $process );
		} catch ( Exception $e ) {
			// If the old XML string is invalid, we can't set old description pages
			return;
		}

		foreach ( $descriptionPageElements as &$element ) {
			$filteredElements = array_filter(
				$oldDescriptionPageElements,
				static fn ( $elementData ) => $elementData['id'] === $element['id']
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
			throw new CpdXmlProcessingException(
				Message::newFromKey( "cpd-error-message-missing-label", $element["id"] )->text()
			);
		}

		if ( !empty( $element['parent'] ) &&
			 !empty( $element['parent']['name'] ) &&
			 in_array( $element['parent']['type'], $this->laneTypes )
		) {
			$titleText = "$process/{$element['parent']['name']}/{$element['name']}";
		} else {
			$titleText = "{$process}/{$element['name']}";
		}

		$titleText = $this->sanitizeTitle( $titleText );

		$title = Title::newFromText( $titleText, NS_PROCESS );

		if ( !$title ) {
			throw new CpdXmlProcessingException(
				Message::newFromKey( "cpd-error-could-not-create-title", $element["id"] )->text()
			);
		}

		return $title->getPrefixedDBkey();
	}

	/**
	 * Remove temporary and unused fields from the data
	 *
	 * @param array &$element
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

	/**
	 * @param string $titleText
	 *
	 * @return string
	 */
	private function sanitizeTitle( string $titleText ): string {
		$titleText = str_replace( "\n", "", $titleText );

		return $titleText;
	}
}
