<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Linker\LinkRenderer;
use OutputPage;
use Title;

class AddDescriptionPageDiagramNavigationLinks implements OutputPageBeforeHTMLHook {
	public const RETURN_TO_QUERY_PARAM = 'returnto';

	/** @var CpdDescriptionPageUtil */
	private CpdDescriptionPageUtil $descriptionPageUtil;

	/** @var LinkRenderer */
	private LinkRenderer $linkRenderer;

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		CpdDescriptionPageUtil $descriptionPageUtil,
		LinkRenderer $linkRenderer
	) {
		$this->descriptionPageUtil = $descriptionPageUtil;
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 * @param OutputPage $out
	 * @param string &$text
	 *
	 * @return void
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

		$text = $this->createLinkList( $this->descriptionPageUtil->getIncomingPages( $title ) ) . $text;
		$text = $text . $this->createLinkList( $this->descriptionPageUtil->getOutgoingPages( $title ) );
	}

	/**
	 * @param array $links
	 *
	 * @return string
	 */
	private function createLinkList( array $links ): string {
		$html = '<ul>';
		foreach ( $links as $link ) {
			$html .= '<li>';
			$html .= $this->linkRenderer->makeLink(
				$link,
				$link->getText()
			);
			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}
}
