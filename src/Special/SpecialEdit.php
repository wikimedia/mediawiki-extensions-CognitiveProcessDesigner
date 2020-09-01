<?php

namespace CognitiveProcessDesigner\Special;

class SpecialEdit extends CognitiveSpecialPage {

	public function __construct() {
		parent::__construct(
			'CognitiveProcessDesignerEdit',
			'cognitiveprocessdesigner-viewspecialpage',
			true
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $par ) {
		parent::execute( $par );

		$this->getOutput()->addModules( [
			'ext.cognitiveProcessDesignerEdit.special'
		] );
	}
}
