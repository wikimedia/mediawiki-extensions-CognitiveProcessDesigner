<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\Exceptions\CpdSvgException;
use CognitiveProcessDesigner\Process\SvgFile;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MWContentSerializationException;
use MWException;
use Wikimedia\ParamValidator\ParamValidator;

class SaveCpdDiagram extends ApiBase {
	/**
	 * @var SvgFile
	 */
	private SvgFile $svgFile;

	/**
	 * @var CpdDiagramPageUtil
	 */
	private CpdDiagramPageUtil $diagramPageUtil;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param SvgFile $svgFile
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		CpdDiagramPageUtil $diagramPageUtil,
		SvgFile $svgFile
	) {
		parent::__construct( $main, $action );

		$this->svgFile = $svgFile;
		$this->diagramPageUtil = $diagramPageUtil;
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 * @throws MWContentSerializationException
	 * @throws MWException
	 */
	public function execute() {
		$user = $this->getContext()->getUser();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$xml = json_decode( $params['xml'], true );
		$svg = json_decode( $params['svg'], true );

		$diagramPage = $this->diagramPageUtil->createOrUpdateDiagramPage( $process, $user, $xml );
		$svgFile = $this->diagramPageUtil->getSvgFilePage( $process );

		try {
			$this->svgFile->save( $svgFile, $svg, $user );
		} catch ( CpdSvgException $e ) {
			$this->getResult()->addValue( null, 'error', $e->getMessage() );

			return;
		}

		$this->getResult()->addValue( null, 'svgFile', $svgFile->getPrefixedDBkey() );
		$this->getResult()->addValue( null, 'diagramPage', $diagramPage->getTitle()->getPrefixedDBkey() );
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
			'xml' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'svg' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
