<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use Wikimedia\Rdbms\ILoadBalancer;

class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param CpdDiagramPageUtil $cpdDiagramPageUtil
	 */
	public function __construct(
		private readonly ILoadBalancer $loadBalancer,
		private readonly CpdDiagramPageUtil $cpdDiagramPageUtil
	) {
		parent::__construct();
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
		return new SecondaryDataProvider( $this->cpdDiagramPageUtil );
	}
}
