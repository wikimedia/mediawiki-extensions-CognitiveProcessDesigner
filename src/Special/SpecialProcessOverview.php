<?php

namespace CognitiveProcessDesigner\Special;

use SpecialPage;
use TemplateParser;

class SpecialProcessOverview extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ProcessOverview' );

		$this->templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);
	}

	/**
	 *
	 * @param string $subPage
	 *
	 * @return void
	 */
	public function execute( $subPage ) {
		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'processoverview' )->text() );
		$out->addModules( "ext.cpd.special.vue" );

		$html = $this->templateParser->processTemplate(
			'cpd.vue', [
				'loading-text' => $this->msg( 'bs-cpd-process-overview-loading-text' )->escaped()
			]
		);

		$out->addHTML( $html );
	}
}
