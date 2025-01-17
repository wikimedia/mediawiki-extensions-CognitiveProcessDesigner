<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use MediaWiki\Title\Title;

class SecondaryDataProvider extends \MWStake\MediaWiki\Component\DataStore\SecondaryDataProvider {

	/**
	 * @param Record[] &$dataSet
	 *
	 * @return Record[]
	 */
	protected function doExtend( &$dataSet ) {
		/** @var Record $dataSet */
		$dbKey = $dataSet->get( Record::TITLE );
		$process = $dataSet->get( Record::PROCESS );

		$processPage = Title::newFromText( $process, NS_PROCESS );

		$title = Title::newFromDBkey( $dbKey );
		$dataSet->set( Record::TITLE, $title->getSubpageText() );
		$dataSet->set( Record::TITLE_URL, $title->getFullURL() );
		$dataSet->set( Record::PROCESS_URL, $processPage->getFullURL() );

		return $dataSet;
	}
}
