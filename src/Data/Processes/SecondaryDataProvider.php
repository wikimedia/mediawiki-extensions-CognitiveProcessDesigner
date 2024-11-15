<?php

namespace CognitiveProcessDesigner\Data\Processes;

use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
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
		$process = $dataSet->get( Record::PROCESS );
		$diagramPage = $this->util->getDiagramPage( $process );
		$svgFile = $this->util->getSvgFile( $process );

		$dataSet->set( Record::DB_KEY, $diagramPage->getTitle()->getPrefixedDBkey() );
		$dataSet->set( Record::URL, $diagramPage->getTitle()->getLocalURL() );
		$dataSet->set( Record::EDIT_URL, $diagramPage->getTitle()->getEditURL() );

		$isNew = false;
		// Check if the content is invalid or if there is no content. Then the page is new.
		try {
			$this->util->validateContent( $diagramPage );
		} catch ( CpdInvalidContentException $e ) {
			$isNew = true;
		}
		$dataSet->set( Record::IS_NEW, $isNew );

		if ( $svgFile ) {
			$dataSet->set( Record::IMAGE_URL, $svgFile->getUrl() );
		}
	}
}
