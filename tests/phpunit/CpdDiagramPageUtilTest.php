<?php

namespace CognitiveProcessDesigner\Tests;

use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CognitiveProcessDesigner\Util\CpdDiagramPageUtil
 */
class CpdDiagramPageUtilTest extends TestCase {
	/**
	 * @covers ::getProcess
	 *
	 * @param Title $title
	 * @param string $process
	 *
	 * @return void
	 *
	 * @dataProvider provideTitles
	 * @throws CpdInvalidNamespaceException
	 */
	public function testGetProcess( Title $title, string $process ): void {
		if ( $process === 'exception' ) {
			$this->expectException( CpdInvalidNamespaceException::class );
			CpdDiagramPageUtil::getProcess( $title );

			return;
		}

		$this->assertEquals( $process, CpdDiagramPageUtil::getProcess( $title ) );
	}

	/**
	 * @covers ::getLaneFromTitle
	 *
	 * @param Title $title
	 * @param string $process
	 * @param array $lanes
	 *
	 * @return void
	 *
	 * @throws CpdInvalidNamespaceException
	 * @dataProvider provideTitles
	 */
	public function testGetLanesFromTitle( Title $title, string $process, array $lanes ): void {
		if ( $process === 'exception' ) {
			$this->expectException( CpdInvalidNamespaceException::class );
			CpdDiagramPageUtil::getLanesFromTitle( $title );

			return;
		}

		$this->assertEquals( $lanes, CpdDiagramPageUtil::getLanesFromTitle( $title ) );
	}

	/**
	 * @return array
	 */
	public function provideTitles(): array {
		return [
			[
				'title' => Title::newFromDBkey( 'Process:Mouse/a' ),
				'process' => 'Mouse',
				'lanes' => []
			],
			[
				'title' => Title::newFromDBkey( 'Process:horse/lane1/a' ),
				'process' => 'Horse',
				'lanes' => [ 'lane1' ]
			],
			[
				'title' => Title::newFromDBkey( 'Process:cat/lane1/lane2/lane3/a' ),
				'process' => 'Cat',
				'lanes' => [
					'lane1',
					'lane2',
					'lane3'
				]
			],
			[
				'title' => Title::newFromDBkey( 'InvalidProcessNamespaceProcess:cat/lane1/lane2/lane3/lane4/a' ),
				'process' => 'exception',
				'lanes' => [
					'lane1',
					'lane2',
					'lane3',
					'lane4'
				]
			],
		];
	}
}
