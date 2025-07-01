<?php

namespace CognitiveProcessDesigner\Api\Store;

use CognitiveProcessDesigner\Data\Processes\Record;
use CognitiveProcessDesigner\Data\Processes\Store;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Title\Title;
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
		$records = $res->getRecords();
		$this->validateEditPermission( $records );
		$result->addValue( null, 'results', json_encode( $records ) );
	}

	/**
	 * @param Record[] &$records
	 *
	 * @return void
	 */
	private function validateEditPermission( array &$records ): void {
		$permissionManager = $this->getPermissionManager();
		$user = $this->getUser();
		foreach ( $records as &$record ) {
			$dbKey = $record->get( Record::DB_KEY );
			if ( !$permissionManager->quickUserCan( 'edit', $user, Title::newFromDBkey( $dbKey ) ) ) {
				$record->set( Record::EDIT_URL, null );
			}
		}
	}

	/**
	 * @return string[]
	 */
	protected function getRequiredPermissions() {
		return [ 'read' ];
	}
}
