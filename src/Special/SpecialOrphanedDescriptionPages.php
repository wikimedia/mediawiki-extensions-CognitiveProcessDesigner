<?php

namespace CognitiveProcessDesigner\Special;

use Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\Title;
use Message;
use OOUI\Exception;
use OOUI\FieldLayout;
use OOUI\SearchInputWidget;
use SpecialPage;
use Wikimedia\Rdbms\ILoadBalancer;

class SpecialOrphanedDescriptionPages extends SpecialPage {
	/**
	 * @var ILoadBalancer
	 */
	private ILoadBalancer $loadBalancer;

	/**
	 * @var LinkRenderer
	 */
	private LinkRenderer $linkRenderer;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct( ILoadBalancer $loadBalancer, LinkRenderer $linkRenderer ) {
		parent::__construct( 'OrphanedProcessDescriptionPages' );
		$this->loadBalancer = $loadBalancer;
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 * @param string|null $subPage
	 *
	 * @return void
	 * @throws Exception
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$out = $this->getOutput();
		$out->enableOOUI();
		$out->setPageTitle( $this->msg( 'orphanedprocessdescriptionpages' )->text() );
		$out->addModules( 'ext.cpd.special.orphanedpages' );
		$out->addHTML( $this->getHtml() );
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private function getHtml(): string {
		$links = $this->getOrphanedPagesLinks();

		if ( empty( $links ) ) {
			return Message::newFromKey( "cpd-empty-orphaned-description-pages-list" );
		}

		$html = Html::openElement( 'div', [ 'id' => 'cpd-special-orphaned-pages' ] );
		$html .= $this->getSearchWidget();

		$html .= Html::openElement( 'ul' );
		foreach ( $links as $link ) {
			$html .= Html::rawElement( 'li', [], $link );
		}
		$html .= Html::closeElement( 'ul' );
		$html .= Html::closeElement( 'div' );

		return $html;
	}

	/**
	 * @return string[]
	 */
	private function getOrphanedPagesLinks(): array {
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$rows = $dbr->select(
			'cpd_orphaned_description_pages', [
				'page_title',
				'process'
			]
		);

		$links = [];
		foreach ( $rows as $row ) {
			$title = Title::newFromDBkey( $row->page_title );
			$links[] = $this->linkRenderer->makeLink(
				$title,
				$title->getText()
			);
		}

		return $links;
	}

	/**
	 * @return string
	 *
	 * @throws Exception
	 */
	private function getSearchWidget(): string {
		$searchInput = new SearchInputWidget( [
			'infusable' => true,
			'id' => 'cpd-ui-orphanedpages-filter',
			'icon' => 'search'
		] );
		$search = Html::openElement( 'div', [
			'class' => 'allpages-filter-cnt'
		] );
		$search .= new FieldLayout( $searchInput, [
			'label' => $this->msg( 'bs-cpd-process-search-placeholder' )->text(),
			'align' => 'left'
		] );
		$search .= Html::closeElement( 'div' );

		return $search;
	}

	/**
	 * @return string
	 */
	protected function getGroupName(): string {
		return 'maintenance';
	}
}
