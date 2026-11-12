<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use MediaWiki\Output\OutputPage;
use MediaWiki\Skin\Skin;

class DisableVisualEditor {

	/**
	 * VE is only allowed for the CPD content model to render diffs. It must not take over editing.
	 *
	 * @param OutputPage $output
	 * @param Skin $skin
	 * @return bool
	 */
	public function onVisualEditorBeforeEditor( OutputPage $output, Skin $skin ): bool {
		$title = $output->getTitle();
		if ( $title && $title->getContentModel() === CognitiveProcessDesignerContent::MODEL ) {
			return false;
		}

		return true;
	}
}
