<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\CpdNavigationConnection;
use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
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
	 * @throws CpdCreateElementException
	 */
	public function getConnections( Title $title ): array {
		$connections = [
			'incoming' => [],
			'outgoing' => []
		];

		$element = $this->findElementForPage( $title );
		if ( !$element ) {
			return $connections;
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

		return $connections;
	}

	/**
	 * Find element corresponding to the title
	 *
	 * @param Title $title
	 *
	 * @return CpdElement|null
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidContentException
	 * @throws CpdInvalidNamespaceException
	 * @throws CpdXmlProcessingException
	 */
	private function findElementForPage( Title $title ): CpdElement|null {
		$process = CpdDiagramPageUtil::getProcess( $title );
		$xml = $this->diagramPageUtil->getXml( CpdDiagramPageUtil::getProcess( $title ) );

		$element = null;
		foreach ( $this->xmlProcessor->createElements( $process, $xml ) as $element ) {
			if ( $element->getDescriptionPage()->equals( $title ) ) {
				break;
			}
		}

		return $element;
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
	private static function createConnectionText( Title $title, bool $isLaneChange = true ): string {
		$lanes = CpdDiagramPageUtil::getLanesFromTitle( $title );
		$lastLane = array_pop( $lanes );
		if ( !$lastLane || !$isLaneChange ) {
			return $title->getSubpageText();
		}

		return sprintf( '%s:</br>%s', $lastLane, $title->getSubpageText() );
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
}
