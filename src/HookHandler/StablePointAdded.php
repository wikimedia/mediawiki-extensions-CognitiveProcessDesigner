<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Extension\ContentStabilization\Hook\Interfaces\ContentStabilizationStablePointAddedHook;
use MediaWiki\Extension\ContentStabilization\StablePoint;

class StablePointAdded implements ContentStabilizationStablePointAddedHook {
	/** @var CpdDescriptionPageUtil */
	private CpdDescriptionPageUtil $descriptionPageUtil;

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 */
	public function __construct( CpdDiagramPageUtil $diagramPageUtil, CpdDescriptionPageUtil $descriptionPageUtil ) {
		$this->diagramPageUtil = $diagramPageUtil;
		$this->descriptionPageUtil = $descriptionPageUtil;
	}

	/**
	 * @inheritDoc
	 */
	public function onContentStabilizationStablePointAdded( StablePoint $stablePoint ): void {
		try {
			$process = $this->diagramPageUtil->getProcess( $stablePoint->getPage() );
			$this->descriptionPageUtil->cleanUpOrphanedDescriptionPages(
				$process,
				$stablePoint->getRevision()->getId()
			);
		} catch ( CpdInvalidNamespaceException $e ) {
			return;
		}
	}
}
