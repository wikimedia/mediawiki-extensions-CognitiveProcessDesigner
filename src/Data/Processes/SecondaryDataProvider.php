<?php

namespace CognitiveProcessDesigner\Data\Processes;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MWStake\MediaWiki\Component\DataStore\IRecord;

class SecondaryDataProvider extends \MWStake\MediaWiki\Component\DataStore\SecondaryDataProvider {

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $util;

	/**
	 * @param CpdDiagramPageUtil $util
	 */
	public function __construct( CpdDiagramPageUtil $util ) {
		$this->util = $util;
	}

	/**
	 * @param IRecord &$dataSet
	 *
	 * @return void
	 */
	protected function doExtend( &$dataSet ) {
		$process = $dataSet->get( Record::TITLE );
		$diagramPage = $this->util->getDiagramPage( $process );
		$svgFile = $this->util->getSvgFile( $process );

		$dataSet->set( Record::URL, $diagramPage->getTitle()->getLocalURL() );
		$dataSet->set( Record::EDIT_URL, $diagramPage->getTitle()->getEditURL() );
		$dataSet->set(
			Record::IMAGE_URL,
			$svgFile ? $svgFile->getUrl() : ""
		);
	}
}
