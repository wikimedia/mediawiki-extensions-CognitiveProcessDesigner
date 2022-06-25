<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use CommentStoreComment;
use ContentHandler;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use Message;
use MWException;
use RuntimeException;
use TextContent;
use Title;
use Wikimedia\ParamValidator\ParamValidator;
use WikiPage;

class SaveDiagramElementsApi extends ApiBase {

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'elements' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'cpd-api-save-diagram-elements-param-elements'
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$elements = json_decode( $params['elements'], true );

		$errors = [];
		$warnings = [];

		if ( $elements ) {
			if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
				// MW 1.36+
				$wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
			} else {
				$wikiPageFactory = null;
			}
			foreach ( $elements as $element ) {
				$new = false;

				$title = Title::makeTitle( NS_MAIN, $element['title'] );

				if ( $wikiPageFactory !== null ) {
					// MW 1.36+
					$wikipage = $wikiPageFactory->newFromTitle( $title );
				} else {
					$wikipage = WikiPage::factory( $title );
				}

				$updater = $wikipage->newPageUpdater( $this->getContext()->getUser() );
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

		if ( $errors ) {
			$this->getResult()->addValue( null, 'errors', $errors );
		}
		if ( $warnings ) {
			$this->getResult()->addValue( null, 'warnings', $warnings );
		}

		$this->getResult()->addValue( null, 'success', $success );
	}

}
