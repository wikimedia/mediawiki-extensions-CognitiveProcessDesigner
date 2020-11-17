<?php
namespace CognitiveProcessDesigner\Hook\OutputPageParserOutput;

use OutputPage;
use ParserOutput;

class AddModules {

	/**
	 * @param OutputPage &$out
	 * @param ParserOutput $parserOutput
	 */
	public static function callback( OutputPage &$out, ParserOutput $parserOutput ) {
		$out->addModules( [
			'ext.cognitiveProcessDesigner.editor',
			'ext.cognitiveProcessDesignerEdit.styles'
		] );
	}
}
