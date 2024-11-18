<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

class SecondaryDataProvider extends \MWStake\MediaWiki\Component\DataStore\SecondaryDataProvider {

	/**
	 * @param Record[] &$dataSet
	 *
	 * @return Record[]
	 */
	protected function doExtend( &$dataSet ) {
		return $dataSet;
	}
}
