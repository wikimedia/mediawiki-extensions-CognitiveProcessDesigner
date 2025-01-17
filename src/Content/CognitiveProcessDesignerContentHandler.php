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
use TemplateParser;
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
		$parser = MediaWikiServices::getInstance()->getParser();
		$page = $cpoParams->getPage();
		$process = $page->getDBkey();
		$canvasHeight = $this->config->get( 'CPDCanvasProcessHeight' );

		$output = $parser->parse(
			null,
			$page,
			$cpoParams->getParserOptions()
		);

		$this->diagramPageUtil->setJsConfigVars( $output, $process );
		$output->addModules( [ 'ext.cpd.viewer' ] );

		$templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);

		// Embed svg image in the viewer hidden
		$imageFile = $this->diagramPageUtil->getSvgFile( $process );
		$imageDbKey = $imageFile?->getTitle()->getPrefixedDBkey();

		$output->setText( $templateParser->processTemplate(
			'CpdContainer', [
				'process' => $process,
				'showToolbar' => true,
				'width' => '100%',
				'height' => $canvasHeight . 'px',
				'diagramImage' => $imageDbKey ? $parser->recursiveTagParse( "[[$imageDbKey]]" ) : null
			]
		) );
	}
}
