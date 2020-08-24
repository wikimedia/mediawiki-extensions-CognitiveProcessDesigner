<?php
namespace CognitiveProcessDesigner\Hook\OutputPageParserOutput;

use BlueSpice\Hook\OutputPageParserOutput;

class AddModules extends OutputPageParserOutput {

	public function doProcess() {
		$this->out->addModules( [
			'ext.cognitiveProcessDesigner.editor',
			'ext.cognitiveProcessDesignerEdit.styles'
		] );
	}
}
