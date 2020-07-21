<?php

namespace CognitiveProcessDesigner\ResourceModule;

use ResourceLoaderContext;

class BpmnJS extends \ResourceLoaderFileModule {

	/**
	 *
	 * @param \ResourceLoaderContext $context
	 * @return array
	 */
	protected function getScriptFiles( \ResourceLoaderContext $context ) {
		if ( $context->getDebug() ) {
			array_unshift( $this->scripts, 'bpmn-js/bpmn-modeler.development.js' );
		} else {
			array_unshift( $this->scripts, 'bpmn-js/bpmn-modeler.production.min.js' );
		}
		$files = parent::getScriptFiles( $context );
		return $files;
	}

	/**
	 * Get a list of file paths for all styles in this module, in order of proper inclusion.
	 *
	 * @param ResourceLoaderContext $context
	 * @return array List of file paths
	 */
	public function getStyleFiles( ResourceLoaderContext $context ) {
		$styleFiles = parent::getStyleFiles( $context );
		if ( !isset( $styleFiles['all'] ) ) {
			$styleFiles['all'] = [];
		}
		$styleFiles['all'] = array_merge( [
			'bpmn-js/assets/diagram-js.css',
			'bpmn-js/assets/bpmn-font/css/bpmn.css'
		], $styleFiles['all'] );

		return $styleFiles;
	}

}
