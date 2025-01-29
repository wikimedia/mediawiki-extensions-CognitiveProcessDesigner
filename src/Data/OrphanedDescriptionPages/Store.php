<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Extension\ContentStabilization\StabilizationLookup;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class Store implements IStore {

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var StabilizationLookup */
	private StabilizationLookup $lookup;

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $cpdDiagramPageUtil;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param CpdDiagramPageUtil $cpdDiagramPageUtil
	 * @param StabilizationLookup $lookup
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		CpdDiagramPageUtil $cpdDiagramPageUtil,
		StabilizationLookup $lookup
	) {
		$this->loadBalancer = $loadBalancer;
		$this->cpdDiagramPageUtil = $cpdDiagramPageUtil;
		$this->lookup = $lookup;
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
		return new Reader( $this->loadBalancer, $this->cpdDiagramPageUtil, $this->lookup );
	}
}
