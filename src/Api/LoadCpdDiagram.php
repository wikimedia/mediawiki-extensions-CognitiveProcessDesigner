<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Title\Title;
use Status;
use Wikimedia\ParamValidator\ParamValidator;

class LoadCpdDiagram extends ApiBase {

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/** @var CpdDescriptionPageUtil */
	private CpdDescriptionPageUtil $descriptionPageUtil;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		CpdDiagramPageUtil $diagramPageUtil,
		CpdDescriptionPageUtil $descriptionPageUtil
	) {
		parent::__construct( $main, $action );

		$this->diagramPageUtil = $diagramPageUtil;
		$this->descriptionPageUtil = $descriptionPageUtil;
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute() {
		$result = $this->getResult();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );

		try {
			$this->diagramPageUtil->validateContent( $diagramPage );
		} catch ( CpdInvalidContentException $e ) {
			$result->addValue( null, 'exists', 0 );
			$result->addValue( null, 'xml', null );
			$result->addValue( null, 'descriptionPages', [] );
			$result->addValue( null, 'svgFile', null );

			return;
		}

		$result->addValue( null, 'exists', 1 );
		$result->addValue( null, 'xml', $diagramPage->getContent()->getText() );
		$result->addValue(
			null,
			'descriptionPages',
			array_map( fn( Title $page ) => $page->getPrefixedDBkey(),
				$this->descriptionPageUtil->findDescriptionPages( $process ) )
		);

		$svgFile = $this->diagramPageUtil->getSvgFile( $process );
		if ( !$svgFile ) {
			throw new ApiUsageException( null, Status::newFatal( "Diagram svg file does not exist" ) );
		}

		$result->addValue( null, 'svgFile', $svgFile->getUrl() );
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
			]
		];
	}
}
