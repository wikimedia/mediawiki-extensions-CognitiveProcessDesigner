<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use Wikimedia\Rdbms\ILoadBalancer;

class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {

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
		CpdDiagramPageUtil $util,
	) {
		parent::__construct();
		$this->loadBalancer = $loadBalancer;
		$this->util = $util;
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
		return new SecondaryDataProvider( $this->util );
	}
}
