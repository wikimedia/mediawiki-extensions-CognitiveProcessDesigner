<?php

namespace CognitiveProcessDesigner\Special;

use BlueSpice\SpecialPage;
use Html;

class CognitiveSpecialPage extends SpecialPage {

	/**
	 * @param string $par
	 * @return string|void
	 */
	public function execute( $par ) {
		$this->setHeaders();

		$html = Html::openElement(
			'div',
			[
				'class' => 'cpd-toolbar',
				'align' => 'right'
			]
		);
		$html .= Html::element(
			'button',
			[
				'id' => 'cpd-btn-edit-bpmn-id',
				'class' => 'cpd-edit-bpmn hidden'
			],
			wfMessage( 'edit' )->text()
		);
		$html .= Html::closeElement( 'div' );

		$html .= Html::element(
			'div',
			[ 'id' => 'cpd-wrapper', 'class' => 'hidden cpd-js-drop-zone' ]
		);

		$html .= Html::openElement(
			'a',
			[
				'id' => 'cpd-img-href',
				'href' => ''
			]
		);

		$html .= Html::element(
			'img',
			[
				'id' => 'cpd-img',
				'src' => '',
				'class' => 'hidden',
				'width' => 0,
				'height' => 0,
			]
		);
		$html .= Html::closeElement( 'a' );

		$this->getOutput()->addHTML( $html );
		$this->getOutput()->addModuleStyles( [
			'ext.cognitiveProcessDesignerEdit.styles'
		] );
	}

	/**
	 * @return string ID of the HTML element being added
	 */
	protected function getId() {
		return 'cpd';
	}
}
