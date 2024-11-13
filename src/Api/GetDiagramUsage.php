<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\HookHandler\BpmnTag;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\MediaWikiServices;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class GetDiagramUsage extends ApiBase {

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		CpdDiagramPageUtil $diagramPageUtil
	) {
		parent::__construct( $main, $action );

		$this->diagramPageUtil = $diagramPageUtil;
	}

	/**
	 * @inheritDoc
	 *
	 * @throws ApiUsageException
	 * @throws CpdInvalidArgumentException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$page = $params['page'];
		$title = Title::newFromDBkey( $page );

		try {
			$process = CpdDiagramPageUtil::getProcessFromTitle( $title );
		} catch ( CpdInvalidNamespaceException $e ) {
			$services = MediaWikiServices::getInstance();
			$pageFactory = $services->getWikiPageFactory();
			$page = $pageFactory->newFromTitle( $title );
			$process = $page->getParserOutput()->getPageProperty( BpmnTag::PROCESS_PROP_NAME );
		}

		$result = $this->getResult();
		if ( $process ) {
			$links = $this->diagramPageUtil->getDiagramUsageLinks( $process );
			$result->addValue( null, 'links', $links );
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'page' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
