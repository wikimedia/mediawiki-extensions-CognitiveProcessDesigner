<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use CognitiveProcessDesigner\Job\MoveDescriptionPage;
use CognitiveProcessDesigner\Job\SaveDescriptionPage;
use Exception;
use JobQueueGroup;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Message\Message;
use User;

class CpdSaveDescriptionPagesUtil {

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param JobQueueGroup $jobQueueGroup
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly JobQueueGroup $jobQueueGroup,
		private readonly LinkRenderer $linkRenderer
	) {
	}

	/**
	 * @param User $user
	 * @param string $process
	 * @param CpdElement[] $elements
	 *
	 * @return array
	 * @throws CpdSaveException
	 */
	public function saveDescriptionPages( User $user, string $process, array $elements ): array {
		$warnings = $this->processDescriptionPages( $elements, $user );

		try {
			$this->descriptionPageUtil->updateElementConnections( $elements, $process );
		} catch ( Exception $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}

		return $warnings;
	}

	/**
	 * @param CpdElement[] $elements
	 * @param User $user
	 *
	 * @return array
	 * @throws CpdSaveException
	 */
	private function processDescriptionPages( array $elements, User $user ): array {
		if ( empty( $elements ) ) {
			throw new CpdSaveException( 'No elements to save' );
		}

		$warnings = [];
		$this->validateElements( $elements );
		foreach ( $elements as $element ) {
			$warnings = array_merge(
				$warnings,
				$this->processPage( $element, $user )
			);
		}

		return $warnings;
	}

	/**
	 * Move or create description page
	 * for the given element
	 *
	 * @param CpdElement $element
	 * @param User $user
	 *
	 * @return array
	 * @throws CpdSaveException
	 */
	private function processPage( CpdElement $element, User $user ): array {
		$warnings = [];

		$descriptionPage = $element->getDescriptionPage();
		if ( !$descriptionPage ) {
			$warnings[] = Message::newFromKey(
				'cpd-description-page-has-no-property-warning',
				$element->getId()
			)->text();

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
				$warnings[] = Message::newFromKey(
					'cpd-description-page-does-not-exist-anymore-warning',
					$this->linkRenderer->makeLink(
						$oldDescriptionPage,
						$oldDescriptionPage->getSubpageText()
					)
				)->text();
			} else {
				$this->moveDescriptionPage( $element, $user );

				return $warnings;
			}
		}

		if ( $descriptionPage->exists() ) {
			return $warnings;
		}

		$this->createDescriptionPage( $element, $user );

		return $warnings;
	}

	/**
	 * @param CpdElement $element
	 * @param User $user
	 *
	 * @return void
	 * @throws CpdSaveException
	 */
	private function moveDescriptionPage(
		CpdElement $element,
		User $user
	): void {
		try {
			$job = new MoveDescriptionPage(
				$element->getOldDescriptionPage(), $element->getDescriptionPage(), $user
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
	 * @param User $user
	 *
	 * @return void
	 * @throws CpdSaveException
	 */
	private function createDescriptionPage( CpdElement $element, User $user ): void {
		$content = $this->descriptionPageUtil->generateContentByType( $element->getType() );
		try {
			$job = new SaveDescriptionPage( $element->getDescriptionPage(), $content, $user );
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
}
