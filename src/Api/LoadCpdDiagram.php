<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Revision\RevisionLookup;
use Status;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class LoadCpdDiagram extends ApiBase {

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/** @var CpdDescriptionPageUtil */
	private CpdDescriptionPageUtil $descriptionPageUtil;

	/** @var RevisionLookup */
	private RevisionLookup $revisionLookup;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param RevisionLookup $revisionLookup
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		CpdDiagramPageUtil $diagramPageUtil,
		CpdDescriptionPageUtil $descriptionPageUtil,
		RevisionLookup $revisionLookup
	) {
		parent::__construct( $main, $action );

		$this->diagramPageUtil = $diagramPageUtil;
		$this->descriptionPageUtil = $descriptionPageUtil;
		$this->revisionLookup = $revisionLookup;
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 * @throws CpdInvalidContentException
	 */
	public function execute() {
		$result = $this->getResult();
		$params = $this->extractRequestParams();
		$process = $params['process'];
		$revisionId = $params['revisionId'];

		try {
			if ( $revisionId ) {
				$revision = $this->revisionLookup->getRevisionById( $revisionId );
				$content = $revision->getContent( 'main' );

				if ( !$content ) {
					throw new CpdInvalidContentException( 'Process page does not exist' );
				}

				$svgFile = $this->diagramPageUtil->getSvgFile( $process, $revision );
			} else {
				$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );

				if ( !$diagramPage->exists() ) {
					throw new CpdInvalidContentException( 'Process page does not exist' );
				}

				$content = $diagramPage->getContent();
				$svgFile = $this->diagramPageUtil->getSvgFile( $process );
			}

			$this->diagramPageUtil->validateContent( $content );

			$result->addValue( null, 'exists', 1 );
			$result->addValue( null, 'xml', $content->getText() );
			$result->addValue(
				null,
				'descriptionPages',
				$this->splitDescriptionPagesStatus( $this->descriptionPageUtil->findDescriptionPages( $process ) )
			);


			if ( !$svgFile ) {
				throw new ApiUsageException( null, Status::newFatal( "Diagram svg file does not exist" ) );
			}

			$result->addValue( null, 'svgFile', $svgFile->getUrl() );
		} catch ( CpdInvalidContentException $e ) {
			$result->addValue( null, 'exists', 0 );
			$result->addValue( null, 'xml', null );
			$result->addValue( null, 'descriptionPages', [
				'new' => [],
				'edited' => []
			] );
			$result->addValue( null, 'svgFile', null );
		}
	}

	/**
	 * @param Title[] $pages
	 *
	 * @return string[][]
	 */
	private function splitDescriptionPagesStatus( array $pages ): array {
		$dbKeys = [
			'new' => [],
			'edited' => []
		];

		foreach ( $pages as $page ) {
			$dbKey = $page->getPrefixedDBkey();
			$dbKeys[$page->isNewPage() ? 'new' : 'edited'][] = $dbKey;
		}

		return $dbKeys;
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
