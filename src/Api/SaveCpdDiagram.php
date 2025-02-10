<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\CpdElementFactory;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use CognitiveProcessDesigner\Exceptions\CpdSvgException;
use CognitiveProcessDesigner\Process\SvgFile;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdSaveDescriptionPagesUtil;
use Exception;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MWContentSerializationException;
use MWUnknownContentModelException;
use RuntimeException;
use Wikimedia\ParamValidator\ParamValidator;

class SaveCpdDiagram extends ApiBase {

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdSaveDescriptionPagesUtil $saveDescriptionPagesUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param CpdElementFactory $cpdElementFactory
	 * @param SvgFile $svgFile
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdSaveDescriptionPagesUtil $saveDescriptionPagesUtil,
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly CpdElementFactory $cpdElementFactory,
		private readonly SvgFile $svgFile
	) {
		parent::__construct( $main, $action );
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 * @throws MWContentSerializationException
	 * @throws Exception
	 * @throws MWUnknownContentModelException
	 */
	public function execute() {
		$user = $this->getContext()->getUser();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$xml = json_decode( $params['xml'], true );
		$svg = json_decode( $params['svg'], true );

		$elements = json_decode( $params['elements'], true );
		$cpdElements = $this->cpdElementFactory->makeElements( $elements );

		$svgFilePage = $this->diagramPageUtil->getSvgFilePage( $process );
		$file = $this->svgFile->save( $svgFilePage, $svg, $user );

		$diagramPage = $this->diagramPageUtil->createOrUpdateDiagramPage( $process, $user, $xml, $file );

		$this->getResult()->addValue( null, 'svgFile', $svgFilePage->getPrefixedDBkey() );
		$this->getResult()->addValue( null, 'diagramPage', $diagramPage->getTitle()->getPrefixedDBkey() );

		// Save description pages
		if ( !$params['saveDescriptionPages'] ) {
			$this->getResult()->addValue( null, 'descriptionPages', [] );
			$this->getResult()->addValue( null, 'saveWarnings', [] );
		} else {
			$warnings = $this->saveDescriptionPagesUtil->saveDescriptionPages(
				$user,
				$process,
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
				'saveWarnings',
				$warnings
			);
		}

		// Process possible orphaned description pages after processing description pages, e.g. renaming
		$this->descriptionPageUtil->updateOrphanedDescriptionPages(
			$cpdElements,
			$process,
			$diagramPage->getRevisionRecord()->getId()
		);
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
