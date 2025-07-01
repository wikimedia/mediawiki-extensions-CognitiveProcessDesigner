<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
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
	 *
	 * @throws ApiUsageException
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$revisionId = $params['revision'];
		$warnings = [];

		try {
			$xml = $this->diagramPageUtil->getXml( $process, $revisionId );
			if ( empty( $xml ) ) {
				throw new CpdInvalidContentException();
			}

			$svgFile = $this->diagramPageUtil->getSvgFile( $process, $revisionId );
			if ( !$svgFile ) {
				$svgFilePage = $this->diagramPageUtil->getSvgFilePage( $process );
				$warnings[] = Message::newFromKey( 'cpd-error-message-missing-svg-file', $svgFilePage->getText() );
			}

			try {
				$cpdElements = $this->xmlProcessor->createElements( $process, $xml );
			} catch ( CpdXmlProcessingException $e ) {
				$warnings[] = Message::newFromKey( 'cpd-error-message-invalid-bpmn' );
				$cpdElements = [];
			}

			$this->setResultValues(
				$xml,
				array_map( static fn ( $element ) => json_encode( $element ), $cpdElements ),
				$svgFile?->getUrl(),
				$revisionId,
				$warnings
			);
		} catch ( CpdInvalidContentException $e ) {
			$this->setResultValues( null, [], null, null, [] );
		}
	}

	private function setResultValues( ?string $xml, array $elements, ?string $svgFile, ?int $revId, array $loadWarnings ): void {
		$result = $this->getResult();
		$result->addValue( null, 'xml', $xml );
		$result->addValue( null, 'elements', $elements );
		$result->addValue( null, 'svgFile', $svgFile );
		$result->addValue( null, 'revId', $revId );
		$result->addValue( null, 'loadWarnings', $loadWarnings );
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
			'revision' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			]
		];
	}
}
