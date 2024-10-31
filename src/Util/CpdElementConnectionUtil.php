<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
use Config;
use Title;
use Wikimedia\Rdbms\ILoadBalancer;

class CpdElementConnectionUtil {
	/** @var ILoadBalancer */
	private ILoadBalancer $loadBalancer;

	/** @var Config */
	private Config $config;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param Config $config
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		Config $config,
	) {
		$this->loadBalancer = $loadBalancer;
		$this->config = $config;
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
						'to_page' => $outgoingLink,
					],
					__METHOD__
				);
			}
		}
	}

	/**
	 * @param Title $title
	 *
	 * @return string[] dbkeys
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

		return $links;
	}

	/**
	 * @param Title $title
	 *
	 * @return string[] dbkeys
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

		return $links;
	}
}
