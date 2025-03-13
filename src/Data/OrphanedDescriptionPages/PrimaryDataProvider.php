<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use InvalidArgumentException;
use MWStake\MediaWiki\Component\DataStore\Filter;
use MWStake\MediaWiki\Component\DataStore\Filter\StringValue;
use MWStake\MediaWiki\Component\DataStore\IPrimaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;
use stdClass;
use Wikimedia\Rdbms\ILoadBalancer;

class PrimaryDataProvider implements IPrimaryDataProvider {
	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct(
		private readonly ILoadBalancer $loadBalancer
	) {
	}

	/**
	 * @param ReaderParams $params
	 *
	 * @return Record[]
	 */
	public function makeData( $params ): array {
		$filterConds = $this->makePreFilterConds( $params->getFilter() );
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$rows = $dbr->select(
			'cpd_orphaned_description_pages',
			[
				'page_title',
				'process',
				'process_rev'
			],
			$filterConds
		);

		$records = [];
		foreach ( $rows as $row ) {
			$data = new stdClass();
			$data->process = $row->process;
			$data->process_rev = $row->process_rev;
			$data->title = $row->page_title;
			$records[] = new Record( $data );
		}

		return $records;
	}

	/**
	 * @param Filter[] $preFilters
	 *
	 * @return StringValue[]
	 */
	protected function makePreFilterConds( array $preFilters ): array {
		$conds = [];

		foreach ( $preFilters as $filter ) {
			if ( $filter instanceof StringValue ) {
				$comparison = $filter->getComparison();

				if ( $comparison !== "ct" ) {
					throw new InvalidArgumentException( "Only 'ct' comparison is supported" );
				}

				$conds[] = "{$filter->getField()} LIKE '%{$filter->getValue()}%'";
			}
		}

		return $conds;
	}
}
