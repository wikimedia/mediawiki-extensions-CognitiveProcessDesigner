<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use ManualLogEntry;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;

class OnPageDeleteComplete implements PageDeleteCompleteHook {

	/** @var CpdDescriptionPageUtil */
	private CpdDescriptionPageUtil $descriptionPageUtil;

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 */
	public function __construct( CpdDescriptionPageUtil $descriptionPageUtil ) {
		$this->descriptionPageUtil = $descriptionPageUtil;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageDeleteComplete(
		ProperPageIdentity $page,
		Authority $deleter,
		string $reason,
		int $pageID,
		RevisionRecord $deletedRev,
		ManualLogEntry $logEntry,
		int $archivedRevisionCount
	) {
		try {
			$process = CpdDiagramPageUtil::getProcessFromTitle(
				Title::newFromText( $page->getDBkey(), $page->getNamespace() )
			);
			$this->descriptionPageUtil->updateOrphanedDescriptionPages( [], $process );
		} catch ( CpdInvalidNamespaceException $e ) {
			// Do nothing
		}
	}
}
