<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\HookHandler\BpmnTag;
use CognitiveProcessDesigner\HookHandler\ModifyDescriptionPage;
use CognitiveProcessDesigner\RevisionLookup\IRevisionLookup;
use Content;
use DOMDocument;
use File;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Config\Config;
use MediaWiki\Content\ContentHandler;
use MediaWiki\Content\JsonContent;
use MediaWiki\Content\TextContent;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MWContentSerializationException;
use MWUnknownContentModelException;
use RepoGroup;
use TypeError;
use Wikimedia\Rdbms\ILoadBalancer;
use WikiPage;

class CpdDiagramPageUtil {

	public const CPD_SVG_FILE_EXTENSION = '.cpd.svg';

	/**
	 * @param TitleFactory $titleFactory
	 * @param WikiPageFactory $wikiPageFactory
	 * @param RepoGroup $repoGroup
	 * @param Config $config
	 * @param ILoadBalancer $loadBalancer
	 * @param LinkRenderer $linkRenderer
	 * @param IRevisionLookup $lookup
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory,
		private readonly WikiPageFactory $wikiPageFactory,
		private readonly RepoGroup $repoGroup,
		private readonly Config $config,
		private readonly ILoadBalancer $loadBalancer,
		private readonly LinkRenderer $linkRenderer,
		private readonly IRevisionLookup $lookup
	) {
	}

	/**
	 * @param Title $title
	 *
	 * @return array
	 * @throws CpdInvalidNamespaceException
	 */
	public static function getLanesFromTitle( Title $title ): array {
		if ( $title->getNamespace() !== NS_PROCESS ) {
			throw new CpdInvalidNamespaceException( 'Page not in CPD namespace' );
		}

		$lanes = explode( '/', $title->getBaseText() );

		// Remove process
		array_shift( $lanes );

		return $lanes;
	}

	/**
	 * @param string $process
	 *
	 * @return WikiPage
	 * @throws CpdInvalidArgumentException
	 */
	public function getDiagramPage( string $process ): WikiPage {
		if ( empty( $process ) ) {
			throw new CpdInvalidArgumentException( 'Process name cannot be empty' );
		}

		try {
			return $this->wikiPageFactory->newFromTitle( Title::makeTitle( NS_PROCESS, $process ) );
		} catch ( TypeError $e ) {
			throw new CpdInvalidArgumentException( 'Diagram page not found' );
		}
	}

	/**
	 * @param string $process
	 *
	 * @return RevisionRecord|null
	 * @throws CpdInvalidArgumentException
	 */
	public function getStableRevision( string $process ): ?RevisionRecord {
		$diagramPage = $this->getDiagramPage( $process );

		return $this->lookup->getLastStableRevision( $diagramPage );
	}

	/**
	 * @param PageReference $pageRef
	 *
	 * @return string
	 * @throws CpdInvalidNamespaceException
	 */
	public static function getProcess( PageReference $pageRef ): string {
		if ( $pageRef->getNamespace() !== NS_PROCESS ) {
			throw new CpdInvalidNamespaceException( 'Page not in CPD namespace' );
		}

		return explode( '/', $pageRef->getText() )[0];
	}

	/**
	 * @param string $process
	 * @param int|null $revId
	 *
	 * @return string
	 * @throws CpdInvalidContentException
	 * @throws CpdInvalidArgumentException
	 */
	public function getXml( string $process, ?int $revId = null ): string {
		if ( $revId ) {
			$revision = $this->lookup->getRevisionById( $revId );

			if ( !$revision ) {
				return '';
			}

			$content = $revision->getContent( 'main' );

			if ( !$content ) {
				return '';
			}
		} else {
			$diagramPage = $this->getDiagramPage( $process );

			if ( !$diagramPage->exists() ) {
				return '';
			}

			$content = $diagramPage->getContent();
		}

		if ( !$content ) {
			return '';
		}

		$this->validateContent( $content );

		return $content->getText();
	}

	/**
	 * @param string $process
	 * @param User $user
	 * @param string $xml
	 * @param File|null $svgFile
	 *
	 * @return WikiPage
	 * @throws CpdInvalidArgumentException
	 * @throws MWContentSerializationException
	 * @throws MWUnknownContentModelException
	 */
	public function createOrUpdateDiagramPage(
		string $process,
		User $user,
		string $xml,
		?File $svgFile = null
	): WikiPage {
		$domxml = new DOMDocument( '1.0' );
		$domxml->preserveWhiteSpace = false;
		$domxml->formatOutput = true;
		$domxml->loadXML( $xml );
		$formattedXml = $domxml->saveXML();

		$diagramPage = $this->getDiagramPage( $process );
		$content = ContentHandler::makeContent(
			$formattedXml,
			$diagramPage->getTitle(),
			CognitiveProcessDesignerContent::MODEL
		);

		$updater = $diagramPage->newPageUpdater( $user );
		$updater->setContent( SlotRecord::MAIN, $content );
		$updater->setContent(
			CONTENT_SLOT_CPD_PROCESS_META,
			$this->createSvgMetaContent( $process, $diagramPage, $svgFile )
		);

		$comment = Message::newFromKey( 'cpd-api-save-diagram-update-comment' );
		$commentStore = CommentStoreComment::newUnsavedComment( $comment );
		$updater->saveRevision( $commentStore, $diagramPage->exists() ? EDIT_UPDATE : EDIT_NEW );

		if ( !$updater->wasSuccessful() ) {
			throw new CpdInvalidArgumentException( "Failed to save diagram page for process $process" );
		}

		return $diagramPage;
	}

	/**
	 * @param string $process
	 *
	 * @return Title
	 */
	public function getSvgFilePage( string $process ): Title {
		$process = str_replace( ':', '_', $process );
		return $this->titleFactory->makeTitle( NS_FILE, $process . self::CPD_SVG_FILE_EXTENSION );
	}

	/**
	 * @param string $process
	 * @param int|null $revId
	 *
	 * @return File|null
	 * @throws CpdInvalidArgumentException
	 */
	public function getSvgFile( string $process, ?int $revId = null ): ?File {
		$svgFilePage = $this->getSvgFilePage( $process );

		if ( !$svgFilePage->exists() ) {
			return null;
		}

		$options = [];
		$revision = null;
		if ( $revId ) {
			$revision = $this->lookup->getRevisionById( $revId );
		}

		if ( $revision && !$revision->isCurrent() ) {
			$meta = $this->getMetaForPage( $this->getDiagramPage( $process ), $revision );

			if ( array_key_exists( 'cpd-svg-ts', $meta ) ) {
				if ( $meta['cpd-svg-ts'] ) {
					$options['time'] = $meta['cpd-svg-ts'];
				} else {
					// Set to first revision timestamp
					$revision = $this->lookup->getFirstRevision( $svgFilePage );
					if ( $revision ) {
						$options['time'] = $revision->getTimestamp();
					}
				}
			}
		}

		try {
			$file = $this->repoGroup->findFile( $svgFilePage, $options );
		} catch ( TypeError $e ) {
			return null;
		}

		if ( !$file ) {
			return null;
		}

		return $file;
	}

	/**
	 * @param Title $title
	 *
	 * @return void
	 * @throws CpdInvalidNamespaceException
	 */
	public function validateNamespace( Title $title ): void {
		if ( $title->getNamespace() !== NS_PROCESS ) {
			throw new CpdInvalidNamespaceException( 'CPD page not in correct namespace' );
		}
	}

	/**
	 * @param Content|null $content
	 *
	 * @return void
	 * @throws CpdInvalidContentException
	 */
	public function validateContent( Content|null $content ): void {
		if ( !( $content instanceof TextContent ) ) {
			throw new CpdInvalidContentException( 'Process page does not have content' );
		}

		$xml = $content->getText();
		if ( empty( $xml ) ) {
			throw new CpdInvalidContentException( 'Process page does not have content' );
		}

		$domXml = new DOMDocument( '1.0' );
		if ( !$domXml->loadXML( $xml ) ) {
			throw new CpdInvalidContentException( 'Process page does not have valid xml' );
		}
	}

	/**
	 * @param string $process
	 * @param ParserOutput|OutputPage $output
	 * @param bool $isModeler
	 *
	 * @return void
	 */
	public function addOutputDependencies(
		string $process,
		ParserOutput|OutputPage $output,
		bool $isModeler = false
	): void {
		$this->setJsConfigVars( $output, $process );

		$modules = [];
		$styles = [];

		if ( $isModeler ) {
			$modules[] = 'ext.cpd.modeler';
			$output->addModules( $modules );

			return;
		}

		$modules[] = 'ext.cpd.viewer';

		if ( ExtensionRegistry::getInstance()->isLoaded( "SyntaxHighlight" ) ) {
			$styles[] = 'ext.pygments';
			$modules[] = 'ext.pygments.view';
		}

		$output->addModuleStyles( $styles );
		$output->addModules( $modules );
	}

	/**
	 * @param string $process
	 *
	 * @return string[]
	 */
	public function getDiagramUsageLinks( string $process ): array {
		// Sanitize the process parameter as db key. Replace spaces with underscores.
		$process = str_replace( ' ', '_', $process );

		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$rows = $dbr->select(
			'page_props',
			[ 'pp_page' ],
			[
				"pp_value LIKE '%:\"$process\";%'",
				"pp_propname" => BpmnTag::PROCESS_PROP_NAME
			],
			__METHOD__
		);

		$links = [];
		foreach ( $rows as $row ) {
			$title = Title::newFromID( $row->pp_page );
			$links[] = $this->linkRenderer->makeLink(
				$title,
				$title->getText()
			);
		}

		return $links;
	}

	/**
	 * @param string $process
	 *
	 * @return array
	 * @throws CpdInvalidArgumentException
	 */
	public function getMeta( string $process ): array {
		$page = $this->getDiagramPage( $process );

		return $this->getMetaForPage( $page, null );
	}

	/**
	 * @param WikiPage $page
	 * @param RevisionRecord|null $forRevision
	 *
	 * @return array
	 */
	private function getMetaForPage( WikiPage $page, ?RevisionRecord $forRevision ): array {
		$forRevision = $forRevision ?? $this->lookup->getRevisionByTitle( $page->getTitle() );
		if ( !$forRevision ) {
			return [];
		}
		if ( !$forRevision->hasSlot( CONTENT_SLOT_CPD_PROCESS_META ) ) {
			return [];
		}
		$content = $forRevision->getContent( CONTENT_SLOT_CPD_PROCESS_META );
		if ( !( $content instanceof JsonContent ) ) {
			return [];
		}
		$json = $content->getText();

		return json_decode( $json, true ) ?? [];
	}

	/**
	 * @param string $process
	 * @param WikiPage $diagramPage
	 * @param File|null $svgFile
	 *
	 * @return JsonContent
	 */
	private function createSvgMetaContent(
		string $process,
		WikiPage $diagramPage,
		?File $svgFile = null
	): JsonContent {
		$meta = [
			'cpd-svg-ts' => null,
			'cpd-svg-sha1' => null,
		];

		if ( $svgFile ) {
			$svgFilePage = $this->getSvgFilePage( $process );
			$svgFileRevision = $this->lookup->getRevisionByTitle( $svgFilePage );

			if ( $svgFileRevision ) {
				if ( $diagramPage->exists() ) {
					$meta = array_merge( $this->getMetaForPage( $diagramPage, null ), [
						'cpd-svg-ts' => $svgFileRevision->getTimestamp(),
						'cpd-svg-sha1' => $svgFile->getSha1(),
					] );
				} else {
					$meta = [
						'cpd-svg-ts' => $svgFileRevision->getTimestamp(),
						'cpd-svg-sha1' => $svgFile->getSha1(),
					];
				}
			}
		}

		return new JsonContent( json_encode( $meta ) );
	}

	/**
	 * @param ParserOutput|OutputPage $output
	 * @param string $process
	 *
	 * @return void
	 */
	private function setJsConfigVars(
		ParserOutput|OutputPage $output,
		string $process,
	): void {
		if ( $output instanceof OutputPage ) {
			$output->addJsConfigVars( 'cpdProcess', $process );
			$output->addJsConfigVars( 'cpdProcessNamespace', NS_PROCESS );
			$output->addJsConfigVars(
				'cpdReturnToQueryParam',
				ModifyDescriptionPage::RETURN_TO_QUERY_PARAM
			);
			$output->addJsConfigVars(
				'cpdRevisionQueryParam',
				ModifyDescriptionPage::REVISION_QUERY_PARAM
			);

			if ( $this->config->has( 'CPDDedicatedSubpageTypes' ) ) {
				$types = $this->config->get( 'CPDDedicatedSubpageTypes' );
				// Ensure compatibility with appendJsConfigVar
				$compat = [];
				foreach ( $types as $type ) {
					$compat[ $type ] = true;
				}

				$output->addJsConfigVars( 'cpdDedicatedSubpageTypes', $compat );
			}

			return;
		}

		$output->appendJsConfigVar( 'cpdProcesses', $process );
		$output->setJsConfigVar( 'cpdProcessNamespace', NS_PROCESS );
		$output->setJsConfigVar(
			'cpdReturnToQueryParam',
			ModifyDescriptionPage::RETURN_TO_QUERY_PARAM
		);
		$output->setJsConfigVar(
			'cpdRevisionQueryParam',
			ModifyDescriptionPage::REVISION_QUERY_PARAM
		);

		if ( $this->config->has( 'CPDDedicatedSubpageTypes' ) ) {
			foreach ( $this->config->get( 'CPDDedicatedSubpageTypes' ) as $type ) {
				$output->appendJsConfigVar( 'cpdDedicatedSubpageTypes', $type );
			}
		}
	}
}
