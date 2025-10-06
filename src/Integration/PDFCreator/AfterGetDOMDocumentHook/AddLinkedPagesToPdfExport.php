<?php

namespace CognitiveProcessDesigner\Integration\PDFCreator\AfterGetDOMDocumentHook;

use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Extension\PDFCreator\Factory\ExportPageFactory;
use MediaWiki\Extension\PDFCreator\Factory\PageSpecFactory;
use MediaWiki\Extension\PDFCreator\Factory\TemplateProviderFactory;
use MediaWiki\Extension\PDFCreator\IPreProcessor;
use MediaWiki\Extension\PDFCreator\ISpecificationAware;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Extension\PDFCreator\Utility\ExportSpecification;
use MediaWiki\Title\TitleFactory;

class AddLinkedPagesToPdfExport implements IPreProcessor, ISpecificationAware {

	/** @var ExportSpecification|null */
	private ExportSpecification|null $specification = null;

	/** @var string|null */
	private string|null $workspace = null;

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param TitleFactory $titleFactory
	 * @param ExportPageFactory $exportPageFactory
	 * @param PageSpecFactory $pageSpecFactory
	 * @param TemplateProviderFactory $templateProviderFactory
	 */
	public function __construct(
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
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

		$mode = $this->specification->getMode();
		if ( $mode !== 'pageWithLinkedPages' ) {
			return;
		}

		$templateName = $this->specification->getParams()['template'] ?? 'StandardPDF';
		$templateProvider = $this->templateProviderFactory->getTemplateProviderFor( $templateName );
		$template = $templateProvider->getTemplate( $context, $templateName );

		foreach ( $pages as $page ) {
			// Skip if page is not a process page or a description page
			try {
				$title = $this->titleFactory->newFromDBkey( $page->getPrefixedDBKey() );
				$process = $this->diagramPageUtil->getProcess( $title );
			} catch ( CpdInvalidNamespaceException $e ) {
				continue;
			}

			$descriptionPages = $this->descriptionPageUtil->findDescriptionPages( $process );

			foreach ( $descriptionPages as $descriptionPage ) {
				// Skip if already added
				if ( $descriptionPage->getPrefixedDBkey() === $page->getPrefixedDBkey() ) {
					continue;
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
}
