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
		$this->getOutput()->addModules( [
			'ext.cognitiveProcessDesignerTestSuite.special'
		] );
	}
}
