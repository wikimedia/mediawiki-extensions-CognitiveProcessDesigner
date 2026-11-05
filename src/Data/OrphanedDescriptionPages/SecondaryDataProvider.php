<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\DataStore\IRecord;

class SecondaryDataProvider extends \MWStake\MediaWiki\Component\DataStore\SecondaryDataProvider {

	/**
	 * @param CpdDiagramPageUtil $cpdDiagramPageUtil
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly CpdDiagramPageUtil $cpdDiagramPageUtil,
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @param Record[] $dataSets
	 *
	 * @return IRecord[]
	 */
	public function extend( $dataSets ): array {
		$dataSets = $this->filterUnstableRevisions( $dataSets );

		return parent::extend( $dataSets );
	}

	/**
	 * @param Record[] &$dataSet
	 *
	 * @return Record[]
	 */
	protected function doExtend( &$dataSet ) {
		/** @var Record $dataSet */
		$dbKey = $dataSet->get( Record::TITLE );
		$process = $dataSet->get( Record::PROCESS );

		$processPage = $this->titleFactory->makeTitle( NS_PROCESS, $process );

		$title = $this->titleFactory->newFromDBkey( $dbKey );
		$dataSet->set( Record::TITLE, $title->getSubpageText() );
		$dataSet->set( Record::TITLE_URL, $title->getFullURL() );
		$dataSet->set( Record::PROCESS_URL, $processPage->getFullURL() );

		return $dataSet;
	}

	/**
	 * @param array $dataSets
	 *
	 * @return IRecord[]
	 * @throws CpdInvalidArgumentException
	 */
	private function filterUnstableRevisions( array $dataSets ): array {
		$filteredDataSets = [];
		foreach ( $dataSets as $dataSet ) {
			$process = $dataSet->get( Record::PROCESS );
			$stableRevision = $this->cpdDiagramPageUtil->getStableRevision( $process );

			// Content stabilization is not enabled
			if ( !$stableRevision ) {
				$filteredDataSets[] = $dataSet;
				continue;
			}

			$revId = (int)$dataSet->get( Record::PROCESS_REV );

			/** @var Record $dataSet */
			if ( $revId === $stableRevision->getId() ) {
				$filteredDataSets[] = $dataSet;
			}
		}

		return $filteredDataSets;
	}
}
