<?php

namespace CognitiveProcessDesigner\Data\Processes;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MWStake\MediaWiki\Component\DataStore\ISecondaryDataProvider;

class SecondaryDataProvider implements ISecondaryDataProvider {

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $util;

	/**
	 * @param CpdDiagramPageUtil $util
	 */
	public function __construct( CpdDiagramPageUtil $util ) {
		$this->util = $util;
	}

	/**
	 * @param Record[] $dataSets
	 *
	 * @return Record[]
	 */
	public function extend( $dataSets ) {
		foreach ( $dataSets as $dataSet ) {
			$process = $dataSet->get( Record::TITLE );
			$diagramPage = $this->util->getDiagramPage( $process );
			$svgFile = $this->util->getSvgFile( $process );

			$dataSet->set( Record::URL, $diagramPage->getTitle()->getLocalURL() );
			$dataSet->set( Record::EDIT_URL, $diagramPage->getTitle()->getEditURL() );
			$dataSet->set(
				Record::IMAGE_URL,
				$svgFile ? $svgFile->getUrl() : ""
			);
			$dataSet->set(
				Record::USED_IN_URLS,
				$this->util->getDiagramUsageLinks( $process )
			);
		}

		return $dataSets;
	}
}
