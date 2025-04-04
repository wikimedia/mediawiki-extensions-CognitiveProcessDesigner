<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use Exception;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Message\Message;
use MediaWiki\Page\MovePageFactory;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\SlotRecord;
use User;

class CpdSaveDescriptionPagesUtil {

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param LinkRenderer $linkRenderer
	 * @param WikiPageFactory $wikiPageFactory
	 * @param MovePageFactory $movePageFactory
	 */
	public function __construct(
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly LinkRenderer $linkRenderer,
		private readonly WikiPageFactory $wikiPageFactory,
		private readonly MovePageFactory $movePageFactory,
	) {
	}

	/**
	 * @param User $user
	 * @param CpdElement[] $elements
	 *
	 * @return array
	 */
	public function saveDescriptionPages( User $user, array $elements ): array {
		$warnings = [];

		if ( empty( $elements ) ) {
			$warnings[] = Message::newFromKey( "cpd-save-description-page-empty-set-warning" )->escaped();

			return $warnings;
		}

		foreach ( $elements as $element ) {
			try {
				$this->validateElement( $element, $elements );
			} catch ( CpdSaveException $e ) {
				$warnings[] = $e->getMessage();

				continue;
			}

			$warnings = array_merge(
				$warnings,
				$this->processPage( $element, $user )
			);
		}

		return $warnings;
	}

	/**
	 * Move or create description page
	 * for the given element
	 *
	 * @param CpdElement $element
	 * @param User $user
	 *
	 * @return array
	 */
	private function processPage( CpdElement $element, User $user ): array {
		$warnings = [];

		$descriptionPage = $element->getDescriptionPage();
		$oldDescriptionPage = $element->getOldDescriptionPage();

		if ( $oldDescriptionPage ) {
			// If the old description page does not exist, add a warning and create the new description page
			if ( !$oldDescriptionPage->exists() ) {
				$warnings[] = Message::newFromKey(
					'cpd-description-page-does-not-exist-anymore-warning',
					$this->linkRenderer->makeLink(
						$oldDescriptionPage,
						$oldDescriptionPage->getSubpageText()
					)
				)->text();
			} else {
				try {
					$this->moveDescriptionPage( $element, $user );
				} catch ( CpdSaveException $e ) {
					$warnings[] = $e->getMessage();
				}

				return $warnings;
			}
		}

		// If the description page already exists do nothing
		if ( $descriptionPage->exists() ) {
			return $warnings;
		}

		try {
			$this->createDescriptionPage( $element, $user );
		} catch ( CpdSaveException $e ) {
			$warnings[] = $e->getMessage();
		}

		return $warnings;
	}

	/**
	 * @param CpdElement $element
	 * @param User $user
	 *
	 * @return void
	 * @throws CpdSaveException
	 */
	private function moveDescriptionPage(
		CpdElement $element,
		User $user
	): void {
		$oldDescriptionPageTitle = $element->getOldDescriptionPage();
		$newDescriptionPageTitle = $element->getDescriptionPage();
		if ( !$oldDescriptionPageTitle || !$newDescriptionPageTitle ) {
			throw new CpdSaveException(
				Message::newFromKey( 'cpd-description-page-has-no-property-warning', $element->getId() )
			);
		}

		if ( $element->getOldDescriptionPage()->equals( $element->getDescriptionPage() ) ) {
			throw new CpdSaveException(
				Message::newFromKey( 'cpd-api-move-equal-description-pages-error-message' )
			);
		}

		try {
			$this->movePageFactory->newMovePage( $oldDescriptionPageTitle, $newDescriptionPageTitle )->move(
				$user,
				Message::newFromKey( 'cpd-api-move-description-page-comment' )->escaped(),
				false
			);
		} catch ( Exception $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}
	}

	/**
	 * @param CpdElement $element
	 * @param User $user
	 *
	 * @return void
	 * @throws CpdSaveException
	 */
	private function createDescriptionPage( CpdElement $element, User $user ): void {
		$descriptionPageTitle = $element->getDescriptionPage();
		if ( !$descriptionPageTitle ) {
			throw new CpdSaveException(
				Message::newFromKey( 'cpd-description-page-has-no-property-warning', $element->getId() )
			);
		}

		$descriptionPage = $this->wikiPageFactory->newFromTitle( $descriptionPageTitle );
		$updater = $descriptionPage->newPageUpdater( $user );
		$updater->setContent(
			SlotRecord::MAIN,
			$this->descriptionPageUtil->generateContentByType( $element->getType() )
		);
		$comment = Message::newFromKey( 'cpd-api-save-description-page-comment' );
		$commentStore = CommentStoreComment::newUnsavedComment( $comment );
		$updater->saveRevision( $commentStore, EDIT_NEW );

		if ( !$updater->wasSuccessful() ) {
			throw new CpdSaveException( "Failed to save description page {$updater->getPage()->getDBkey()}" );
		}
	}

	/**
	 * Check for required description pages
	 * and duplicate description pages
	 *
	 * @param CpdElement $element
	 * @param CpdElement[] $elements
	 *
	 * @throws CpdSaveException
	 */
	private function validateElement( CpdElement $element, array $elements ): void {
		if ( !$element->getDescriptionPage() ) {
			throw new CpdSaveException(
				Message::newFromKey( "cpd-description-page-has-no-property-warning", $element->getId() )->escaped()
			);
		}

		try {
			$this->diagramPageUtil->validateNamespace( $element->getDescriptionPage() );
		} catch ( CpdInvalidNamespaceException $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}

		foreach ( $elements as $compareWith ) {
			if ( $element->getId() === $compareWith->getId() ) {
				continue;
			}

			if ( $element->getDescriptionPage()->equals( $compareWith->getDescriptionPage() ) ) {
				throw new CpdSaveException(
					"cpd-save-description-page-duplicate-warning", $element->getDescriptionPage()->getPrefixedDBkey()
				);
			}
		}
	}
}
