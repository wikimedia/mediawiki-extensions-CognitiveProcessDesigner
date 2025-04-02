<?php

use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\DerivativeRequest;
use MediaWiki\Title\Title;

require_once dirname( __DIR__, 3 ) . '/maintenance/Maintenance.php';

class MigrateDiagrams extends Maintenance {

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrate diagrams to new version' );
		$this->addOption(
			'username',
			'Username to use for the migration',
			true
		);
		$this->addOption(
			'password',
			'Password to use for the migration',
			true
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$user = $this->getOption( 'username' );
		$password = $this->getOption( 'password' );

		$testDbKey = 'Migration';

		$services = MediaWikiServices::getInstance();
		$wikiPageFactory = $services->getWikiPageFactory();

		$page = $wikiPageFactory->newFromTitle( Title::newFromDBkey( $testDbKey ) );
		$text = $page->getContent()->getText();

		preg_match( '/\|id=([^|}]*)/', $text, $idMatch );;
		$process = $idMatch[1];
		if ( empty( $process ) ) {
			$this->output( "No process name found." );

			return false;
		}
		$process = str_replace( ":", "_", $process);
		$process = 'foobarfoo1234';

		preg_match( '/<bpmn:definitions[\s\S]*<\/bpmn:definitions>/', $text, $matches );
		if ( !empty( $matches ) ) {
			$xml = $matches[0];
			$xml = str_replace( "/n", "", $xml );
		} else {
			$this->error( "No valid bpmn data found." );

			return false;
		}

		try {
			$saveCpdApiResult = $this->request( [
				'action' => 'cpd-save-diagram',
				'process' => $process,
				'xml' => json_encode( $xml ),
				'token' => $this->getCsrfToken( $user, $password ),
			] );
		} catch ( Exception $e ) {
			$this->error( $e->getMessage() );

			return false;
		}

		$diagramPage = $saveCpdApiResult->getResultData( [ 'diagramPage' ] );
		$warnings = $saveCpdApiResult->getResultData( [ 'saveWarnings' ] );

		if ( !empty( $warnings ) ) {
			$this->output( "Process page created with warnings: $diagramPage  \n" );
			foreach ( $warnings as $warning ) {
				$this->output( "Warning: $warning  \n" );
			}

			return true;
		}

		$this->output( "Process page created: $diagramPage  \n" );

		return true;
	}

	/**
	 * @param string $user
	 * @param string $password
	 *
	 * @return string
	 */
	private function getCsrfToken( string $user, string $password ): string {
		$context = RequestContext::getMain();
		$context->setUser(
			MediaWikiServices::getInstance()->getUserFactory()->newFromName( $user )
		);
		$apiResult = $this->request( [
			'action' => 'query',
			'meta' => 'tokens',
			'type' => 'login',
			'format' => 'json'
		] );
		$loginToken = $apiResult->getResultData(
			[
				'query',
				'tokens',
				'logintoken'
			]
		);
		$this->request( [
			'action' => 'login',
			'lgname' => $user,
			'lgpassword' => $password,
			'lgtoken' => $loginToken
		] );

		return $context->getCsrfTokenSet()->getToken()->toString();
	}

	/**
	 * @param array $data
	 *
	 * @return ApiResult
	 */
	private function request( array $data ): ApiResult {
		$api = new ApiMain(
			new DerivativeRequest(
				RequestContext::getMain()->getRequest(), $data, true
			), true
		);

		$api->execute();

		return $api->getResult();
	}
}

$maintClass = MigrateDiagrams::class;
require_once RUN_MAINTENANCE_IF_MAIN;
