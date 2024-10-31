<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\CpdElementFactory;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use CognitiveProcessDesigner\Job\MoveDescriptionPage;
use CognitiveProcessDesigner\Job\SaveDescriptionPage;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use Exception;
use JobQueueGroup;
use MediaWiki\User\UserIdentity;
use Wikimedia\ParamValidator\ParamValidator;

class SaveCpdDescriptionPages extends ApiBase {
	/**
	 * @var CpdElementFactory
	 */
	private CpdElementFactory $cpdElementFactory;

	/**
	 * @var JobQueueGroup
	 */
	private JobQueueGroup $jobQueueGroup;

	/**
	 * @var CpdDiagramPageUtil
	 */
	private CpdDiagramPageUtil $diagramPageUtil;

	/**
	 * @var CpdDescriptionPageUtil
	 */
	private CpdDescriptionPageUtil $descriptionPageUtil;

	/**
	 * @var UserIdentity
	 */
	private UserIdentity $user;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdElementFactory $cpdElementFactory
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		CpdElementFactory $cpdElementFactory,
		CpdDiagramPageUtil $diagramPageUtil,
		CpdDescriptionPageUtil $descriptionPageUtil,
		JobQueueGroup $jobQueueGroup
	) {
		parent::__construct( $main, $action );

		$this->cpdElementFactory = $cpdElementFactory;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->diagramPageUtil = $diagramPageUtil;
		$this->descriptionPageUtil = $descriptionPageUtil;
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute(): void {
		$this->user = $this->getContext()->getUser();

		$params = $this->extractRequestParams();
		$process = $params['process'];
		$elements = json_decode( $params['elements'], true );

		if ( empty( $elements ) ) {
			$this->getResult()->addValue( null, 'descriptionPages', [] );
			$this->getResult()->addValue( null, 'warnings', [] );

			return;
		}

		$elements = $this->cpdElementFactory->makeElements( $elements );
		// TODO: Think about moving this to save description pages
		$this->descriptionPageUtil->updateOrphanedDescriptionPages( $elements, $process );
		$this->descriptionPageUtil->updateElementConnections( $elements, $process );

		try {
			$result = $this->processDescriptionPages( $elements );
		} catch ( CpdSaveException $e ) {
			$this->getResult()->addValue( null, 'error', $e->getMessage() );

			return;
		}

		$this->getResult()->addValue(
			null,
			'descriptionPages',
			array_map( static function ( $element ) {
				return json_encode( $element );
			}, $result['elements'] )
		);

		$this->getResult()->addValue(
			null,
			'warnings',
			$result['warnings']
		);
	}

	/**
	 * @param CpdElement[] $elements
	 *
	 * @return array
	 * @throws CpdSaveException
	 */
	private function processDescriptionPages( array $elements ): array {
		if ( empty( $elements ) ) {
			throw new CpdSaveException( 'No elements to save' );
		}

		$warnings = [];
		$this->validateElements( $elements );
		foreach ( $elements as $element ) {
			$warnings = array_merge(
				$warnings,
				$this->processPage( $element )
			);
		}

		return [
			'elements' => $elements,
			'warnings' => $warnings
		];
	}

	/**
	 * Move or create description page
	 * for the given element
	 *
	 * @param CpdElement $element
	 *
	 * @return array
	 * @throws CpdSaveException
	 */
	private function processPage( CpdElement $element ): array {
		$warnings = [];

		$descriptionPage = $element->getDescriptionPage();
		if ( !$descriptionPage ) {
			$warnings[] = "Element {$element->getId()} has no description page property";

			return $warnings;
		}

		try {
			$this->diagramPageUtil->validateNamespace( $descriptionPage );
		} catch ( CpdInvalidNamespaceException $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}

		$oldDescriptionPage = $element->getOldDescriptionPage();
		if ( $oldDescriptionPage ) {
			// If the old description page does not exist, add a warning and create the new description page
			if ( !$oldDescriptionPage->exists() ) {
				$warnings[] = "Description page {$oldDescriptionPage->getPrefixedDBkey()} does not exist anymore";
			} else {
				$this->moveDescriptionPage( $element );

				return $warnings;
			}
		}

		if ( $descriptionPage->exists() ) {
			$warnings[] = "Description page {$descriptionPage->getPrefixedDBkey()} already exists";

			return $warnings;
		}

		$this->createDescriptionPage( $element );

		return $warnings;
	}

	/**
	 * @param CpdElement $element
	 *
	 * @return void
	 * @throws CpdSaveException
	 */
	private function moveDescriptionPage(
		CpdElement $element
	): void {
		try {
			$job = new MoveDescriptionPage(
				$element->getOldDescriptionPage(), $element->getDescriptionPage(), $this->user
			);
			$job->run();
			// TODO implement job queue; remove this line
			//$this->jobQueueGroup->push( $job );
		} catch ( Exception $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}
	}

	/**
	 * @param CpdElement $element
	 *
	 * @return void
	 * @throws CpdSaveException
	 */
	private function createDescriptionPage( CpdElement $element ): void {
		$content = $this->descriptionPageUtil->generateContentByType( $element->getType() );
		try {
			$job = new SaveDescriptionPage( $element->getDescriptionPage(), $content, $this->user );
			// TODO implement job queue; remove this line
			//$this->jobQueueGroup->push( $job );
			$job->run();
		} catch ( Exception $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}
	}

	/**
	 * Check for required description pages
	 * and duplicate description pages
	 *
	 * @param CpdElement[] $elements
	 *
	 * @throws CpdSaveException
	 */
	private function validateElements( array $elements ): void {
		$descriptionPages = [];
		foreach ( $elements as $element ) {
			if ( !$element->getDescriptionPage() ) {
				throw new CpdSaveException( "Element {$element->getId()} has no description page property" );
			}

			if ( in_array( $element->getDescriptionPage(), $descriptionPages ) ) {
				throw new CpdSaveException( "Duplicate description page {$element->getDescriptionPage()}" );
			}

			$descriptionPages[] = $element->getDescriptionPage();
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
			'elements' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			]
		];
	}
}
