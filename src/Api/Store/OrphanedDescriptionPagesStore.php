<?php

namespace CognitiveProcessDesigner\Api\Store;

use CognitiveProcessDesigner\Data\OrphanedDescriptionPages\Store;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Api class for
 * <mediawiki>/api.php?action=cpd-process-overview-store
 */
class OrphanedDescriptionPagesStore extends ApiBase {

	/** @var Store */
	private Store $store;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param ILoadBalancer $loadBalancer
	 * @param CpdDiagramPageUtil $cpdDiagramPageUtil
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		ILoadBalancer $loadBalancer,
		CpdDiagramPageUtil $cpdDiagramPageUtil
	) {
		parent::__construct( $main, $action );
		$this->store = new Store( $loadBalancer, $cpdDiagramPageUtil );
	}

	/**
	 * @return void
	 * @throws ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$readerParams = new ReaderParams( [
			'start' => $this->getStart( $params ),
			'limit' => $this->getLimit( $params ),
			'filter' => $this->getFilter( $params ),
			'sort' => $this->getSort( $params )
		] );

		$res = $this->store->getReader()->read( $readerParams );
		$records = $res->getRecords();
		$result = $this->getResult();

		$result->addValue(
			null,
			'results',
			array_map( fn ( $record ) => $record->getData(), $records )
		);

		$result->addValue(
			null,
			'total',
			$res->getTotal()
		);
	}

	/**
	 * @param array $params
	 *
	 * @return int
	 */
	private function getStart( array $params ): int {
		return (int)$params['start'];
	}

	/**
	 * @param array $params
	 *
	 * @return int
	 */
	private function getLimit( array $params ): int {
		return (int)$params['limit'];
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	private function getFilter( array $params ): array {
		if ( isset( $params['filter'] ) ) {
			return json_decode( $params['filter'], 1 );
		}

		return [];
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	private function getSort( array $params ): array {
		if ( isset( $params['sort'] ) ) {
			return json_decode( $params['sort'], 1 );
		}

		return [];
	}

	/**
	 * @return string[]
	 */
	protected function getRequiredPermissions() {
		return [ 'read' ];
	}

	/**
	 * Called by ApiMain
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return parent::getAllowedParams() + [
				'sort' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => '[]'
				],
				'filter' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => '[]'
				],
				'limit' => [
					ParamValidator::PARAM_TYPE => 'integer',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => 25
				],
				'start' => [
					ParamValidator::PARAM_TYPE => 'integer',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => 0
				],
				'query' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false
				]
			];
	}
}
