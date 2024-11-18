<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use MWStake\MediaWiki\Component\DataStore\IPrimaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use stdClass;
use Wikimedia\Rdbms\ILoadBalancer;

class PrimaryDataProvider implements IPrimaryDataProvider {

	/** @var ILoadBalancer */
	private $loadBalancer;

	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct(
		ILoadBalancer $loadBalancer
	) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param ReaderParams $params
	 *
	 * @return Record[]
	 */
	public function makeData( $params ): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$rows = $dbr->select(
			'cpd_orphaned_description_pages', [
				'page_title',
				'process'
			]
		);

		$records = [];
		foreach ( $rows as $row ) {
			$data = new stdClass();
			$data->process = $row->process;
			$data->db_key = $row->page_title;
			$records[] = new Record( $data );
		}

		return $records;
	}
}
