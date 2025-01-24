<?php

namespace CognitiveProcessDesigner\Tests;

use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use MediaWiki\Config\Config;
use MediaWiki\Page\PageStore;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use PHPUnit\Framework\TestCase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @coversDefaultClass \CognitiveProcessDesigner\Util\CpdDescriptionPageUtil
 */
class CpdDescriptionPageUtilTest extends TestCase {
	/**
	 * @var CpdDescriptionPageUtil
	 */
	private CpdDescriptionPageUtil $util;

	protected function setUp(): void {
		parent::setUp();

		if ( !defined( 'NS_PROCESS' ) ) {
			define( 'NS_PROCESS', 1530 );
			define( 'NS_PROCESS_TALK', 1531 );
		}

		$mockTitleFactory = $this->createMock( TitleFactory::class );
		$mockTitleFactory->method( 'makeTitle' )->willReturnCallback( function ( $namespace, $title ) {
			$titleMock = $this->createMock( Title::class );
			$titleMock->method( 'getFullText' )->willReturn( sprintf( '%s:%s', 'Process', $title ) );

			return $titleMock;
		} );
		$this->util = new CpdDescriptionPageUtil(
			$this->createMock( PageStore::class ),
			$this->createMock( ILoadBalancer::class ),
			$this->createMock( WikiPageFactory::class ),
			$this->createMock( Config::class ),
			$this->createMock( CpdElementConnectionUtil::class ),
		);
	}

	/**
	 * @covers ::isDescriptionPage
	 *
	 * @param Title $title
	 * @param bool $expected
	 *
	 * @return void
	 *
	 * @dataProvider provideTitles
	 */
	public function testIsDescriptionPage( Title $title, bool $expected ): void {
		$this->assertEquals( $expected, $this->util->isDescriptionPage( $title ) );
	}

	/**
	 * @return array
	 */
	public function provideTitles(): array {
		$a = Title::newFromText( 'a' );

		$b = Title::newFromText( 'b', 1530 );

		$c = Title::newFromText( 'c', 1530 );
		$c->setContentModel( 'wikitext' );

		$d = Title::newFromText( 'd', 1530 );
		$d->setContentModel( 'CPD' );

		return [
			'test1' => [
				'title' => $a,
				'expected' => false
			],
			'test2' => [
				'title' => $b,
				'expected' => false
			],
			'test3' => [
				'title' => $c,
				'expected' => true
			],
			'test4' => [
				'title' => $d,
				'expected' => false
			]
		];
	}
}
