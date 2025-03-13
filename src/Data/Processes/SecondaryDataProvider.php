<?php

namespace CognitiveProcessDesigner\Data\Processes;

use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MWStake\MediaWiki\Component\DataStore\IRecord;

class SecondaryDataProvider extends \MWStake\MediaWiki\Component\DataStore\SecondaryDataProvider {

	/**
	 * @param CpdDiagramPageUtil $util
	 */
	public function __construct( private readonly CpdDiagramPageUtil $util ) {
	}

	/**
	 * @param IRecord &$dataSet
	 *
	 * @return void
	 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
	 * @throws CpdInvalidArgumentException
	 */
	protected function doExtend( &$dataSet ) {
		$process = $dataSet->get( Record::PROCESS );
		$diagramPage = $this->util->getDiagramPage( $process );
		$svgFile = $this->util->getSvgFile( $process, $this->util->getStableRevision( $process ) );

		$dataSet->set( Record::DB_KEY, $diagramPage->getTitle()->getPrefixedDBkey() );
		$dataSet->set( Record::URL, $diagramPage->getTitle()->getLocalURL() );
		$dataSet->set( Record::EDIT_URL, $diagramPage->getTitle()->getEditURL() );

		$isNew = false;
		// Check if the content is invalid or if there is no content. Then the page is new.
		try {
			if ( !$diagramPage->exists() ) {
				throw new CpdInvalidContentException( 'Process page does not exist' );
			}
			$this->util->validateContent( $diagramPage->getContent() );
		} catch ( CpdInvalidContentException $e ) {
			$isNew = true;
		}
		$dataSet->set( Record::IS_NEW, $isNew );

		if ( $svgFile && !$isNew ) {
			$dataSet->set( Record::IMAGE_URL, $svgFile->getUrl() );
		}
	}
}
