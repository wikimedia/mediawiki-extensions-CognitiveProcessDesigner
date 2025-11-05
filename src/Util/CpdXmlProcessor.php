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

	public function __construct(
		Config $config,
		private readonly CpdElementFactory $cpdElementFactory
	) {
		if ( $config->has( 'CPDDedicatedSubpageTypes' ) ) {
			$this->dedicatedSubpageTypes = $config->get( 'CPDDedicatedSubpageTypes' );
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

		$descriptionPageElements = $this->createDescriptionPageElements( $xmlString, $process );

		if ( $oldXmlString ) {
			$this->setOldDescriptionPages( $process, $oldXmlString, $descriptionPageElements );
		}

		return $this->cpdElementFactory->makeElements( $descriptionPageElements );
	}

	/**
	 * @param string $xmlString
	 * @param string $process
	 *
	 * @return array
	 * @throws CpdXmlProcessingException
	 */
	private function createDescriptionPageElements( string $xmlString, string $process ): array {
		try {
			$xml = new SimpleXMLElement( $xmlString );
			$xml->registerXPathNamespace( 'bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL' );
		} catch ( Exception $e ) {
			throw new CpdXmlProcessingException( Message::newFromKey( "cpd-error-xml-parse-error" )->text() );
		}

		// First set all description pages
		$descriptionPageElements = $this->extractDescriptionPageElements( $xml );
		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			$this->setDescriptionPage( $descriptionPageElement, $process );
		}

		// Then set all connections
		$sequenceFlows = CpdSequenceFlowUtil::fixSubpageSequenceFlows(
			$this->extractSequenceFlows( $xml ),
			$descriptionPageElements
		);

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
		}

		return $descriptionPageElements;
	}

	/**
	 * @param SimpleXMLElement $xml
	 *
	 * @return array
	 */
	private function extractDescriptionPageElements( SimpleXMLElement $xml ): array {
		$descriptionPageElements = [];
		$elementsQuery = implode(
			" or ",
			array_map(
				static fn ( $type ) => 'local-name() = "' . lcfirst( str_replace( 'bpmn:', '', $type ) ) . '"',
				$this->dedicatedSubpageTypes
			)
		);

		$participantNames = [];

		$xmlParticipants = $xml->xpath( '//bpmn:participant' );
		foreach ( $xmlParticipants as $xmlParticipant ) {
			$attributes = $xmlParticipant->attributes();
			$processId = (string)$attributes->processRef;
			$participantNames[ $processId ] = (string)$attributes->name;
		}

		$processes = $xml->xpath( '//bpmn:process' );
		foreach ( $processes as $process ) {
			$process->registerXPathNamespace( 'bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL' );
			$processId = (string)$process->attributes()->id;

			$lanes = [];
			$laneSets = $process->xpath( './/bpmn:laneSet' );
			if ( !empty( $laneSets ) ) {
				$this->extractLanes( $laneSets[0], $lanes );
			}

			$elements = $process->xpath( './/bpmn:*[' . $elementsQuery . ']' );

			foreach ( $elements as $element ) {
				$type = 'bpmn:' . ucfirst( $element->getName() );
				$attributes = $element->attributes();
				$id = (string)$attributes->id;
				$name = (string)$attributes->name;

				// ERM44753 Fallback to id if name is empty
				if ( empty( $name ) ) {
					$name = $id;
				}

				$parents = [];

				if ( isset( $participantNames[ $processId ] ) ) {
					$parents[] = $participantNames[ $processId ];
				}

				if ( isset( $lanes[ $id ] ) ) {
					$parents = array_merge( $parents, $lanes[ $id ] );
				}

				$descriptionPageElements[] = [
					'type' => $type,
					'id' => $id,
					'label' => $name,
					'parents' => $parents
				];
			}
		}

		return $descriptionPageElements;
	}

	/**
	 * @param SimpleXMLElement $xml
	 *
	 * @return mixed
	 */
	private function extractSequenceFlows( SimpleXMLElement $xml ): array {
		return array_map( static function ( SimpleXMLElement $xmlSequenceFlow ) {
			$attributes = $xmlSequenceFlow->attributes();

			return [
				'sourceRef' => (string)$attributes->sourceRef,
				'targetRef' => (string)$attributes->targetRef
			];
		}, $xml->xpath( '//bpmn:sequenceFlow' ) );
	}

	/**
	 * @param SimpleXMLElement $laneSet
	 * @param array &$map
	 * @param array $parentStack
	 *
	 * @return void
	 */
	private function extractLanes( SimpleXMLElement $laneSet, array &$map = [], array $parentStack = [] ): void {
		$ns = $laneSet->getNamespaces( true );

		foreach ( $laneSet->children( $ns['bpmn'] ) as $lane ) {
			if ( $lane->getName() !== 'lane' ) {
				continue;
			}

			$attributes = $lane->attributes();
			$laneName = (string)$attributes->name;
			$newStack = array_merge( $parentStack, [ $laneName ] );

			// Process flowNodeRefs
			foreach ( $lane->children( $ns['bpmn'] ) as $child ) {
				if ( $child->getName() === 'flowNodeRef' ) {
					$ref = (string)$child;
					$map[ $ref ] = $newStack;
				}

				// Recurse into childLaneSet if present
				if ( $child->getName() === 'childLaneSet' ) {
					$this->extractLanes( $child, $map, $newStack );
				}
			}
		}
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
			$oldDescriptionPageElements = $this->createDescriptionPageElements( $oldXmlString, $process );
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
		if ( empty( $element['label'] ) ) {
			throw new CpdXmlProcessingException(
				Message::newFromKey( "cpd-error-message-missing-label", $element["id"] )->text()
			);
		}

		if ( empty( $element['parents'] ) ) {
			$titleText = "{$process}/{$element['label']}";
		} else {
			$titleText = "{$process}/" . implode( "/", $element['parents'] ) . "/{$element['label']}";
		}

		$titleText = $this->sanitizeTitle( $titleText );

		$title = Title::makeTitle( NS_PROCESS, $titleText );

		if ( !$title ) {
			throw new CpdXmlProcessingException(
				Message::newFromKey( "cpd-error-could-not-create-title", $element["id"] )->text()
			);
		}

		return $title->getPrefixedDBkey();
	}

	/**
	 * ERM42675
	 *
	 * 00AD is a soft hyphen
	 *
	 * @param string $titleText
	 *
	 * @return string
	 */
	private function sanitizeTitle( string $titleText ): string {
		$disallowedCharacters = [
			'#',
			'<',
			'>',
			'[',
			']',
			'|',
			'{',
			'}',
			'?',
			'+',
			'%',
			"\n",
			"\u{00AD}"
		];

		foreach ( $disallowedCharacters as $disallowedCharacter ) {
			$titleText = str_replace( $disallowedCharacter, '', $titleText );
		}

		return trim( $titleText );
	}
}
