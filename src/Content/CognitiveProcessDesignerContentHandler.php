<?php

namespace CognitiveProcessDesigner\Content;

use CognitiveProcessDesigner\Action\EditDiagramAction;
use CognitiveProcessDesigner\Action\EditDiagramXmlAction;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use Config;
use Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use TextContentHandler;

class CognitiveProcessDesignerContentHandler extends TextContentHandler {
	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/** @var Config */
	private Config $config;

	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = CognitiveProcessDesignerContent::MODEL ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_XML ] );

		$services = MediaWikiServices::getInstance();
		$this->diagramPageUtil = $services->getService( 'CpdDiagramPageUtil' );
		$this->config = $services->getService( 'MainConfig' );
	}

	/**
	 * @return string
	 */
	public function getContentClass(): string {
		return CognitiveProcessDesignerContent::class;
	}

	/**
	 * @return array
	 */
	public function getActionOverrides(): array {
		return [
			'edit' => EditDiagramAction::class,
			'editxml' => EditDiagramXmlAction::class,
		];
	}

	/**
	 * @param Content $content
	 * @param ContentParseParams $cpoParams
	 * @param ParserOutput &$output
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	): void {
		$page = $cpoParams->getPage();
		$canvasHeight = $this->config->get( 'CPDCanvasProcessHeight' );

		$output = MediaWikiServices::getInstance()->getParser()->parse(
			null,
			$page,
			$cpoParams->getParserOptions()
		);

		$this->diagramPageUtil->setJsConfigVars( $output, $page->getDBkey(), $canvasHeight );
		$output->addModules( [ 'ext.cpd.viewer' ] );
	}
}
