<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdNavigationConnection;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use MediaWiki\Title\Title;

class CpdElementConnectionUtil {

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdXmlProcessor $xmlProcessor
	 */
	public function __construct(
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdXmlProcessor $xmlProcessor,
	) {
	}

	/**
	 * @param Title $title
	 *
	 * @return array [
	 * 'incoming' => CpdNavigationConnection[],
	 * 'outgoing' => CpdNavigationConnection[]
	 * ]
	 *
	 * @throws CpdInvalidNamespaceException
	 * @throws CpdInvalidContentException
	 * @throws CpdXmlProcessingException
	 */
	public function getConnections( Title $title ): array {
		$process = CpdDiagramPageUtil::getProcess( $title );
		$xml = $this->diagramPageUtil->getXml( CpdDiagramPageUtil::getProcess( $title ) );
		$cpdElements = $this->xmlProcessor->createElements( $process, $xml );
		$connections = [
			'incoming' => [],
			'outgoing' => []
		];

		foreach ( $cpdElements as $element ) {
			if ( $element->getDescriptionPage()->getPrefixedDBkey() !== $title->getPrefixedDBkey() ) {
				continue;
			}

			foreach ( $element->getOutgoingLinks() as $outgoingLink ) {
				$connections['outgoing'][] = $this->createNavigationConnection(
					$outgoingLink->getDescriptionPage()->getPrefixedDBkey(),
					$outgoingLink->getType(),
					$title
				);
			}
			foreach ( $element->getIncomingLinks() as $incomingLink ) {
				$connections['incoming'][] = $this->createNavigationConnection(
					$incomingLink->getDescriptionPage()->getPrefixedDBkey(),
					$incomingLink->getType(),
					$title
				);
			}
		}

		return $connections;
	}

	/**
	 * @param string $dbKey
	 * @param string $type
	 * @param Title $source
	 *
	 * @return CpdNavigationConnection
	 * @throws CpdInvalidNamespaceException
	 */
	private function createNavigationConnection( string $dbKey, string $type, Title $source ): CpdNavigationConnection {
		$target = Title::newFromDBkey( $dbKey );
		$lanes = CpdDiagramPageUtil::getLanesFromTitle( $target );
		$isLaneChange = $lanes !== CpdDiagramPageUtil::getLanesFromTitle( $source );
		$link = $target->getFullURL();

		return new CpdNavigationConnection(
			self::createConnectionText( $target, $isLaneChange ), $link, $type, $isLaneChange
		);
	}

	/**
	 * Include last lane in the connection text when it is a lane change
	 *
	 * @param Title $title
	 * @param bool $isLaneChange
	 *
	 * @return string
	 * @throws CpdInvalidNamespaceException
	 */
	public static function createConnectionText( Title $title, bool $isLaneChange = true ): string {
		$lanes = CpdDiagramPageUtil::getLanesFromTitle( $title );
		$lastLane = array_pop( $lanes );
		if ( !$lastLane || !$isLaneChange ) {
			return $title->getSubpageText();
		}

		return sprintf( '%s:</br>%s', $lastLane, $title->getSubpageText() );
	}
}
