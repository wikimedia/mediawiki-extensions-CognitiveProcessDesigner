<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Extension\ContentStabilization\StabilizationLookup;
use Wikimedia\Rdbms\ILoadBalancer;

class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {

	/** @var ILoadBalancer */
	private ILoadBalancer $loadBalancer;

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
		parent::__construct();

		$this->loadBalancer = $loadBalancer;
		$this->cpdDiagramPageUtil = $cpdDiagramPageUtil;
		$this->lookup = $lookup;
	}

	/**
	 * @return Schema
	 */
	public function getSchema() {
		return new Schema();
	}

	/**
	 * @param array $params
	 *
	 * @return PrimaryDataProvider
	 */
	protected function makePrimaryDataProvider( $params ) {
		return new PrimaryDataProvider( $this->loadBalancer );
	}

	/**
	 * @return null
	 */
	protected function makeSecondaryDataProvider() {
		return new SecondaryDataProvider( $this->cpdDiagramPageUtil, $this->lookup );
	}
}
