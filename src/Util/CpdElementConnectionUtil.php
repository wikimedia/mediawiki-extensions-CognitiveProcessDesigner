<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\CpdNavigationConnection;
use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\HookHandler\ModifyDescriptionPage;
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
	 * @param int|null $revId
	 *
	 * @return array [
	 * 'incoming' => CpdNavigationConnection[],
	 * 'outgoing' => CpdNavigationConnection[]
	 * ]
	 *
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 * @throws CpdInvalidContentException
	 * @throws CpdInvalidNamespaceException
	 * @throws CpdXmlProcessingException
	 */
	public function getConnections( Title $title, ?int $revId = null ): array {
		$connections = [
			'incoming' => [],
			'outgoing' => []
		];

		$element = $this->findElementForPage( $title, $revId );
		if ( !$element ) {
			return $connections;
		}

		foreach ( $element->getOutgoingLinks() as $outgoingLink ) {
			$connections['outgoing'][] = $this->createNavigationConnection(
				$outgoingLink->getDescriptionPage()->getPrefixedDBkey(),
				$outgoingLink->getType(),
				$title,
				$revId
			);
		}
		foreach ( $element->getIncomingLinks() as $incomingLink ) {
			$connections['incoming'][] = $this->createNavigationConnection(
				$incomingLink->getDescriptionPage()->getPrefixedDBkey(),
				$incomingLink->getType(),
				$title,
				$revId
			);
		}

		return $connections;
	}

	/**
	 * Find element corresponding to the title
	 * Use latest revision if revId is not provided
	 *
	 * @param Title $title
	 * @param int|null $revId
	 *
	 * @return CpdElement|null
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 * @throws CpdInvalidContentException
	 * @throws CpdInvalidNamespaceException
	 * @throws CpdXmlProcessingException
	 */
	private function findElementForPage( Title $title, ?int $revId = null ): CpdElement|null {
		$process = CpdDiagramPageUtil::getProcess( $title );
		$xml = $this->diagramPageUtil->getXml( CpdDiagramPageUtil::getProcess( $title ), $revId );

		foreach ( $this->xmlProcessor->createElements( $process, $xml ) as $element ) {
			if ( $element->getDescriptionPage()->equals( $title ) ) {
				return $element;
			}
		}

		return null;
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
	 * @param int|null $revId
	 *
	 * @return CpdNavigationConnection
	 * @throws CpdInvalidNamespaceException
	 */
	private function createNavigationConnection(
		string $dbKey,
		string $type,
		Title $source,
		?int $revId = null
	): CpdNavigationConnection {
		$target = Title::newFromDBkey( $dbKey );
		$queryParam = $revId ? ModifyDescriptionPage::REVISION_QUERY_PARAM . '=' . $revId : '';
		$link = $target->getFullURL( $queryParam );

		$lanes = CpdDiagramPageUtil::getLanesFromTitle( $target );
		$isLaneChange = $lanes !== CpdDiagramPageUtil::getLanesFromTitle( $source );

		return new CpdNavigationConnection(
			self::createConnectionText( $target, $isLaneChange ), $link, $type, $isLaneChange
		);
	}
}
