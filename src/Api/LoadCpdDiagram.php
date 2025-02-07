<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Content\TextContent;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use Wikimedia\ParamValidator\ParamValidator;

class LoadCpdDiagram extends ApiBase {

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param RevisionLookup $revisionLookup
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly RevisionLookup $revisionLookup
	) {
		parent::__construct( $main, $action );
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute() {
		$result = $this->getResult();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$revisionId = $params['revisionId'];

		try {
			if ( $revisionId ) {
				$revision = $this->revisionLookup->getRevisionById( $revisionId );
				$content = $revision->getContent( 'main' );

				if ( !$content ) {
					throw new CpdInvalidContentException( 'Process page does not exist' );
				}

				$svgFile = $this->diagramPageUtil->getSvgFile( $process, $revision );
			} else {
				$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );

				if ( !$diagramPage->exists() ) {
					throw new CpdInvalidContentException( 'Process page does not exist' );
				}

				$content = $diagramPage->getContent();
				$svgFile = $this->diagramPageUtil->getSvgFile( $process );
			}

			$this->diagramPageUtil->validateContent( $content );
			$text = ( $content instanceof TextContent ) ? $content->getText() : '';
			$result->addValue( null, 'exists', 1 );
			$result->addValue( null, 'xml', $text );
			$result->addValue(
				null,
				'descriptionPages',
				array_map( fn( Title $page ) => $page->getPrefixedDBkey(),
					$this->descriptionPageUtil->findDescriptionPages( $process ) )
			);

			if ( !$svgFile ) {
				throw new ApiUsageException( null, Status::newFatal( "Diagram svg file does not exist" ) );
			}

			$result->addValue( null, 'svgFile', $svgFile->getUrl() );
		} catch ( CpdInvalidContentException $e ) {
			$result->addValue( null, 'exists', 0 );
			$result->addValue( null, 'xml', null );
			$result->addValue( null, 'descriptionPages', [] );
			$result->addValue( null, 'svgFile', null );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'process' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'revisionId' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			]
		];
	}
}
