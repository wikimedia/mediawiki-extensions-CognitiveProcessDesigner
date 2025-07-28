<?php

namespace CognitiveProcessDesigner\UsageTracker;

use BS\UsageTracker\CollectorResult;
use BS\UsageTracker\Collectors\Base as UsageTrackerBase;

class NumberOfDescriptionPages extends UsageTrackerBase {

	public const IDENTIFIER = 'cpd-number-of-description-pages';

	/**
	 * @return string
	 */
	public function getDescription(): string {
		return 'Number of description pages';
	}

	/**
	 *
	 * @return string
	 */
	public function getIdentifier(): string {
		return self::IDENTIFIER;
	}

	/**
	 *
	 * @return CollectorResult
	 */
	public function getUsageData(): CollectorResult {
		$res = new CollectorResult( $this );

		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$row = $db->newSelectQueryBuilder()
			->select( 'COUNT( distinct page_id ) as count' )
			->from( 'page' )
			->where( [
				'page_namespace' => NS_PROCESS,
				'page_content_model' => 'wikitext',
				'page_is_redirect' => 0,
			] )
			->caller( __METHOD__ )
			->fetchRow();

		$res->count = $row ? $row->count : 0;
		return $res;
	}
}
