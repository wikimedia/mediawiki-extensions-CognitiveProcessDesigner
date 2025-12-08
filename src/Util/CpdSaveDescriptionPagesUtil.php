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
use MediaWiki\Title\Title;
use MediaWiki\User\User;

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
					$this->moveDescriptionPage( $descriptionPage, $oldDescriptionPage, $user );
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
			$this->createDescriptionPage( $descriptionPage, $element->getType(), $user );
		} catch ( CpdSaveException $e ) {
			$warnings[] = $e->getMessage();
		}

		return $warnings;
	}

	/**
	 * @param Title $newDescriptionPageTitle
	 * @param Title $oldDescriptionPageTitle
	 * @param User $user
	 *
	 * @return void
	 * @throws CpdSaveException
	 */
	private function moveDescriptionPage(
		Title $newDescriptionPageTitle,
		Title $oldDescriptionPageTitle,
		User $user
	): void {
		if ( $oldDescriptionPageTitle->equals( $newDescriptionPageTitle ) ) {
			throw new CpdSaveException(
				Message::newFromKey( 'cpd-api-move-equal-description-pages-error-message' )
			);
		}

		try {
			$this->movePageFactory->newMovePage( $oldDescriptionPageTitle, $newDescriptionPageTitle )->move(
				$user,
				Message::newFromKey( 'cpd-api-move-description-page-comment' )->escaped()
			);
		} catch ( Exception $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}
	}

	/**
	 * @param Title $descriptionPageTitle
	 * @param string $type
	 * @param User $user
	 *
	 * @return void
	 * @throws CpdSaveException
	 */
	private function createDescriptionPage( Title $descriptionPageTitle, string $type, User $user ): void {
		$descriptionPage = $this->wikiPageFactory->newFromTitle( $descriptionPageTitle );
		$updater = $descriptionPage->newPageUpdater( $user );
		$updater->setContent(
			SlotRecord::MAIN,
			$this->descriptionPageUtil->generateContentByType( $type )
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
		$descriptionPage = $element->getDescriptionPage();

		if ( !$descriptionPage ) {
			$reason = $element->getInvalidDescriptionPageWarning();
			if ( !$reason ) {
				$reason = 'unknown';
			}

			throw new CpdSaveException(
				Message::newFromKey( "cpd-description-page-has-no-property-warning", $element->getLabel(), $reason )
					->escaped()
			);
		}

		try {
			$this->diagramPageUtil->validateNamespace( $descriptionPage );
		} catch ( CpdInvalidNamespaceException $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}

		foreach ( $elements as $compareWith ) {
			if ( $element->getId() === $compareWith->getId() ) {
				continue;
			}

			if ( !$compareWith->getDescriptionPage() ) {
				continue;
			}

			if ( $descriptionPage->equals( $compareWith->getDescriptionPage() ) ) {
				throw new CpdSaveException(
					Message::newFromKey(
						"cpd-save-description-page-duplicate-warning",
						$element->getDescriptionPage()->getPrefixedDBkey()
					)->escaped()
				);
			}
		}
	}
}
