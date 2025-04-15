<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use MWStake\MediaWiki\Component\DataStore\ResultSet;
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
	 *
	 * @param ReaderParams $params
	 *
	 * @return ResultSet
	 */
	public function read( $params ) {
		$resultSet = parent::read( new ReaderParams( [
			ReaderParams::PARAM_QUERY => $params->getQuery(),
			ReaderParams::PARAM_LIMIT => ReaderParams::LIMIT_INFINITE,
			ReaderParams::PARAM_FILTER => $params->getFilter(),
			ReaderParams::PARAM_SORT => $params->getSort()
		] ) );

		$dataSets = $resultSet->getRecords();
		$total = count( $dataSets );

		return new ResultSet( $dataSets, $total );
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
