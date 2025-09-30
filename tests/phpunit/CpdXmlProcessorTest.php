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
			"bpmn:Task",
			"bpmn:StartEvent",
			"bpmn:EndEvent"
		];
		$configMock = $this->createMock( Config::class );
		$configMock->method( 'has' )->willReturn( true );
		$configMock->method( 'get' )->willReturnCallback( static function ( $arg ) use ( $subpageTypes ) {
			if ( $arg === 'CPDDedicatedSubpageTypes' ) {
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

	/**
	 * @covers ::findDescriptionPageEligibleElements
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testDiagramWithoutBpmnPrefixes(): void {
		$fixturePath = __DIR__ . '/fixtures';
		$xml = file_get_contents( $fixturePath . '/diagram_without_bpmn_prefixes.xml' );
		$resultElements = json_decode( file_get_contents( $fixturePath . '/elementsData.json' ), true );
		$elements = $this->xmlProcessor->createElements( 'BackendElements', $xml );
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

	/**
	 * @covers ::createRealSequenceFlows
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testSimpleCreateRealSequenceFlows(): void {
		$xml = file_get_contents( __DIR__ . '/fixtures/gatewayDiagram.xml' );
		$elements = $this->xmlProcessor->createElements( 'RealSequenceFlows', $xml );
		$this->assertCount( 0, $elements[0]->getIncomingLinks() );
		$this->assertCount( 4, $elements[0]->getOutgoingLinks() );
		$this->assertCount( 1, $elements[1]->getIncomingLinks() );
		$this->assertCount( 0, $elements[1]->getOutgoingLinks() );
		$this->assertCount( 1, $elements[2]->getIncomingLinks() );
		$this->assertCount( 0, $elements[2]->getOutgoingLinks() );
		$this->assertCount( 1, $elements[3]->getIncomingLinks() );
		$this->assertCount( 0, $elements[3]->getOutgoingLinks() );
		$this->assertCount( 1, $elements[4]->getIncomingLinks() );
		$this->assertCount( 0, $elements[4]->getOutgoingLinks() );
	}

	/**
	 * @covers ::createAllElementsData
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testCreatingParentRelations(): void {
		$xml = file_get_contents( __DIR__ . '/fixtures/laneDiagram.xml' );
		$elements = $this->xmlProcessor->createElements( 'ParentRelations', $xml );
		$this->assertEquals( "Process:ParentRelations/participant1/lane3/sublane1/start", $elements[0]->getDescriptionPage()->getPrefixedDBkey() );
		$this->assertEquals( "Process:ParentRelations/participant1/lane1/end", $elements[1]->getDescriptionPage()->getPrefixedDBkey() );
		$this->assertEquals( "Process:ParentRelations/participant1/lane2/task1", $elements[2]->getDescriptionPage()->getPrefixedDBkey() );
		$this->assertEquals( "Process:ParentRelations/participant1/lane3/sublane1/end_b", $elements[3]->getDescriptionPage()->getPrefixedDBkey() );
		$this->assertEquals( "Process:ParentRelations/participant1/lane3/sublane2/end_c", $elements[4]->getDescriptionPage()->getPrefixedDBkey() );
		$this->assertEquals( "Process:ParentRelations/participant2/end2", $elements[5]->getDescriptionPage()->getPrefixedDBkey() );
		$this->assertEquals( "Process:ParentRelations/participant2/start2", $elements[6]->getDescriptionPage()->getPrefixedDBkey() );
	}
}
