<?php

namespace CognitiveProcessDesigner\Hook\BSUEModulePDFBeforeAddingStyleBlocks;

use BlueSpice\UEModulePDF\Hook\BSUEModulePDFBeforeAddingStyleBlocks;
use RequestContext;

class AddCPDStyles extends BSUEModulePDFBeforeAddingStyleBlocks {

	/**
	 * Embeds CSS into pdf export
	 * @return bool Always true to keep hook running
	 */
	protected function doProcess() {
		$path = dirname( dirname( dirname( __DIR__ ) ) ) . '/resources/styles/cpd.entity.less';

		$compiler = RequestContext::getMain()
			->getOutput()
			->getResourceLoader()
			->getLessCompiler();

		$this->styleBlocks['CPD'] = $compiler->parse(
				file_get_contents( $path ),
				$path
		)->getCss();

		return true;
	}
}
