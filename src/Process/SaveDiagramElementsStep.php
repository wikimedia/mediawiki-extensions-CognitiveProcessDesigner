<?php

namespace CognitiveProcessDesigner\Process;

use CommentStoreComment;
use ContentHandler;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use Message;
use MWException;
use MWStake\MediaWiki\Component\ProcessManager\IProcessStep;
use RuntimeException;
use TextContent;
use Title;
use WikiPage;

class SaveDiagramElementsStep implements IProcessStep {

	/**
	 * @var array
	 */
	private $elements;

	/**
	 * @var string
	 */
	private $actorName;

	/**
	 * @param array $elements
	 * @param string $actorName
	 */
	public function __construct( array $elements, string $actorName ) {
		$this->elements = $elements;
		$this->actorName = $actorName;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data = [] ): array {
		$errors = [];
		$warnings = [];

		if ( $this->elements ) {
			$userFactory = MediaWikiServices::getInstance()->getUserFactory();
			if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
				// MW 1.36+
				$wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
			} else {
				$wikiPageFactory = null;
			}

			foreach ( $this->elements as $element ) {
				$new = false;

				$title = Title::newFromText( $element['title'] );

				if ( $wikiPageFactory !== null ) {
					// MW 1.36+
					$wikipage = $wikiPageFactory->newFromTitle( $title );
				} else {
					$wikipage = WikiPage::factory( $title );
				}

				$actor = $userFactory->newFromName( $this->actorName );
				$updater = $wikipage->newPageUpdater( $actor );
				if ( $wikipage->exists() ) {
					$parentRevision = $updater->grabParentRevision();
					$content = $parentRevision->getContent( SlotRecord::MAIN );
					if ( $content instanceof TextContent ) {
						$text = $content->getText();

						$text = preg_replace( '/<div class="cdp-data".*?<\/div>/s', '', $text );

						$text = '<div class="cdp-data">' . $element['content'] . '</div>' . "\n" . $text;

						$content = ContentHandler::makeContent( $text, $title );
					}
				} else {
					$new = true;

					$content = ContentHandler::makeContent( $element['content'], $title );
				}

				$updater->setContent( SlotRecord::MAIN, $content );

				$comment = Message::newFromKey( 'cpd-api-save-diagram-elements-update-comment' );
				$commentStore = CommentStoreComment::newUnsavedComment( $comment );

				$flag = ( $new ? EDIT_NEW : EDIT_UPDATE );
				try {
					$result = $updater->saveRevision( $commentStore, $flag );
				} catch ( MWException | RuntimeException $e ) {
					$errors[$element['title']] = $e->getMessage();

					continue;
				}

				if ( $result === null || !$updater->wasSuccessful() ) {
					$status = $updater->getStatus();

					if ( $status->getErrors() ) {
						// If status is okay but there are errors - they are not fatal, just warnings
						if ( $status->isOK() ) {
							$warnings[$element['title']] = $status->getMessage();
						} else {
							$errors[$element['title']] = $status->getMessage();
						}
					}
				}
			}
		}

		$success = true;
		if ( $errors ) {
			$success = false;
		}

		return [
			'errors' => $errors,
			'warnings' => $warnings
		];
	}
}
