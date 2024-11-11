<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\CpdNavigationConnection;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Linker\LinkRenderer;
use OutputPage;
use TemplateParser;
use Title;

class AddDescriptionPageDiagramNavigationLinks implements OutputPageBeforeHTMLHook {
	public const RETURN_TO_QUERY_PARAM = 'returnto';

	/** @var CpdDescriptionPageUtil */
	private CpdDescriptionPageUtil $descriptionPageUtil;

	/** @var LinkRenderer */
	private LinkRenderer $linkRenderer;

	/** @var TemplateParser */
	private TemplateParser $templateParser;

	/** @var CpdElementConnectionUtil */
	private CpdElementConnectionUtil $connectionUtil;

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param CpdElementConnectionUtil $connectionUtil
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		CpdDescriptionPageUtil $descriptionPageUtil,
		CpdElementConnectionUtil $connectionUtil,
		LinkRenderer $linkRenderer
	) {
		$this->descriptionPageUtil = $descriptionPageUtil;
		$this->linkRenderer = $linkRenderer;
		$this->templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);
		$this->connectionUtil = $connectionUtil;
	}

	/**
	 * @param OutputPage $out
	 * @param string &$text
	 *
	 * @return void
	 * @throws CpdInvalidNamespaceException
	 */
	public function onOutputPageBeforeHTML( $out, &$text ): void {
		$title = $out->getTitle();
		if ( !$title ) {
			return;
		}

		if ( !$this->descriptionPageUtil->isDescriptionPage( $title ) ) {
			return;
		}

		$request = $out->getContext()->getRequest();

		$returnToTitle = Title::newFromText( $request->getVal( self::RETURN_TO_QUERY_PARAM ) );
		if ( $returnToTitle ) {
			$out->addSubtitle(
				"< " . $this->linkRenderer->makeLink(
					$returnToTitle,
					$returnToTitle->getText()
				)
			);
		}

		$text = $this->createNavigation( $this->connectionUtil->getIncomingConnections( $title ) ) . $text;
		$text = $text . $this->createNavigation( $this->connectionUtil->getOutgoingConnections( $title ) );
	}

	/**
	 * @param CpdNavigationConnection[] $connections
	 *
	 * @return string
	 */
	private function createNavigation( array $connections ): string {
		return $this->templateParser->processTemplate(
			'DescriptionPageNavigation', [
				'connections' => array_map( fn( CpdNavigationConnection $connection ) => $connection->toArray(),
					$connections )
			]
		);
	}
}
