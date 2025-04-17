<?php

namespace CognitiveProcessDesigner\Action;

use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use EditAction;
use MediaWiki\Html\TemplateParser;
use MediaWiki\MediaWikiServices;

class EditDiagramAction extends EditAction {

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'edit';
	}

	/**
	 * @return void
	 */
	public function show(): void {
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
			$diagramPage = $this->getWikiPage();
			if ( !$diagramPage->exists() ) {
				throw new CpdInvalidContentException( 'Process page does not exist' );
			}
			$diagramPageUtil->validateContent( $diagramPage->getContent() );
		} catch ( CpdInvalidContentException $e ) {
			$headlineMsg .= '-create';
		}

		$outputPage->setPageTitle(
			$this->getContext()->msg( $headlineMsg )->params( $title->getText() )->text()
		);

		$diagramPageUtil->addOutputDependencies( $process, $outputPage, true );

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
