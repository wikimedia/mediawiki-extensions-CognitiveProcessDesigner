<?php

namespace CognitiveProcessDesigner\Tests;

use MediaWikiLangTestCase;

/**
 * @coversDefaultClass \CognitiveProcessDesigner\HookHandler\RegisterNamespaces
 */
class RegisterNamespacesTest extends MediaWikiLangTestCase {
	/**
	 * @covers ::onCanonicalNamespaces
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function testNamespaceName(): void {
		$nsInfo = $this->getServiceContainer()->getNamespaceInfo();
		$name = $nsInfo->getCanonicalName( 1530 );
		$name_talk = $nsInfo->getCanonicalName( 1531 );

		$this->assertEquals( 'Process', $name );
		$this->assertEquals( 'Process_talk', $name_talk );
	}

	/**
	 * @covers ::onCanonicalNamespaces
	 *
	 * @return void
	 * @throws \MWException
	 */
	public function testNamespaceNameFallback(): void {
		$this->mergeMwGlobalArrayValue( 'wgExtraNamespaces', [
			9000 => 'Process',
		] );
		$nsInfo = $this->getServiceContainer()->getNamespaceInfo();
		$name = $nsInfo->getCanonicalName( 1530 );
		$name_talk = $nsInfo->getCanonicalName( 1531 );

		$this->assertEquals( 'CPD', $name );
		$this->assertEquals( 'CPD_talk', $name_talk );
	}

	/**
	 * @covers ::onCanonicalNamespaces
	 *
	 * @return void
	 * @throws \MWException
	 */
	public function testNamespaceNameCollisionException(): void {
		$this->expectExceptionMessage(
			'CognitiveProcessDesigner: Namespace names "Process" and "CPD" are already assigned'
		);

		$this->mergeMwGlobalArrayValue( 'wgExtraNamespaces', [
			9000 => 'Process',
			9002 => 'CPD',
		] );

		$nsInfo = $this->getServiceContainer()->getNamespaceInfo();
		$nsInfo->getCanonicalName( 1530 );
		$nsInfo->getCanonicalName( 1531 );
	}
}
