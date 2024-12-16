<?php

namespace CognitiveProcessDesigner\Action;

use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use EditAction;
use MediaWiki\MediaWikiServices;
use TemplateParser;

class EditDiagramAction extends EditAction {

	/**
	 * @return string
	 */
	public function getName() {
		return 'edit';
	}

	/**
	 * @return void
	 */
	public function show() {
		$services = MediaWikiServices::getInstance();
		/** @var CpdDiagramPageUtil $diagramPageUtil */
		$diagramPageUtil = $services->getService( 'CpdDiagramPageUtil' );
		$canvasHeight = $services->getService( 'MainConfig' )->get( 'CPDCanvasProcessHeight' );

		$this->useTransactionalTimeLimit();

		$title = $this->getTitle();
		$process = $title->getDBkey();

		$outputPage = $this->getOutput();
		$outputPage->setRobotPolicy( 'noindex,nofollow' );
		$outputPage->addBacklinkSubtitle( $this->getTitle() );

		$headlineMsg = 'cpd-editor-title';

		try {
			$diagramPageUtil->validateContent( $this->getWikiPage() );
		} catch ( CpdInvalidContentException $e ) {
			$headlineMsg .= '-create';
		}

		$outputPage->setPageTitle(
			$this->getContext()->msg( $headlineMsg )->params( $title->getText() )->text()
		);

		$diagramPageUtil->setJsConfigVars( $outputPage, $process );
		$outputPage->addModules( [ 'ext.cpd.modeler' ] );

		$templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);
		$outputPage->addHTML( $templateParser->processTemplate(
			'CpdContainer', [
				'process' => $process,
				'showToolbar' => true,
				'width' => '100%',
				'height' => $canvasHeight . 'px'
			]
		) );
	}
}
