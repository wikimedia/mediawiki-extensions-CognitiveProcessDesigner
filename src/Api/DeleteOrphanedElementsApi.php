<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use CommentStoreComment;
use ContentHandler;
use Message;
use MWException;
use RecentChange;
use RuntimeException;
use Title;
use WikiPage;

class DeleteOrphanedElementsApi extends ApiBase {

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'elements' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'cpd-api-delete-orphaned-elements-param-elements'
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
			foreach ( $elements as $element ) {
				$title = Title::makeTitle( NS_MAIN, $element['title'] );

				$wikipage = WikiPage::factory( $title );

				$content = ContentHandler::makeContent( '[[Category:Delete]]', $title );

				$comment = Message::newFromKey( 'cpd-api-delete-orphaned-elements-update-comment' )->text();

				try {
					$status = $wikipage->doEditContent( $content, $comment, EDIT_UPDATE );
				} catch ( MWException $e ) {
					$errors[$element['title']] = $e->getMessage();

					continue;
				}

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
