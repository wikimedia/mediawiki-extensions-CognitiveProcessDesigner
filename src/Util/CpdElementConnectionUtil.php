<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\CpdNavigationConnection;
use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use MediaWiki\Html\TemplateParser;
use MediaWiki\Message\Message;
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
	 * @param Title $title
	 * @param int|null $revId
	 *
	 * @return string
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 * @throws CpdInvalidContentException
	 * @throws CpdInvalidNamespaceException
	 * @throws CpdXmlProcessingException
	 */
	public function createNavigationHtml(
		Title $title,
		?int $revId = null
	): string {
		$connections = $this->getConnections( $title, $revId );

		$incoming = $this->buildConnection( $connections['incoming'], 'incoming' );
		$outgoing = $this->buildConnection( $connections['outgoing'], 'outgoing' );

		$templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);

		return $templateParser->processTemplate(
			'DescriptionPageNavigation',
			[
				'incoming' => $incoming,
				'outgoing' => $outgoing,
				'incomingheading' => Message::newFromKey( 'cpd-description-navigation-incoming-label' )->text(),
				'outgoingheading' => Message::newFromKey( 'cpd-description-navigation-outgoing-label' )->text()
			]
		);
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
		$lanes = CpdDiagramPageUtil::getLanesFromTitle( $target );
		$isLaneChange = $lanes !== CpdDiagramPageUtil::getLanesFromTitle( $source );

		return CpdNavigationConnection::createFromTitle( $target, $type, $isLaneChange, $revId );
	}

	/**
	 * @param CpdNavigationConnection[] $connections
	 * @param string $direction
	 *
	 * @return array
	 */
	private function buildConnection( array $connections, string $direction ): array {
		$result = [];
		foreach ( $connections as $connection ) {
			$con = $connection->toArray();
			$item = [
				'link' => $con['link'],
				'text' => $con['text'],
				'class' => $direction . ' ' . $con['type'],
				'title' => $con['title']
			];
			if ( $con['isLaneChange'] ) {
				$item['class'] .= ' cpd-lane-change';
			}
			$result[] = $item;
		}

		return $result;
	}
}
