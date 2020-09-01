<?php

namespace CognitiveProcessDesigner\Special;

class SpecialTestSuite extends CognitiveSpecialPage {

	/**
	 * SpecialTestSuite constructor.
	 */
	public function __construct() {
		parent::__construct(
			'CognitiveProcessDesignerTestSuite',
			'cognitiveprocessdesigner-viewspecialpage',
			false
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $par ) {
		parent::execute( $par );

		$this->getOutput()->addModules( [
			'ext.cognitiveProcessDesignerTestSuite.special'
		] );
	}
}
