<?php

namespace CognitiveProcessDesigner\Action;

use EditAction;
use MediaWiki\MediaWikiServices;

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
		$diagramPageUtil = MediaWikiServices::getInstance()->getService( 'CpdDiagramPageUtil' );

		$this->useTransactionalTimeLimit();

		$title = $this->getTitle();

		$outputPage = $this->getOutput();
		$outputPage->setRobotPolicy( 'noindex,nofollow' );
		$outputPage->addBacklinkSubtitle( $this->getTitle() );

		$headlineMsg = 'cpd-editor-title';
		if ( empty( $this->getWikiPage()->getContent()->getText() ) ) {
			$headlineMsg .= '-create';
		}

		$outputPage->setPageTitle(
			$this->getContext()->msg( $headlineMsg )->params( $title->getText() )->text()
		);

		$diagramPageUtil->setJsConfigVars( $outputPage, $title->getDBkey() );
		$outputPage->addModules( [ 'ext.cognitiveProcessDesigner.modeler' ] );
	}
}
