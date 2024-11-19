<?php

namespace CognitiveProcessDesigner\Special;

use Html;
use MediaWiki\Linker\LinkRenderer;
use OOUI\Exception;
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
		$out->addModules( 'ext.cpd.special.orphaneddescriptionpages' );
		$out->addHTML( $this->getHtml() );
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private function getHtml(): string {
		return Html::element( 'div', [ 'id' => 'cpd-special-orphaned-pages' ] );
	}

	/**
	 * @return string
	 */
	protected function getGroupName(): string {
		return 'maintenance';
	}
}
