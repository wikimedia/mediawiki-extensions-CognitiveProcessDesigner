<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use Content;
use MediaWiki\Config\Config;
use MediaWiki\Page\PageStore;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\ILoadBalancer;

class CpdDescriptionPageUtil {
	/**
	 * @var PageStore
	 */
	private PageStore $pageStore;

	/**
	 * @var ILoadBalancer
	 */
	private ILoadBalancer $loadBalancer;

	/**
	 * @var Config
	 */
	private Config $config;

	/**
	 * @var WikiPageFactory
	 */
	private WikiPageFactory $wikiPageFactory;

	/**
	 * @var CpdElementConnectionUtil
	 */
	private CpdElementConnectionUtil $connectionUtil;

	/**
	 * @param PageStore $pageStore
	 * @param ILoadBalancer $loadBalancer
	 * @param WikiPageFactory $wikiPageFactory
	 * @param Config $config
	 * @param CpdElementConnectionUtil $connectionUtil
	 */
	public function __construct(
		PageStore $pageStore,
		ILoadBalancer $loadBalancer,
		WikiPageFactory $wikiPageFactory,
		Config $config,
		CpdElementConnectionUtil $connectionUtil
	) {
		$this->pageStore = $pageStore;
		$this->loadBalancer = $loadBalancer;
		$this->config = $config;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->connectionUtil = $connectionUtil;
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
	 *
	 * @return void
	 */
	public function updateOrphanedDescriptionPages( array $elements, string $process ): void {
		$dbw = $this->loadBalancer->getConnectionRef( DB_PRIMARY );

		// Clear orphaned pages rows from this process
		$dbw->delete(
			'cpd_orphaned_description_pages',
			[ 'process' => $process ],
			__METHOD__
		);

		$orphanedPages = [];
		$existingPages = array_map( fn( Title $title ) => $title->getPrefixedDBkey(),
			$this->findDescriptionPages( $process ) );
		$pagesFromElements = array_map( fn( CpdElement $element ) => $element->getDescriptionPage()->getPrefixedDBkey(),
			$elements );

		foreach ( $existingPages as $descriptionPage ) {
			if ( !in_array( $descriptionPage, $pagesFromElements, true ) ) {
				$orphanedPages[] = $descriptionPage;
			}
		}

		if ( empty( $orphanedPages ) ) {
			return;
		}

		// Insert orphaned pages
		$dbw->insert(
			'cpd_orphaned_description_pages',
			array_map( fn( string $page ) => [
				'process' => $process,
				'page_title' => $page
			], $orphanedPages ),
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
}
