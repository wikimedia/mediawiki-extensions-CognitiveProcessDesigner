<?php

namespace CognitiveProcessDesigner\Integration\PDFCreator\AfterGetDOMDocumentHook;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use DOMDocument;
use DOMException;
use DOMXPath;
use MediaWiki\Extension\PDFCreator\Utility\PageContext;

class AppendDiagramImagesToPdfExport {

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 */
	public function __construct(
		private readonly CpdDiagramPageUtil $diagramPageUtil,
	) {
	}

	/**
	 * Embed diagram images into PDF export
	 *
	 * @param DOMDocument $dom
	 * @param PageContext $context
	 *
	 * @return void
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 * @throws CpdInvalidNamespaceException
	 * @throws DOMException
	 */
	public function onPDFCreatorAfterGetDOMDocument( DOMDocument $dom, PageContext $context ): void {
		$xpath = new DOMXPath( $dom );
		$cpdContainers = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' cpd-container ')]" );

		foreach ( $cpdContainers as $container ) {
			$process = $container->getAttribute( "data-process" );
			$revision = (int)$container->getAttribute( "data-revision" );

			if ( !$process || !$revision ) {
				continue;
			}

			$file = $this->diagramPageUtil->getSvgFile( $process, $revision );

			if ( !$file ) {
				continue;
			}

			$fragment = $dom->createDocumentFragment();
			$fragment->appendXML( $this->diagramPageUtil->createSvgFileLinkHtml( $file ) );
			$container->parentNode->replaceChild( $fragment, $container );
		}
	}
}
