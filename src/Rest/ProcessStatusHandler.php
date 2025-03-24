<?php

namespace CognitiveProcessDesigner\Rest;

use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use MWStake\MediaWiki\Component\ProcessManager\ProcessManager;
use Wikimedia\ParamValidator\ParamValidator;

class ProcessStatusHandler extends SimpleHandler {

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'processId' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => 'string'
			]
		];
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function run() {
		$request = $this->getRequest();
		$processId = $request->getPathParam( 'processId' );

		/** @var ProcessManager $processManager */
		$processManager = MediaWikiServices::getInstance()->getService( 'ProcessManager' );
		$processInfo = $processManager->getProcessInfo( $processId );

		if ( $processInfo === null ) {
			throw new Exception( 'Process does not exist!' );
		}

		return $this->getResponseFactory()->createJson( $processInfo->jsonSerialize() );
	}
}
