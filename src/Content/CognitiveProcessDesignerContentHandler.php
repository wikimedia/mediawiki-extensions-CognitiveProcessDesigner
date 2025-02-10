<?php

namespace CognitiveProcessDesigner\Content;

use CognitiveProcessDesigner\Action\EditDiagramAction;
use CognitiveProcessDesigner\Action\EditDiagramXmlAction;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Config\Config;
use MediaWiki\Content\Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\TextContentHandler;
use MediaWiki\Html\TemplateParser;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use ParserOutput;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CognitiveProcessDesignerContentHandler extends TextContentHandler {
	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/** @var Config */
	private Config $config;

	/** @var RevisionLookup */
	private RevisionLookup $revisionLookup;

	/**
	 * @param string $modelId
	 *
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct( $modelId = CognitiveProcessDesignerContent::MODEL ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_XML ] );

		$services = MediaWikiServices::getInstance();
		$this->diagramPageUtil = $services->getService( 'CpdDiagramPageUtil' );
		$this->revisionLookup = $services->getRevisionLookup();
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
	 *
	 * @throws CpdInvalidContentException
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	): void {
		$parser = MediaWikiServices::getInstance()->getParser();
		$page = $cpoParams->getPage();
		$revisionId = $cpoParams->getRevId();
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
		$revision = $this->revisionLookup->getRevisionById( $revisionId );
		$imageFile = $this->diagramPageUtil->getSvgFile( $process, $revision );
		$imageDbKey = $imageFile?->getTitle()->getPrefixedDBkey();

		$output->setRawText(
			$templateParser->processTemplate(
				'CpdContainer', [
					'process' => $process,
					'revision' => $revisionId,
					'showToolbar' => true,
					'width' => '100%',
					'height' => $canvasHeight . 'px',
					'diagramImage' => $imageDbKey ? $parser->recursiveTagParse( "[[$imageDbKey]]" ) : null
				]
			)
		);
	}
}
