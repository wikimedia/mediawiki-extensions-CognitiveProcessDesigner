<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\TitleFactory;

class OnPageDeleteComplete implements PageDeleteCompleteHook {

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly TitleFactory $titleFactory
	) {
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
				$this->titleFactory->makeTitle( $page->getNamespace(), $page->getDBkey() )
			);
			$this->descriptionPageUtil->cleanUpOrphanedDescriptionPages( $process );
		} catch ( CpdInvalidNamespaceException $e ) {
			// Do nothing
		}
	}
}
