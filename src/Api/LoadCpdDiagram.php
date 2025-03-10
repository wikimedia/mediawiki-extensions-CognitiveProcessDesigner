<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\RevisionLookup\IRevisionLookup;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use Exception;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Message\Message;
use Wikimedia\ParamValidator\ParamValidator;

class LoadCpdDiagram extends ApiBase {

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdXmlProcessor $xmlProcessor
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly CpdXmlProcessor $xmlProcessor,
		private readonly CpdDiagramPageUtil $diagramPageUtil,
	) {
		parent::__construct( $main, $action );
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 * @throws CpdXmlProcessingException
	 * @throws CpdCreateElementException
	 */
	public function execute() {
		$result = $this->getResult();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$revisionId = $params['revisionId'];
		$revision = null;
		$warnings = [];

		try {
			$xml = $this->diagramPageUtil->getXml( $process, $revisionId );
			$cpdElements = $this->xmlProcessor->createElements( $process, $xml );

			$svgFile = $this->diagramPageUtil->getSvgFile( $process, $revision );
			if ( !$svgFile ) {
				$svgFilePage = $this->diagramPageUtil->getSvgFilePage( $process );
				$warnings[] = Message::newFromKey( 'cpd-error-message-missing-svg-file', $svgFilePage->getText() );
			}

			$result->addValue( null, 'xml', $xml );
			$result->addValue(
				null,
				'elements',
				array_map( fn( $element ) => json_encode( $element ), $cpdElements )
			);
			$result->addValue( null, 'svgFile', $svgFile?->getUrl() );
			$result->addValue( null, 'loadWarnings', $warnings );
		} catch ( CpdInvalidContentException $e ) {
			$result->addValue( null, 'xml', null );
			$result->addValue( null, 'elements', [] );
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
