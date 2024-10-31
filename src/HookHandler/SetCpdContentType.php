<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook;

class SetCpdContentType implements ContentHandlerDefaultModelForHook {

	/**
	 * @inheritDoc
	 */
	public function onContentHandlerDefaultModelFor( $title, &$model ) {
		if ( $title->getNamespace() === NS_PROCESS && !$title->isSubpage() ) {
			$model = CognitiveProcessDesignerContent::MODEL;
		}
	}
}
