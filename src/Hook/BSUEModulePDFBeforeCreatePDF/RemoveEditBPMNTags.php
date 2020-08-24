<?php
namespace CognitiveProcessDesigner\Hook\BSUEModulePDFBeforeCreatePDF;

use BlueSpice\UEModulePDF\Hook\BSUEModulePDFBeforeCreatePDF;
use DomXPath;

class RemoveEditBPMNTags extends BSUEModulePDFBeforeCreatePDF {

	protected $classesToRemove = [
		'cpd-toolbar',
		'cpd-js-drop-zone'
	];

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$finder = new DomXPath( $this->DOM );
		foreach ( $this->classesToRemove as $class ) {
			$elements = $finder->query( "//*[contains(@class, '" . $class . "')]" );
			foreach ( $elements as $element ) {
				$element->parentNode->removeChild( $element );
			}
		}

		return true;
	}

}
