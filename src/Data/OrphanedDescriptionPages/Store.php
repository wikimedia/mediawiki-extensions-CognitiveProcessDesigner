<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class Store implements IStore {
	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param CpdDiagramPageUtil $cpdDiagramPageUtil
	 */
	public function __construct(
		private readonly ILoadBalancer $loadBalancer,
		private readonly CpdDiagramPageUtil $cpdDiagramPageUtil
	) {
	}

	/**
	 * @return null
	 */
	public function getWriter() {
		return null;
	}

	/**
	 * @return Reader
	 */
	public function getReader() {
		return new Reader( $this->loadBalancer, $this->cpdDiagramPageUtil );
	}
}
