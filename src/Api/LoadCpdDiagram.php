<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\RevisionLookup\IRevisionLookup;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Content\TextContent;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use Wikimedia\ParamValidator\ParamValidator;

class LoadCpdDiagram extends ApiBase {

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdXmlProcessor $xmlProcessor
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param IRevisionLookup $lookup
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly CpdXmlProcessor $xmlProcessor,
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly IRevisionLookup $lookup
	) {
		parent::__construct( $main, $action );
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 * @throws \Exception
	 */
	public function execute() {
		$result = $this->getResult();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$revisionId = $params['revisionId'];
		$revision = null;
		$warnings = [];

		try {
			if ( $revisionId ) {
				$revision = $this->lookup->getRevisionById( $revisionId );
				$content = $revision->getContent( 'main' );

				if ( !$content ) {
					throw new CpdInvalidContentException( 'Process page does not exist' );
				}
			} else {
				$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );

				if ( !$diagramPage->exists() ) {
					throw new CpdInvalidContentException( 'Process page does not exist' );
				}

				$content = $diagramPage->getContent();
			}

			$svgFile = $this->diagramPageUtil->getSvgFile( $process, $revision );
			if ( !$svgFile ) {
				$svgFilePage = $this->diagramPageUtil->getSvgFilePage( $process );
				$warnings[] = Message::newFromKey( 'cpd-error-message-missing-svg-file', $svgFilePage->getText() );
				$result->addValue( null, 'svgFile', null );
			} else {
				$result->addValue( null, 'svgFile', $svgFile->getUrl() );
			}

			$this->diagramPageUtil->validateContent( $content );
			$xml = ( $content instanceof TextContent ) ? $content->getText() : '';

			$elements = $this->xmlProcessor->makeElementsData( $process, $xml );

			$result->addValue( null, 'exists', 1 );
			$result->addValue( null, 'xml', $xml );
			$result->addValue(
				null,
				'elements',
				$elements
			);
			$result->addValue(
				null,
				'descriptionPages',
				array_map( fn( Title $page ) => $page->getPrefixedDBkey(),
					$this->descriptionPageUtil->findDescriptionPages( $process ) )
			);

			$result->addValue( null, 'loadWarnings', $warnings );
		} catch ( CpdInvalidContentException $e ) {
			$result->addValue( null, 'exists', 0 );
			$result->addValue( null, 'xml', null );
			$result->addValue( null, 'elements', [] );
			$result->addValue( null, 'descriptionPages', [] );
			$result->addValue( null, 'svgFile', null );
			$result->addValue( null, 'loadWarnings', [] );
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
