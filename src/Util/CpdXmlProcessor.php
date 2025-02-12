<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
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
		Config $config
	) {
		$this->dedicatedSubpageTypes = [];
		if ( $config->has( 'CPDDedicatedSubpageTypes' ) ) {
			$this->dedicatedSubpageTypes = array_map( function ( string $type ) {
				$type = str_replace( 'bpmn:', '', $type );

				return lcfirst( $type );
			}, $config->get( 'CPDDedicatedSubpageTypes' ) );
		}

		$this->laneTypes = [];
		if ( $config->has( 'CPDLaneTypes' ) ) {
			$this->laneTypes = array_map( function ( string $type ) {
				$type = str_replace( 'bpmn:', '', $type );

				return lcfirst( $type );
			}, $config->get( 'CPDLaneTypes' ) );
		}
	}

	/**
	 * @param string $process
	 * @param string $xmlString
	 * @param string|null $oldXmlString
	 *
	 * @return CpdElement[]
	 * @throws Exception
	 */
	public function makeElements( string $process, string $xmlString, ?string $oldXmlString = null ): array {
		$descriptionPageElements = $this->createAllElementsData( $process, $xmlString );

		if ( $oldXmlString ) {
			$this->setOldDescriptionPages( $process, $oldXmlString, $descriptionPageElements );
		}

		return $descriptionPageElements;
	}

	/**
	 * @param string $process
	 * @param string $xmlString
	 *
	 * @return array
	 * @throws Exception
	 */
	private function createAllElementsData( string $process, string $xmlString ): array {
		$elementsData = [];
		$xml = new SimpleXMLElement( $xmlString );
		$xml->registerXPathNamespace( 'bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL' );
		$xmlElements = $xml->xpath( '//bpmn:*' );
		foreach ( $xmlElements as $xmlElement ) {
			$type = lcfirst( $xmlElement->getName() );
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
		$this->setParents( $descriptionPageElements, $elementsData );
		$this->setConnections( $descriptionPageElements, $elementsData );
		$this->setDescriptionPages( $process, $descriptionPageElements );

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
	 * @param array $descriptionPageElements
	 * @param array $elementsData
	 *
	 * @return void
	 */
	private function setParents( array &$descriptionPageElements, array $elementsData ): void {
		$parents = $this->filterByType( $elementsData, [ 'participant' ] );

		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			foreach ( $parents as $parent ) {
				if ( empty( $parent['processRef'] ) ) {
					continue;
				}
				if ( $descriptionPageElement['parentRef'] === $parent['processRef'] ) {
					$descriptionPageElement['parent'] = $parent;
				}
			}
		}
	}

	/**
	 * @param array $descriptionPageElements
	 * @param array $elementsData
	 *
	 * @return void
	 */
	private function setConnections( array &$descriptionPageElements, array $elementsData ): void {
		$connections = $this->filterByType( $elementsData, [ 'sequenceFlow' ] );

		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			foreach ( $connections as $connection ) {
				if ( empty( $connection['sourceRef'] ) || empty( $connection['targetRef'] ) ) {
					continue;
				}
				if ( $descriptionPageElement['id'] === $connection['sourceRef'] ) {
					$filteredElements = array_filter(
						$descriptionPageElements,
						fn( $element ) => $element['id'] === $connection['targetRef']
					);
					$targetElement = reset( $filteredElements );
					$descriptionPageElement['outgoingLinks'][] = $targetElement;
				}
				if ( $descriptionPageElement['id'] === $connection['targetRef'] ) {
					$filteredElements = array_filter(
						$descriptionPageElements,
						fn( $element ) => $element['id'] === $connection['sourceRef']
					);
					$sourceElement = reset( $filteredElements );
					$descriptionPageElement['incomingLinks'][] = $sourceElement;
				}
			}
		}
	}


	/**
	 * @param string $process
	 * @param array $descriptionPageElements
	 *
	 * @return void
	 * @throws Exception
	 */
	private function setDescriptionPages(
		string $process,
		array &$descriptionPageElements,
	): void {
		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			$descriptionPageElement['descriptionPage'] = $this->makeDescriptionPageTitle(
				$process,
				$descriptionPageElement
			);
		}
	}

	/**
	 * @param string $process
	 * @param string $oldXmlString
	 * @param array $descriptionPageElements
	 *
	 * @return void
	 * @throws Exception
	 */
	private function setOldDescriptionPages(
		string $process,
		string $oldXmlString,
		array &$descriptionPageElements,
	): void {
		$oldDescriptionPageElements = $this->createAllElementsData( $process, $oldXmlString );
		foreach ( $descriptionPageElements as &$descriptionPageElement ) {
			$filteredElements = array_filter(
				$oldDescriptionPageElements,
				fn( $element ) => $element['id'] === $descriptionPageElement['id']
			);
			$oldDescriptionPageElement = reset( $filteredElements );
			if ( $oldDescriptionPageElement ) {
				$descriptionPageElement['oldDescriptionPage'] = $oldDescriptionPageElement['descriptionPage'];
			}
		}
	}

	/**
	 * @param string $process
	 * @param array $element
	 *
	 * @return Title
	 * @throws Exception
	 */
	private function makeDescriptionPageTitle( string $process, array $element ): Title {
		if ( empty( $element['name'] ) ) {
			throw new Exception( Message::newFromKey( "cpd-error-message-missing-label", $element["id"] )->text() );
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
			throw new Exception( Message::newFromKey( "cpd-error-could-not-create-title", $element["id"] )->text() );
		}

		return $title;
	}
}
