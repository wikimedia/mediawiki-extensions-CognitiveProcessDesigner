<?php

namespace CognitiveProcessDesigner\Hook\BSInsertMagicAjaxGetData;

use BlueSpice\InsertMagic\Hook\BSInsertMagicAjaxGetData;

class AddBpmnTag extends BSInsertMagicAjaxGetData {

	/**
	 * @inheritDoc
	 */
	protected function skipProcessing() {
		return $this->type !== 'tags';
	}

	/**
	 * @inheritDoc
	 */
	protected function doProcess() {
		$this->response->result[] = (object)[
			'id' => 'bpmn',
			'type' => 'tag',
			'name' => 'bpmn',
			'desc' => $this->msg( 'cpd-tag-bpmn-desc' )->text(),
			'code' => '<bpmn name="Some diagram" />',
			'mwvecommand' => 'bpmnCommand',
			'previewable' => false,
			'examples' => [
				[
					'label' => $this->msg( 'cpd-tag-bpmn-example' )->plain(),
					'code' => '<bpmn name="Some diagram" />'
				]
			],
			'helplink' => 'https://www.mediawiki.org/wiki/Extension:Cognitive_Process_Designer'
		];

		return true;
	}
}
