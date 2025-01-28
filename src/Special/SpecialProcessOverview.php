<?php

namespace CognitiveProcessDesigner\Special;

use MediaWiki\Html\TemplateParser;
use MediaWiki\SpecialPage\SpecialPage;

class SpecialProcessOverview extends SpecialPage {
	/** @var TemplateParser */
	private TemplateParser $templateParser;

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
		parent::execute( $subPage );

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'processoverview' )->text() );
		$out->addModules( "ext.cpd.special.processoverview" );

		$html = $this->templateParser->processTemplate(
			'SpecialProcessOverview', [
				'loading-text' => $this->msg( 'bs-cpd-process-overview-loading-text' )->escaped()
			]
		);

		$out->addHTML( $html );
	}
}
