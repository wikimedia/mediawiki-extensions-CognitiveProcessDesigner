<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Extension\ContentStabilization\Hook\Interfaces\ContentStabilizationGetCurrentInclusionsHook;
use MediaWiki\Page\PageIdentity;

class ExcludeDiagramImagesFromStabilization implements ContentStabilizationGetCurrentInclusionsHook {

	/**
	 * Exclude diagram images from stabilization
	 * Stabilization is dependent on the process, not the diagram image
	 *
	 * All diagram images ending on .cpd.svg by convention, defined in CpdDiagramPageUtil::CPD_SVG_FILE_EXTENSION
	 *
	 * @inheritDoc
	 */
	public function onContentStabilizationGetCurrentInclusions( PageIdentity $page, array &$res ): void {
		foreach ( $res['images'] as $key => $image ) {
			if ( str_ends_with( $image['name'], CpdDiagramPageUtil::CPD_SVG_FILE_EXTENSION ) ) {
				unset( $res['images'][$key] );
			}
		}
	}
}
