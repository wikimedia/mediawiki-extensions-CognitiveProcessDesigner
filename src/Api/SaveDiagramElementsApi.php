<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use CognitiveProcessDesigner\Process\SaveDiagramElementsStep;
use Wikimedia\ParamValidator\ParamValidator;

class SaveDiagramElementsApi extends ApiBase {

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'elements' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'cpd-api-save-diagram-elements-param-elements'
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$elements = json_decode( $params['elements'], true );

		$actorName = $this->getContext()->getUser()->getName();

		// TODO: Replace with background process after ProcessManager 2.0 will be released
		$saveDiagramElements = new SaveDiagramElementsStep( $elements, $actorName );
		$res = $saveDiagramElements->execute();

		$success = true;

		if ( $res['errors'] ) {
			$success = false;
			$this->getResult()->addValue( 'elements_save', 'errors', $res['errors'] );
		}

		if ( $res['warnings'] ) {
			$this->getResult()->addValue( 'elements_save', 'warnings', $res['warnings'] );
		}

		$this->getResult()->addValue( 'elements_save', 'success', $success );
	}

}
