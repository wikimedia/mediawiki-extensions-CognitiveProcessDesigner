<?php

namespace CognitiveProcessDesigner;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;

class Setup {
	public static function callback() {
		mwsInitComponents();

		if ( !defined( 'CONTENT_SLOT_CPD_PROCESS_META' ) ) {
			define( 'CONTENT_SLOT_CPD_PROCESS_META', 'cpd-process-meta' );
		}

		/**
		 * ERM40817
		 * Enable CPD content model in visual editor for history version diff
		 */
		if ( isset( $GLOBALS['wgVisualEditorAvailableContentModels'] ) ) {
			$GLOBALS['wgVisualEditorAvailableContentModels'][CognitiveProcessDesignerContent::MODEL] = 'article';
		}
	}
}
