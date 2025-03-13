<?php

namespace CognitiveProcessDesigner\Integration\PDFCreator\StylesheetsProvider;

use MediaWiki\Extension\PDFCreator\IStylesheetsProvider;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;

class CssStyles implements IStylesheetsProvider {

	/**
	 * @inheritDoc
	 */
	public function execute( string $module, ExportContext $context ): array {
		$base = dirname( __DIR__, 4 ) . '/resources/styles';

		return [
			'cpd-export.css' => "$base/cpd.export.css"
		];
	}
}
