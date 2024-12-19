<?php

namespace CognitiveProcessDesigner\Tests;

use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use PHPUnit\Framework\TestCase;
use Title;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @coversDefaultClass \CognitiveProcessDesigner\Util\CpdElementConnectionUtil
 */
class CpdElementConnectionUtilTest extends TestCase {
	/** @var CpdElementConnectionUtil */
	private CpdElementConnectionUtil $util;

	/** @var IDatabase */
	private IDatabase $db;

	protected function setUp(): void {
		parent::setUp();

		if ( !defined( 'NS_PROCESS' ) ) {
			define( 'NS_PROCESS', 1530 );
			define( 'NS_PROCESS_TALK', 1531 );
		}

		$this->db = $this->createMock( IDatabase::class );
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )->willReturn( $this->db );

		$this->util = new CpdElementConnectionUtil( $lb );
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
	 * @dataProvider provideConnections
	 */
	public function testCreateNavigationConnection( string $dbKey, array $connections, array $expected ): void {
		$title = Title::newFromDBkey( $dbKey );
		$this->db->method( 'select' )->willReturn( $connections );
		$connections = $this->util->getIncomingConnections( $title );

		for ( $i = 0; $i < count( $connections ); $i++ ) {
			$data = $connections[$i]->toArray();
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
					(object)[ 'from_page' => 'Process:Foo/lane1/lane2/b' ],
					(object)[ 'from_page' => 'Process:Foo/c' ],
				],
				[
					[
						'text' => 'lane2:b',
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
					(object)[ 'from_page' => 'Process:Foo/lane1/lane2/lane3/b' ],
					(object)[ 'from_page' => 'Process:Foo/lane1/lane2/c' ],
					(object)[ 'from_page' => 'Process:Foo/d' ],
				],
				[
					[
						'text' => 'lane3:b',
						'isLaneChange' => false
					],
					[
						'text' => 'lane2:c',
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
