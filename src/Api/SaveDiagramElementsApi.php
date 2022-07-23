<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use CognitiveProcessDesigner\Process\SaveDiagramElementsStep;
use Exception;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\ProcessManager\ManagedProcess;
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

		$process = new ManagedProcess( [
			'save-elements-step' => [
				'class' => SaveDiagramElementsStep::class,
				'args' => [
					$elements,
					$actorName
				]
			]
		], 300 );

		/** @var \MWStake\MediaWiki\Component\ProcessManager\ProcessManager $processManager */
		$processManager = MediaWikiServices::getInstance()->getService( 'ProcessManager' );

		try {
			$processId = $processManager->startProcess( $process );
		} catch ( Exception $e ) {
			$this->getResult()->addValue( null, 'success', false );
			$this->getResult()->addValue( null, 'error', $e->getMessage() );

			return;
		}

		$this->getResult()->addValue( null, 'success', true );
		$this->getResult()->addValue( null, 'processId', $processId );
	}

}
