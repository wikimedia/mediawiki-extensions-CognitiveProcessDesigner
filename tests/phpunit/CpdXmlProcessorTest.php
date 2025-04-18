<?php

namespace CognitiveProcessDesigner\Tests;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\CpdElementFactory;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use Exception;
use MediaWiki\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CognitiveProcessDesigner\Util\CpdXmlProcessor
 */
class CpdXmlProcessorTest extends TestCase {

	/** @var CpdXmlProcessor */
	private CpdXmlProcessor $xmlProcessor;

	protected function setUp(): void {
		parent::setUp();

		$subpageTypes = [
			"bpmn:Gateway",
			"bpmn:ExclusiveGateway",
			"bpmn:Task",
			"bpmn:StartEvent",
			"bpmn:EndEvent"
		];
		$laneTypes = [
			"bpmn:Participant",
			"bpmn:Lane"
		];
		$configMock = $this->createMock( Config::class );
		$configMock->method( 'has' )->willReturn( true );
		$configMock->method( 'get' )->willReturnCallback( static function ( $arg ) use ( $subpageTypes, $laneTypes ) {
			if ( $arg === 'CPDLaneTypes' ) {
				return $laneTypes;
			} elseif ( $arg === 'CPDDedicatedSubpageTypes' ) {
				return $subpageTypes;
			}

			return [];
		} );

		$this->xmlProcessor = new CpdXmlProcessor(
			$configMock, new CpdElementFactory()
		);
	}

	/**
	 * @covers ::findDescriptionPageEligibleElements
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testFindDescriptionPageEligibleElements(): void {
		$fixturePath = __DIR__ . '/fixtures';
		$xml = file_get_contents( $fixturePath . '/diagram.xml' );
		$oldXml = file_get_contents( $fixturePath . '/oldDiagram.xml' );
		$resultElements = json_decode( file_get_contents( $fixturePath . '/elementsData.json' ), true );
		$elements = $this->xmlProcessor->createElements( 'BackendElements', $xml, $oldXml );
		$this->assertEquals( $resultElements, array_map( static function ( CpdElement $element ) {
			// Cant use jsonSerialize here because of check for existence which is not independent of the wiki
			$data = [
				'id' => $element->getId(),
				'type' => $element->getType(),
				'label' => $element->getLabel(),
			];

			if ( $element->getDescriptionPage() ) {
				$data['descriptionPage'] = $element->getDescriptionPage()->getPrefixedDBkey();
			}

			return $data;
		}, $elements ) );
	}

}
