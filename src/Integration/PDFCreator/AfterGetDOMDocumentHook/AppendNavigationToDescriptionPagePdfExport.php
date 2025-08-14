<?php

namespace CognitiveProcessDesigner\Integration\PDFCreator\AfterGetDOMDocumentHook;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use DOMDocument;
use DOMException;
use MediaWiki\Extension\PDFCreator\IPreProcessor;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\PageContext;

class AppendNavigationToDescriptionPagePdfExport implements IPreProcessor {

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param CpdElementConnectionUtil $connectionUtil
	 */
	public function __construct(
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly CpdElementConnectionUtil $connectionUtil
	) {
	}

	/**
	 * Insert navigation
	 *
	 * @param DOMDocument $dom
	 * @param PageContext $context
	 *
	 * @return void
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 * @throws CpdInvalidContentException
	 * @throws CpdInvalidNamespaceException
	 * @throws CpdXmlProcessingException
	 * @throws DOMException
	 */
	public function onPDFCreatorAfterGetDOMDocument( DOMDocument $dom, PageContext $context ): void {
		$title = $context->getTitle();
		if (
			!$this->descriptionPageUtil->isDescriptionPage( $title ) &&
			$title->getNamespace() !== NS_PROCESS
		) {
			return;
		}

		$revId = $context->getRequest()->getValues( 'oldId' );

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

	/**
	 * @inheritDoc
	 */
	public function execute(
		array &$pages,
		array &$images,
		array &$attachments,
		ExportContext $context,
		string $module = '',
		$params = []
	): void {
		$imagesPath = dirname( __DIR__, 4 ) . '/resources/img';
		$imageNames = [
			'start-incoming.png',
			'end-outgoing.png',
			'task-incoming.png',
			'task-outgoing.png'
		];

		foreach ( $imageNames as $name ) {
			$images[ $name ] = "$imagesPath/$name";
		}
	}
}
