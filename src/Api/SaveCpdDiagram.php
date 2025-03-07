<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\Process\SvgFile;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdSaveDescriptionPagesUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use Exception;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MWContentSerializationException;
use MWUnknownContentModelException;
use Wikimedia\ParamValidator\ParamValidator;

class SaveCpdDiagram extends ApiBase {

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdXmlProcessor $xmlProcessor
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdSaveDescriptionPagesUtil $saveDescriptionPagesUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param SvgFile $svgFile
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly CpdXmlProcessor $xmlProcessor,
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdSaveDescriptionPagesUtil $saveDescriptionPagesUtil,
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
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
		$result = $this->getResult();
		$user = $this->getContext()->getUser();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$xml = json_decode( $params['xml'], true );
		$svg = json_decode( $params['svg'], true );

		$cpdElements = $this->xmlProcessor->createElements(
			$process,
			$xml,
			$this->diagramPageUtil->getXml( $process )
		);

		$svgFilePage = $this->diagramPageUtil->getSvgFilePage( $process );
		$file = $this->svgFile->save( $svgFilePage, $svg, $user );

		$diagramPage = $this->diagramPageUtil->createOrUpdateDiagramPage( $process, $user, $xml, $file );

		// Save description pages
		$warnings = [];
		if ( $params['saveDescriptionPages'] ) {
			$warnings = $this->saveDescriptionPagesUtil->saveDescriptionPages(
				$user,
				$process,
				$cpdElements
			);
		}

		$result->addValue( null, 'svgFile', $svgFilePage->getPrefixedDBkey() );
		$result->addValue( null, 'diagramPage', $diagramPage->getTitle()->getPrefixedDBkey() );
		$result->addValue(
			null,
			'elements',
			array_map( fn( $element ) => json_encode( $element ), $cpdElements )
		);
		$result->addValue(
			null,
			'saveWarnings',
			$warnings
		);

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
			'saveDescriptionPages' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_DEFAULT => false
			]
		];
	}
}
