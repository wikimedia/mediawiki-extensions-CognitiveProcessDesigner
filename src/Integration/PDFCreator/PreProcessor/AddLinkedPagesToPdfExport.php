<?php

namespace CognitiveProcessDesigner\Integration\PDFCreator\PreProcessor;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use Exception;
use MediaWiki\Extension\PDFCreator\Factory\ExportPageFactory;
use MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory;
use MediaWiki\Extension\PDFCreator\Factory\TemplateProviderFactory;
use MediaWiki\Extension\PDFCreator\IPreProcessor;
use MediaWiki\Extension\PDFCreator\ISpecificationAware;
use MediaWiki\Extension\PDFCreator\IWorkspaceAware;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\ExportSpecification;
use MediaWiki\Title\TitleFactory;

class AddLinkedPagesToPdfExport implements IPreProcessor, ISpecificationAware, IWorkspaceAware {

	/** @var ExportSpecification|null */
	private ExportSpecification|null $specification = null;

	/** @var string|null */
	private string|null $workspace = null;

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdXmlProcessor $xmlProcessor
	 * @param TitleFactory $titleFactory
	 * @param ExportPageFactory $exportPageFactory
	 * @param PageSpecFactory $pageSpecFactory
	 * @param TemplateProviderFactory $templateProviderFactory
	 */
	public function __construct(
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdXmlProcessor $xmlProcessor,
		private readonly TitleFactory $titleFactory,
		private readonly ExportPageFactory $exportPageFactory,
		private readonly PageSpecFactory $pageSpecFactory,
		private readonly TemplateProviderFactory $templateProviderFactory
	) {
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
		if ( !$this->specification || !$this->workspace ) {
			return;
		}

		$specParams = $this->specification->getParams();
		if ( !isset( $specParams['mode'] ) ) {
			return;
		}

		$mode = $specParams['mode'];
		if ( $mode !== 'pageWithLinkedPages' ) {
			return;
		}

		$templateName = $this->specification->getParams()['template'] ?? 'StandardPDF';
		$templateProvider = $this->templateProviderFactory->getTemplateProviderFor( $templateName );
		$template = $templateProvider->getTemplate( $context, $templateName );
		$hasDescriptionPage = false;

		foreach ( $pages as $page ) {
			$pageParams = $page->getParams();

			// Skip if page is not a process page or a description page
			try {
				$title = $this->titleFactory->newFromDBkey( $page->getPrefixedDBKey() );
				$process = $this->diagramPageUtil->getProcess( $title );
				$xml = $this->diagramPageUtil->getXml( $process, $pageParams['revId'] ?? null );
				$elements = $this->xmlProcessor->createElements( $process, $xml );
			} catch ( Exception $e ) {
				continue;
			}

			$this->findAndRemoveSvgFilePage( $process, $pages );

			foreach ( $elements as $element ) {
				$descriptionPage = $element->getDescriptionPage();
				if ( !$descriptionPage ) {
					continue;
				}

				// Skip if already added
				if ( $descriptionPage->getPrefixedDBkey() === $page->getPrefixedDBkey() ) {
					continue;
				}

				// Skip redirects
				if ( $descriptionPage->isRedirect() ) {
					continue;
				}

				if ( !$hasDescriptionPage ) {
					$hasDescriptionPage = true;
				}

				$pageSpec = $this->pageSpecFactory->newFromSpec(
					[
						'type' => 'page',
						'label' => $descriptionPage->getText(),
						'target' => $descriptionPage->getPrefixedDBkey(),
					],
					$params
				);

				if ( $pageSpec === null ) {
					continue;
				}

				$pages[] = $this->exportPageFactory->getPageFromSpec(
					$pageSpec,
					$template,
					$context,
					$this->workspace
				);
			}
		}

		// Finally, add navigation icons
		if ( $hasDescriptionPage ) {
			AddNavigationIconsToPdfExport::addIcons( $images );
		}
	}

	/**
	 * @param ExportSpecification $specification
	 *
	 * @return void
	 */
	public function setExportSpecification( ExportSpecification $specification ): void {
		$this->specification = $specification;
	}

	/**
	 * @param string $workspace
	 *
	 * @return void
	 */
	public function setWorkspace( string $workspace ): void {
		$this->workspace = $workspace;
	}

	/**
	 * Find and remove the SVG file page from the list of pages to be exported.
	 *
	 * @param string $process
	 * @param array &$pages
	 *
	 * @return void
	 */
	private function findAndRemoveSvgFilePage( string $process, array &$pages ): void {
		$svgFile = $this->diagramPageUtil->getSvgFilePage( $process );
		foreach ( $pages as $key => $page ) {
			if ( $page->getPrefixedDBKey() === $svgFile->getPrefixedDBKey() ) {
				unset( $pages[$key] );
				$pages = array_values( $pages );
				break;
			}
		}
	}
}
