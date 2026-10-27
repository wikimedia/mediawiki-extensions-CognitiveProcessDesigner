<?php

namespace CognitiveProcessDesigner\Action;

use MediaWiki\Actions\EditAction;

class EditDiagramXmlAction extends EditAction {

	/**
	 * @return string
	 */
	public function getName(): string {
		return 'editxml';
	}
}
