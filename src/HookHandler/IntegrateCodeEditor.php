<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use Title;

class IntegrateCodeEditor {

	/**
	 * @param Title $title
	 * @param string &$languageCode
	 * @return bool
	 */
	public static function onCodeEditorGetPageLanguage( Title $title, &$languageCode ) {
		$currentContentModel = $title->getContentModel();
		if ( $currentContentModel === CognitiveProcessDesignerContent::MODEL ) {
			$languageCode = 'xml';
			return false;
		}

		return true;
	}
}
