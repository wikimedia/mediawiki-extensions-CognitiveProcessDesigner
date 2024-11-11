<?php

namespace CognitiveProcessDesigner\Content;

use CognitiveProcessDesigner\Action\EditDiagramAction;
use CognitiveProcessDesigner\Action\EditDiagramXmlAction;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use Content;
use Html;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use Message;
use ParserOutput;
use TextContentHandler;
use Wikimedia\Rdbms\ILoadBalancer;

class CognitiveProcessDesignerContentHandler extends TextContentHandler {
	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/** @var ILoadBalancer */
	private ILoadBalancer $loadBalancer;

	/** @var LinkRenderer */
	private LinkRenderer $linkRenderer;

	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = CognitiveProcessDesignerContent::MODEL ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_XML ] );

		$services = MediaWikiServices::getInstance();
		$this->diagramPageUtil = $services->getService( 'CpdDiagramPageUtil' );
		$this->loadBalancer = $services->getService( 'DBLoadBalancer' );
		$this->linkRenderer = $services->getService( 'LinkRenderer' );
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
	 * @throws CpdInvalidNamespaceException
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	): void {
		$page = $cpoParams->getPage();

		$output = MediaWikiServices::getInstance()->getParser()->parse(
			null,
			$page,
			$cpoParams->getParserOptions()
		);

		$this->diagramPageUtil->setJsConfigVars( $output, $page->getDBkey() );
		$output->addModules( [ 'ext.cognitiveProcessDesigner.viewer' ] );

		$process = CpdDiagramPageUtil::getProcessFromTitle( $page );
		$html = $this->getDiagramUsageHtml( $process );

		$output->setText( $html );
	}

	/**
	 * @param string $process
	 *
	 * @return string
	 */
	private function getDiagramUsageHtml( string $process ): string {
		$links = $this->diagramPageUtil->getDiagramUsageLinks( $process );

		if ( empty( $links ) ) {
			return '';
		}

		$html = Html::element( 'h2', [], Message::newFromKey( 'cpd-diagram-usage-label' ) );
		$html .= Html::element( 'p', [], Message::newFromKey( 'cpd-diagram-usage-description' ) . ":" );
		$html .= Html::openElement( 'ul' );
		foreach ( $links as $link ) {
			$html .= Html::rawElement( 'li', [], $link );
		}
		$html .= Html::closeElement( 'ul' );

		return $html;
	}
}
