<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use CommentStoreComment;
use ContentHandler;
use Message;
use MWException;
use RuntimeException;
use TextContent;
use Title;
use WikiPage;
use WikitextContent;

class SaveDiagramElementsApi extends ApiBase {

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'elements' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
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
			foreach ( $elements as $element ) {
				$new = false;

				$title = Title::makeTitle( NS_MAIN, $element['title'] );

				$wikipage = WikiPage::factory( $title );
				$pageContent = $wikipage->getContent();
				if ( $pageContent instanceof WikitextContent ) {
					$text = $pageContent->getNativeData();

					$text = preg_replace( '/<div class="cdp-data".*?<\/div>/s', '', $text );

					$text = '<div class="cdp-data">' . $element['content'] . '</div>' . "\n" . $text;

					$content = ContentHandler::makeContent( $text, $title );
				}
				else {
					$new = true;

					$content = ContentHandler::makeContent( $element['content'], $title );
				}

				$comment = Message::newFromKey( 'cpd-api-save-diagram-elements-update-comment' )->text();

				$flag = ( $new ? EDIT_NEW : EDIT_UPDATE );
				try {
					$status = $wikipage->doEditContent( $content, $comment, $flag );
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
