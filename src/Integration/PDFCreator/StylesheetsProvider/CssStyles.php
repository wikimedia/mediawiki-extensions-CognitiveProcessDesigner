<?php

namespace CognitiveProcessDesigner\Integration\PDFCreator\StylesheetsProvider;

use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use MediaWiki\Extension\PDFCreator\IStylesheetsProvider;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Title\Title;

class CssStyles implements IStylesheetsProvider {

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 */
	public function __construct( private readonly CpdDescriptionPageUtil $descriptionPageUtil ) {
	}

	/**
	 * @inheritDoc
	 */
	public function execute( string $module, ExportContext $context ): array {
		$base = dirname( __DIR__, 4 ) . '/resources/styles';

		$title = Title::newFromPageIdentity( $context->getPageIdentity() );
		if (
			!$this->descriptionPageUtil->isDescriptionPage( $title ) && $title->getNamespace() !== NS_PROCESS
		) {
			return [
				'cpd-export.css' => "$base/cpd.export.css"
			];
		}

		return [
			'cpd-export.css' => "$base/cpd.export.css",
			'cpd-navigation.css' => "$base/cpd.navigation.connections.css",
			'cpd-description-page.css' => "$base/cpd.description.page.css"
		];
	}
}
