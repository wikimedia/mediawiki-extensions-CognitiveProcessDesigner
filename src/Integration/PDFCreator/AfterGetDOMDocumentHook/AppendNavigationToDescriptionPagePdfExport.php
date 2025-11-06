<?php

namespace CognitiveProcessDesigner\Integration\PDFCreator\AfterGetDOMDocumentHook;

use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use DOMDocument;
use DOMException;
use MediaWiki\Extension\PDFCreator\Utility\PageContext;

class AppendNavigationToDescriptionPagePdfExport {

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param CpdElementConnectionUtil $connectionUtil
	 */
	public function __construct(
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly CpdElementConnectionUtil $connectionUtil,
	) {
	}

	/**
	 * Insert navigation
	 *
	 * @param DOMDocument $dom
	 * @param PageContext $context
	 *
	 * @return void
	 * @throws DOMException
	 */
	public function onPDFCreatorAfterGetDOMDocument( DOMDocument $dom, PageContext $context ): void {
		if ( !$this->descriptionPageUtil->isDescriptionPage( $context->getTitle() ) ) {
			return;
		}

		$revId = $context->getRequest()->getVal( 'oldId' );

		$navigationHtml = $this->connectionUtil->createNavigationHtml(
			$context->getTitle(),
			$revId ? (int)$revId : null
		);

		$body = $dom->getElementsByTagName( 'body' )->item( 0 );

		$fragment = $dom->createDocumentFragment();
		$fragment->appendXML( $navigationHtml );

		// Create the wrapper div
		$wrapperDiv = $dom->createElement( 'div' );
		$wrapperDiv->appendChild( $fragment );

		// Move all existing body children into the wrapper div
		while ( $body->firstChild ) {
			$wrapperDiv->appendChild( $body->firstChild );
		}

		$body->appendChild( $wrapperDiv );
	}
}
