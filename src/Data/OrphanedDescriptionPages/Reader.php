<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use Wikimedia\Rdbms\ILoadBalancer;

class Reader extends \MWStake\MediaWiki\Component\DataStore\Reader {

	/** @var ILoadBalancer */
	private ILoadBalancer $loadBalancer;

	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct(
		ILoadBalancer $loadBalancer
	) {
		parent::__construct();
		$this->loadBalancer = $loadBalancer;
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
		return new SecondaryDataProvider();
	}
}
