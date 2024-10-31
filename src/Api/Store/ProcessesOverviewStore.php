<?php

namespace CognitiveProcessDesigner\Api\Store;

use ApiBase;
use ApiMain;
use ApiUsageException;
use CognitiveProcessDesigner\Data\Processes\Store;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Api class for
 * <mediawiki>/api.php?action=cpd-process-overview-store
 */
class ProcessesOverviewStore extends ApiBase {

	/** @var Store */
	private Store $store;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param ILoadBalancer $loadBalancer
	 * @param CpdDiagramPageUtil $util
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		ILoadBalancer $loadBalancer,
		CpdDiagramPageUtil $util,
	) {
		parent::__construct( $main, $action );
		$this->store = new Store( $loadBalancer, $util );
	}

	/**
	 * @return void
	 * @throws ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$params = new ReaderParams( $params );
		$res = $this->store->getReader()->read( $params );
		$result = $this->getResult();
		$result->addValue( null, 'results', json_encode( $res->getRecords() ) );
	}

	/**
	 * @return string[]
	 */
	protected function getRequiredPermissions() {
		return [ 'read' ];
	}
}
