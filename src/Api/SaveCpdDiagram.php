<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\CpdElementFactory;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use CognitiveProcessDesigner\Exceptions\CpdSvgException;
use CognitiveProcessDesigner\Process\SvgFile;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use CognitiveProcessDesigner\Util\CpdSaveDescriptionPagesUtil;
use Exception;
use MWContentSerializationException;
use MWException;
use MWUnknownContentModelException;
use RuntimeException;
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
	 * @throws MWUnknownContentModelException
	 */
	public function execute() {
		$user = $this->getContext()->getUser();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$xml = json_decode( $params['xml'], true );
		$svg = json_decode( $params['svg'], true );

		$svgFilePage = $this->diagramPageUtil->getSvgFilePage( $process );
		try {
			$file = $this->svgFile->save( $svgFilePage, $svg, $user );
		} catch ( CpdSvgException | RuntimeException $e ) {
			$this->getResult()->addValue( null, 'error', $e->getMessage() );

			return;
		}
		$diagramPage = $this->diagramPageUtil->createOrUpdateDiagramPage( $process, $user, $xml, $file );

		$this->getResult()->addValue( null, 'svgFile', $svgFilePage->getPrefixedDBkey() );
		$this->getResult()->addValue( null, 'diagramPage', $diagramPage->getTitle()->getPrefixedDBkey() );

		// Save description pages
		$elements = json_decode( $params['elements'], true );
		if ( !$params['saveDescriptionPages'] || empty( $elements ) ) {
			$this->getResult()->addValue( null, 'descriptionPages', [] );
			$this->getResult()->addValue( null, 'warnings', [] );

			return;
		}

		try {
			$cpdElements = $this->cpdElementFactory->makeElements( $elements );
		} catch ( Exception $e ) {
			$this->getResult()->addValue( null, 'error', $e->getMessage() );

			return;
		}

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
