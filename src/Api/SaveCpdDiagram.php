<?php

namespace CognitiveProcessDesigner\Api;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdSvgException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\SvgFile;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdSaveDescriptionPagesUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MWContentSerializationException;
use MWUnknownContentModelException;
use PermissionsError;
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
	 *
	 * @throws ApiUsageException
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 * @throws CpdInvalidContentException
	 * @throws CpdXmlProcessingException
	 * @throws MWContentSerializationException
	 * @throws MWUnknownContentModelException
	 * @throws PermissionsError
	 */
	public function execute() {
		$result = $this->getResult();
		$user = $this->getContext()->getUser();
		$params = $this->extractRequestParams();
		$process = $params[ 'process' ];

		$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );
		$this->getPermissionManager()->throwPermissionErrors(
			'edit',
			$user,
			$diagramPage->getTitle()
		);

		$svg = $params[ 'svg' ];
		if ( !empty( $svg ) ) {
			$svg = json_decode( $params[ 'svg' ], true );
		}
		$xml = json_decode( $params[ 'xml' ], true );
		$xml = $this->sanitizeXml( $xml );

		$cpdElements = $this->xmlProcessor->createElements(
			$process,
			$xml,
			$this->diagramPageUtil->getXml( $process )
		);

		$warnings = [];

		// Save SVG file
		$svgFilePage = $this->diagramPageUtil->getSvgFilePage( $process );
		try {
			$file = $this->svgFile->save( $svgFilePage, $svg, $user );
		} catch ( CpdSvgException $e ) {
			$warnings[] = $e->getMessage();
			$file = null;
			$svgFilePage = null;
		}

		$diagramPage = $this->diagramPageUtil->createOrUpdateDiagramPage( $process, $user, $xml, $file );

		// Save description pages
		if ( $params[ 'savedescriptionpages' ] ) {
			$warnings = array_merge(
				$warnings,
				$this->saveDescriptionPagesUtil->saveDescriptionPages( $user, $cpdElements )
			);
		}

		$result->addValue( null, 'svgFile', $svgFilePage?->getPrefixedDBkey() );
		$result->addValue( null, 'diagramPage', $diagramPage->getTitle()->getPrefixedDBkey() );
		$result->addValue(
			null,
			'elements',
			array_map( static fn ( $element ) => json_encode( $element ), $cpdElements )
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
	 * @param string $xml
	 *
	 * @return string
	 */
	private function sanitizeXml( string $xml ): string {
		return preg_replace_callback(
			'/name="([^"]+)"/',
			static function ( $matches ) {
				$original = $matches[1];
				$result = '';

				for ( $i = 0; $i < mb_strlen( $original ); $i++ ) {
					$char = mb_substr( $original, $i, 1 );
					$code = mb_ord( $char, 'UTF-8' );
					$unicode = sprintf( 'U+%04X', $code );

					// ERM42675
					// Replace U+002F (/ Slash) with U+2215 (∕ DIVISION SLASH)
					if ( $unicode === "U+002F" ) {
						$char = mb_chr( 0x2215, 'UTF-8' );
					}

					$result .= $char;
				}

				return 'name="' . $result . '"';
			},
			$xml
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
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ''
			],
			'savedescriptionpages' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_DEFAULT => false
			]
		];
	}
}
