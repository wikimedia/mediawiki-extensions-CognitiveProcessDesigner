<?php

namespace CognitiveProcessDesigner\UsageTracker;

use BS\UsageTracker\CollectorResult;
use BS\UsageTracker\Collectors\Base as UsageTrackerBase;
use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;

class NumberOfProcesses extends UsageTrackerBase {

	public const IDENTIFIER = 'cpd-number-of-processes';

	/**
	 * @return string
	 */
	public function getDescription(): string {
		return 'Number of processes';
	}

	/**
	 * @return string
	 */
	public function getIdentifier(): string {
		return self::IDENTIFIER;
	}

	/**
	 * @return CollectorResult
	 */
	public function getUsageData(): CollectorResult {
		$res = new CollectorResult( $this );

		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$row = $db->newSelectQueryBuilder()
			->select( 'COUNT( distinct page_id ) as count' )
			->from( 'page' )
			->where( [
				'page_content_model' => CognitiveProcessDesignerContent::MODEL,
				'page_is_redirect' => 0,
			] )
			->caller( __METHOD__ )
			->fetchRow();

		$res->count = $row ? $row->count : 0;
		return $res;
	}
}
