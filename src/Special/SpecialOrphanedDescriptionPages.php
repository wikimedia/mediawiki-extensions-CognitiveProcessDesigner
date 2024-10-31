<?php

namespace CognitiveProcessDesigner\Special;

use Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\Title;
use Message;
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
	 */
	public function execute( $subPage ) {
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'orphanedprocessdescriptionpages' )->text() );
		$out->addHTML( $this->getHtml() );
	}

	/**
	 * @return string
	 */
	private function getHtml(): string {
		$links = $this->getOrphanedPagesLinks();

		if ( empty( $links ) ) {
			return Message::newFromKey( "cpd-empty-orphaned-description-pages-list" );
		}

		$html = Html::openElement( 'div', [ 'class' => 'cpd-special-orphaned-pages' ] );
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
	 */
	protected function getGroupName(): string {
		return 'maintenance';
	}
}
