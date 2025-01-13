<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use CognitiveProcessDesigner\CpdElementFactory;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use CognitiveProcessDesigner\Exceptions\CpdSvgException;
use CognitiveProcessDesigner\Process\SvgFile;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdSaveDescriptionPagesUtil;
use MWContentSerializationException;
use MWException;
use Wikimedia\ParamValidator\ParamValidator;

class SaveCpdDiagram extends ApiBase {
	/** @var SvgFile */
	private SvgFile $svgFile;

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/** @var CpdSaveDescriptionPagesUtil */
	private CpdSaveDescriptionPagesUtil $saveDescriptionPagesUtil;

	/** @var CpdElementFactory */
	private CpdElementFactory $cpdElementFactory;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdSaveDescriptionPagesUtil $saveDescriptionPagesUtil
	 * @param CpdElementFactory $cpdElementFactory
	 * @param SvgFile $svgFile
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		CpdDiagramPageUtil $diagramPageUtil,
		CpdSaveDescriptionPagesUtil $saveDescriptionPagesUtil,
		CpdElementFactory $cpdElementFactory,
		SvgFile $svgFile
	) {
		parent::__construct( $main, $action );

		$this->svgFile = $svgFile;
		$this->diagramPageUtil = $diagramPageUtil;
		$this->saveDescriptionPagesUtil = $saveDescriptionPagesUtil;
		$this->cpdElementFactory = $cpdElementFactory;
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

		// Save description pages
		$elements = json_decode( $params['elements'], true );
		if ( !$params['saveDescriptionPages'] || empty( $elements ) ) {
			$this->getResult()->addValue( null, 'descriptionPages', [] );
			$this->getResult()->addValue( null, 'warnings', [] );

			return;
		}

		$cpdElements = $this->cpdElementFactory->makeElements( $elements );

		try {
			$warnings = $this->saveDescriptionPagesUtil->saveDescriptionPages(
				$user,
				$process,
				$diagramPage->getRevisionRecord()->getId(),
				$cpdElements
			);

			$this->getResult()->addValue(
				null,
				'descriptionPages',
				array_map( static function ( $element ) {
					return json_encode( $element );
				}, $cpdElements )
			);

			$this->getResult()->addValue(
				null,
				'warnings',
				$warnings
			);
		} catch ( CpdSaveException $e ) {
			$this->getResult()->addValue( null, 'error', $e->getMessage() );
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
			'xml' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'svg' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'elements' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'saveDescriptionPages' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_DEFAULT => false
			]
		];
	}
}
