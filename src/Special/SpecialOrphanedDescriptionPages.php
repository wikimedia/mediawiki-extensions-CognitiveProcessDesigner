<?php

namespace CognitiveProcessDesigner\Special;

use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;

class SpecialOrphanedDescriptionPages extends SpecialPage {
	public function __construct() {
		parent::__construct( 'OrphanedProcessDescriptionPages' );
	}

	/**
	 * @param string|null $subPage
	 *
	 * @return void
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
