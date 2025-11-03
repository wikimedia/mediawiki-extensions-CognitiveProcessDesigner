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

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 */
	public function __construct( private readonly CpdDescriptionPageUtil $descriptionPageUtil ) {
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
			$process = CpdDiagramPageUtil::getProcess(
				Title::makeTitle( $page->getNamespace(), $page->getDBkey() )
			);
			$this->descriptionPageUtil->cleanUpOrphanedDescriptionPages( $process );
		} catch ( CpdInvalidNamespaceException $e ) {
			// Do nothing
		}
	}
}
