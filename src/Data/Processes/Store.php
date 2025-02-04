<?php

namespace CognitiveProcessDesigner\Data\Processes;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class Store implements IStore {

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param CpdDiagramPageUtil $util
	 */
	public function __construct(
		private readonly ILoadBalancer $loadBalancer,
		private readonly CpdDiagramPageUtil $util
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
		return new Reader( $this->loadBalancer, $this->util );
	}
}
