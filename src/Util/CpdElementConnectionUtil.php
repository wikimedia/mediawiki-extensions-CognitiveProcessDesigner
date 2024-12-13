<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\CpdNavigationConnection;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use Title;
use Wikimedia\Rdbms\ILoadBalancer;

class CpdElementConnectionUtil {
	/** @var ILoadBalancer */
	private ILoadBalancer $loadBalancer;

	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct(
		ILoadBalancer $loadBalancer
	) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param CpdElement[] $elements
	 * @param string $process
	 *
	 * @return void
	 */
	public function updateElementConnections( array $elements, string $process ): void {
		$dbw = $this->loadBalancer->getConnectionRef( DB_PRIMARY );

		// Clear rows from this process
		$dbw->delete(
			'cpd_element_connections',
			[ 'process' => $process ],
			__METHOD__
		);

		foreach ( $elements as $element ) {
			foreach ( $element->getOutgoingLinks() as $outgoingLink ) {
				$dbw->insert(
					'cpd_element_connections',
					[
						'process' => $process,
						'from_page' => $element->getDescriptionPage()->getPrefixedDBkey(),
						'from_type' => $element->getType(),
						'to_page' => $outgoingLink->getDescriptionPage()->getPrefixedDBkey(),
                        'to_type' => $outgoingLink->getType()
					],
					__METHOD__
				);
			}
		}
	}

	/**
	 * @param Title $title
	 *
	 * @return CpdNavigationConnection[]
	 * @throws CpdInvalidNamespaceException
	 */
	public function getIncomingConnections( Title $title ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$rows = $dbr->select(
			'cpd_element_connections',
			[ 'from_page' ],
			[ 'to_page' => $title->getPrefixedDBkey() ],
			__METHOD__
		);

		$links = [];
		foreach ( $rows as $row ) {
			$links[] = $row->from_page;
		}

		return array_map( function ( $link ) use ( $title ) {
			return $this->createNavigationConnection( $link, $title );
		}, $links );
	}

	/**
	 * @param Title $title
	 *
	 * @return CpdNavigationConnection[]
	 * @throws CpdInvalidNamespaceException
	 */
	public function getOutgoingConnections( Title $title ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$rows = $dbr->select(
			'cpd_element_connections',
			[ 'to_page' ],
			[ 'from_page' => $title->getPrefixedDBkey() ],
			__METHOD__
		);

		$links = [];
		foreach ( $rows as $row ) {
			$links[] = $row->to_page;
		}

		return array_map( function ( $link ) use ( $title ) {
			return $this->createNavigationConnection( $link, $title );
		}, $links );
	}

	/**
	 * @param string $dbKey
	 * @param Title $source
	 *
	 * @return CpdNavigationConnection
	 * @throws CpdInvalidNamespaceException
	 */
	private function createNavigationConnection( string $dbKey, Title $source ): CpdNavigationConnection {
		$target = Title::newFromDBkey( $dbKey );
		$lanes = CpdDiagramPageUtil::getLanesFromTitle( $target );
		$isLaneChange = $lanes !== CpdDiagramPageUtil::getLanesFromTitle( $source );
		$link = $target->getFullURL();

		return new CpdNavigationConnection( self::createConnectionText( $target ), $link, $isLaneChange );
	}

	/**
	 * Include last lane in the connection text
	 *
	 * @param Title $title
	 *
	 * @return string
	 * @throws CpdInvalidNamespaceException
	 */
	public static function createConnectionText( Title $title ): string {
		$lanes = CpdDiagramPageUtil::getLanesFromTitle( $title );
		$lastLane = array_pop( $lanes );
		if ( !$lastLane ) {
			return $title->getSubpageText();
		}

		return sprintf( '%s:</br>%s', $lastLane, $title->getSubpageText() );
	}
}
