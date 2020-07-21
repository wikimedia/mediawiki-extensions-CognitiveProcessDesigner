<?php

namespace CognitiveProcessDesigner\Special;

class SpecialEdit extends CognitiveSpecialPage {

	public function __construct() {
		parent::__construct(
			'CognitiveProcessDesignerEdit',
			'cognitiveprocessdesigner-viewspecialpage',
			true
		);
		$this->getOutput()->addModules( [
			'ext.cognitiveProcessDesignerEdit.special'
		] );
	}

	/**
	 * @return bool
	 */
	protected function isEditPage() {
		return true;
	}
}
