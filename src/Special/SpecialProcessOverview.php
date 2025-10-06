<?php

namespace CognitiveProcessDesigner\Special;

use CognitiveProcessDesigner\Util\CpdProcessUtil;
use MediaWiki\Html\TemplateParser;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\SpecialPage\SpecialPage;
use PermissionsError;

class SpecialProcessOverview extends SpecialPage {

	/** @var TemplateParser */
	private TemplateParser $templateParser;

	public function __construct( private readonly CpdProcessUtil $processUtil ) {
		parent::__construct( 'ProcessOverview' );

		$this->templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);
	}

	/**
	 * @param string $subPage
	 *
	 * @return void
	 * @throws PermissionsError
	 */
	public function execute( $subPage ) {
		$this->processUtil->throwPermissionErrors( $this->getUser() );

		parent::execute( $subPage );

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'processoverview' )->text() );
		$out->addModules( "ext.cpd.special.processoverview" );
		$modules = ExtensionRegistry::getInstance()->getAttribute(
			'CognitiveProcessDesignerProcessOverviewPluginModules'
		);
		$out->addModules( $modules );

		$html = $this->templateParser->processTemplate(
			'SpecialProcessOverview', [
				'loading-text' => $this->msg( 'bs-cpd-process-overview-loading-text' )->escaped()
			]
		);

		$out->addHTML( $html );
	}
}
