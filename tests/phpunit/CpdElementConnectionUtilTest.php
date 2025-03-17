<?php

namespace CognitiveProcessDesigner\Tests;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CognitiveProcessDesigner\Util\CpdElementConnectionUtil
 */
class CpdElementConnectionUtilTest extends TestCase {
	/** @var CpdElementConnectionUtil */
	private CpdElementConnectionUtil $util;

	/** @var CpdXmlProcessor */
	private CpdXmlProcessor $xmlProcessor;

	protected function setUp(): void {
		parent::setUp();

		if ( !defined( 'NS_PROCESS' ) ) {
			define( 'NS_PROCESS', 1530 );
			define( 'NS_PROCESS_TALK', 1531 );
		}

		$this->xmlProcessor = $this->createMock( CpdXmlProcessor::class );

		$this->util = new CpdElementConnectionUtil(
			$this->createMock( CpdDiagramPageUtil::class ), $this->xmlProcessor,
		);
	}

	/**
	 * @covers ::createNavigationConnection
	 *
	 * @param string $dbKey
	 * @param array $connections
	 * @param array $expected
	 *
	 * @return void
	 *
	 * @throws CpdInvalidNamespaceException
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidContentException
	 * @throws CpdXmlProcessingException
	 * @dataProvider provideConnections
	 */
	public function testCreateNavigationConnection( string $dbKey, array $connections, array $expected ): void {
		$title = Title::newFromDBkey( $dbKey );
		$this->xmlProcessor->method( 'createElements' )->willReturn( $connections );
		$connections = $this->util->getConnections( $title );

		for ( $i = 0; $i < count( $connections ); $i++ ) {
			$data = $connections['incoming'][$i]->toArray();
			$this->assertNotEmpty( $data['link'] );
			$this->assertEquals( $expected[$i]['text'], $data['text'] );
			$this->assertEquals( $expected[$i]['isLaneChange'], $data['isLaneChange'] );
		}
	}

	public function provideConnections() {
		return [
			[
				'Process:Foo/a',
				[
					CpdElement::fromElementJson( [
						'id' => 'a',
						'type' => 'start',
						'label' => 'a',
						'descriptionPage' => 'Process:Foo/a',
						'incomingLinks' => [
							[
								'id' => 'a',
								'type' => 'start',
								'label' => 'a',
								'descriptionPage' => 'Process:Foo/lane1/lane2/b',
							],
							[
								'id' => 'a',
								'type' => 'start',
								'label' => 'a',
								'descriptionPage' => 'Process:Foo/c',
							]
						],
					] ),
				],
				[
					[
						'text' => 'lane2:</br>b',
						'isLaneChange' => true
					],
					[
						'text' => 'c',
						'isLaneChange' => false
					],
				]
			],
			[
				'Process:Foo/lane1/lane2/lane3/a',
				[
					CpdElement::fromElementJson( [
						'id' => 'a',
						'type' => 'start',
						'label' => 'a',
						'descriptionPage' => 'Process:Foo/lane1/lane2/lane3/a',
						'incomingLinks' => [
							[
								'id' => 'a',
								'type' => 'start',
								'label' => 'a',
								'descriptionPage' => 'Process:Foo/lane1/lane2/lane3/b',
							],
							[
								'id' => 'a',
								'type' => 'start',
								'label' => 'a',
								'descriptionPage' => 'Process:Foo/lane1/lane2/c',
							],
							[
								'id' => 'a',
								'type' => 'start',
								'label' => 'a',
								'descriptionPage' => 'Process:Foo/d',
							]
						],
					] )
				],
				[
					[
						'text' => 'b',
						'isLaneChange' => false
					],
					[
						'text' => 'lane2:</br>c',
						'isLaneChange' => true
					],
					[
						'text' => 'd',
						'isLaneChange' => true
					],
				]
			]
		];
	}
}
