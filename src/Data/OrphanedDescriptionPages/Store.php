<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class Store implements IStore {

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $util;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param CpdDiagramPageUtil $util
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		CpdDiagramPageUtil $util
	) {
		$this->loadBalancer = $loadBalancer;
		$this->util = $util;
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
