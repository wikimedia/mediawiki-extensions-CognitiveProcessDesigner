<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Extension\ContentStabilization\StabilizationLookup;
use MediaWiki\Title\Title;
use MWStake\MediaWiki\Component\DataStore\IRecord;

class SecondaryDataProvider extends \MWStake\MediaWiki\Component\DataStore\SecondaryDataProvider {
	/** @var StabilizationLookup */
	private StabilizationLookup $lookup;

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $cpdDiagramPageUtil;

	/**
	 * @param CpdDiagramPageUtil $cpdDiagramPageUtil
	 * @param StabilizationLookup $lookup
	 */
	public function __construct( CpdDiagramPageUtil $cpdDiagramPageUtil, StabilizationLookup $lookup ) {
		$this->cpdDiagramPageUtil = $cpdDiagramPageUtil;
		$this->lookup = $lookup;
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

		$processPage = Title::newFromText( $process, NS_PROCESS );

		$title = Title::newFromDBkey( $dbKey );
		$dataSet->set( Record::TITLE, $title->getSubpageText() );
		$dataSet->set( Record::TITLE_URL, $title->getFullURL() );
		$dataSet->set( Record::PROCESS_URL, $processPage->getFullURL() );

		return $dataSet;
	}

	/**
	 * @param array $dataSets
	 *
	 * @return IRecord[]
	 */
	private function filterUnstableRevisions( array $dataSets ): array {
		$filteredDataSets = [];
		foreach ( $dataSets as $key => $dataSet ) {
			$process = $dataSet->get( Record::PROCESS );
			$page = $this->cpdDiagramPageUtil->getDiagramPage( $process );

			$stableRevision = $this->lookup->getLastStablePoint( $page );

			// Content stabilization is not enabled
			if ( !$stableRevision ) {
				continue;
			}

			$revId = (int)$dataSet->get( Record::PROCESS_REV );
			$id = $stableRevision->getRevision()->getId();

			/** @var Record $dataSet */
			if ( $revId === $id ) {
				$filteredDataSets[] = $dataSet;
			}
		}

		return $filteredDataSets;
	}
}
