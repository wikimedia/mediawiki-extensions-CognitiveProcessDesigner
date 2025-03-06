<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use CognitiveProcessDesigner\RevisionLookup\IRevisionLookup;
use MediaWiki\Config\Config;
use MediaWiki\Content\Content;
use MediaWiki\Page\PageStore;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\ILoadBalancer;

class CpdDescriptionPageUtil {

	/**
	 * @param PageStore $pageStore
	 * @param ILoadBalancer $loadBalancer
	 * @param WikiPageFactory $wikiPageFactory
	 * @param Config $config
	 * @param CpdElementConnectionUtil $connectionUtil
	 * @param IRevisionLookup $lookup
	 */
	public function __construct(
		private readonly PageStore $pageStore,
		private readonly ILoadBalancer $loadBalancer,
		private readonly WikiPageFactory $wikiPageFactory,
		private readonly Config $config,
		private readonly CpdElementConnectionUtil $connectionUtil,
		private readonly IRevisionLookup $lookup
	) {
	}

	/**
	 * @param string $bpmnType
	 *
	 * @return Content|null
	 * @throws CpdSaveException
	 */
	public function generateContentByType( string $bpmnType ): ?Content {
		return $this->getTemplateContent( $bpmnType );
	}

	/**
	 * @param string $bpmnType
	 *
	 * @return Content
	 * @throws CpdSaveException
	 */
	private function getTemplateContent( string $bpmnType ): Content {
		if ( !$this->config->has( 'CPDPageTemplates' ) ) {
			throw new CpdSaveException( 'CPDPageTemplates not configured' );
		}

		$templateConfig = $this->config->get( 'CPDPageTemplates' );
		$template = $templateConfig[$bpmnType] ?? $templateConfig['*'];
		$templateTitle = Title::newFromDBkey( $template );

		if ( !$templateTitle->exists() ) {
			throw new CpdSaveException( "$template does not exist" );
		}

		$templatePage = $this->wikiPageFactory->newFromTitle( $templateTitle );

		return $templatePage->getContent();
	}

	/**
	 * @param string $process
	 *
	 * @return Title[]
	 */
	public function findDescriptionPages( string $process ): array {
		$process = ucfirst( $process );
		$pages = [];

		$queryBuilder = $this->pageStore->newSelectQueryBuilder();
		$queryBuilder->conds( [
			"page_namespace" => NS_PROCESS,
			"LOWER(page_title) LIKE '$process/%'"
		] );

		foreach ( $queryBuilder->fetchPageRecords() as $row ) {
			$pages[] = Title::newFromText( $row->getDBkey(), $row->getNamespace() );
		}

		return $pages;
	}

	/**
	 * @param Title $title
	 *
	 * @return bool
	 */
	public function isDescriptionPage( Title $title ): bool {
		if ( $title->getNamespace() !== NS_PROCESS ) {
			return false;
		}

		if ( $title->getContentModel() === CognitiveProcessDesignerContent::MODEL ) {
			return false;
		}

		return true;
	}

	/**
	 * @param CpdElement[] $elements
	 * @param string $process
	 * @param int $revision
	 *
	 * @return void
	 */
	public function updateOrphanedDescriptionPages( array $elements, string $process, int $revision ): void {
		$dbw = $this->loadBalancer->getConnection( DB_PRIMARY );

		if ( !$this->isStabilizationEnabled() ) {
			// Clear orphaned pages rows from this process
			$dbw->delete(
				'cpd_orphaned_description_pages',
				[ 'process' => $process ],
				__METHOD__
			);
		}

		$orphanedPages = [];
		$existingPages = array_map( fn( Title $title ) => $title->getPrefixedDBkey(),
			$this->findDescriptionPages( $process ) );
		$pagesFromElements = array_map(
			fn( CpdElement $element ) => $element->getDescriptionPage()->getPrefixedDBkey(),
			$elements
		);

		foreach ( $existingPages as $descriptionPage ) {
			if ( !in_array( $descriptionPage, $pagesFromElements, true ) ) {
				$orphanedPages[] = $descriptionPage;
			}
		}

		if ( empty( $orphanedPages ) ) {
			return;
		}

		$dbw->insert(
			'cpd_orphaned_description_pages',
			array_map( fn( string $page ) => [
				'process' => $process,
				'process_rev' => $revision,
				'page_title' => $page
			], $orphanedPages ),
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * Removes all orphaned description pages for the given process
	 * except the ones that are in the given revision when given.
	 * Runs when a new a process is stabilized.
	 *
	 * @param string $process
	 * @param int|null $revision
	 *
	 * @return void
	 */
	public function cleanUpOrphanedDescriptionPages( string $process, ?int $revision = null ): void {
		$dbw = $this->loadBalancer->getConnectionRef( DB_PRIMARY );

		$conds = [ 'process' => $process ];
		if ( $revision ) {
			$conds[] = 'process_rev != ' . $revision;
		}

		$dbw->delete(
			'cpd_orphaned_description_pages',
			$conds,
			__METHOD__
		);
	}

	/**
	 * @param CpdElement[] $elements
	 * @param string $process
	 *
	 * @return void
	 */
	public function updateElementConnections( array $elements, string $process ): void {
		$this->connectionUtil->updateElementConnections( $elements, $process );
	}

	private function isStabilizationEnabled(): bool {
		$dummyPage = Title::newFromText( 'Dummy', NS_PROCESS );

		return $this->lookup->isStabilizationEnabled( $dummyPage );
	}
}
