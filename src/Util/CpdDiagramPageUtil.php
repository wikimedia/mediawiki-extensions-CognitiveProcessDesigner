<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\HookHandler\BpmnTag;
use CognitiveProcessDesigner\HookHandler\ModifyDescriptionPage;
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
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MWContentSerializationException;
use MWUnknownContentModelException;
use RepoGroup;
use Wikimedia\Rdbms\ILoadBalancer;
use WikiPage;

class CpdDiagramPageUtil {
	/** @var TitleFactory */
	private TitleFactory $titleFactory;

	/** @var Config */
	private Config $config;

	/** @var WikiPageFactory */
	private WikiPageFactory $wikiPageFactory;

	/** @var RepoGroup */
	private RepoGroup $repoGroup;

	/** @var ILoadBalancer */
	private ILoadBalancer $loadBalancer;

	/** @var LinkRenderer */
	private LinkRenderer $linkRenderer;

	/**
	 * @param TitleFactory $titleFactory
	 * @param WikiPageFactory $wikiPageFactory
	 * @param RepoGroup $repoGroup
	 * @param Config $config
	 * @param ILoadBalancer $loadBalancer
	 * @param LinkRenderer $linkRenderer
	 * @param RevisionLookup $revisionLookup
	 */
	public function __construct(
		TitleFactory $titleFactory,
		WikiPageFactory $wikiPageFactory,
		RepoGroup $repoGroup,
		Config $config,
		ILoadBalancer $loadBalancer,
		LinkRenderer $linkRenderer,
		private readonly RevisionLookup $revisionLookup
	) {
		$this->titleFactory = $titleFactory;
		$this->config = $config;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->repoGroup = $repoGroup;
		$this->loadBalancer = $loadBalancer;
		$this->linkRenderer = $linkRenderer;
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
	 */
	public function getDiagramPage( string $process ): WikiPage {
		return $this->wikiPageFactory->newFromTitle( Title::newFromText( $process, NS_PROCESS ) );
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
	 * @param User $user
	 * @param string $xml
	 * @param File $svgFile
	 *
	 * @return WikiPage
	 * @throws MWContentSerializationException
	 * @throws MWUnknownContentModelException
	 */
	public function createOrUpdateDiagramPage(
		string $process,
		User $user,
		string $xml,
		File $svgFile
	): WikiPage {
		$diagramPage = $this->getDiagramPage( $process );

		$updater = $diagramPage->newPageUpdater( $user );

		$domxml = new DOMDocument( '1.0' );
		$domxml->preserveWhiteSpace = false;
		$domxml->formatOutput = true;
		$domxml->loadXML( $xml );
		$formattedXml = $domxml->saveXML();

		$content = ContentHandler::makeContent(
			$formattedXml,
			$diagramPage->getTitle(),
			CognitiveProcessDesignerContent::MODEL
		);
		$updater->setContent( SlotRecord::MAIN, $content );
		if ( $diagramPage->exists() ) {
			$metaContent = $this->getUpdatedMetaContent( $diagramPage, [
				'cpd-svg-ts' => $svgFile->getTimestamp(),
				'cpd-svg-sha1' => $svgFile->getSha1(),
			] );
		} else {
			$metaContent = new JsonContent( json_encode( [
				'cpd-svg-ts' => $svgFile->getTimestamp(),
				'cpd-svg-sha1' => $svgFile->getSha1(),
			] ) );
		}
		$updater->setContent( CONTENT_SLOT_CPD_PROCESS_META, $metaContent );
		$comment = Message::newFromKey( 'cpd-api-save-diagram-update-comment' );
		$commentStore = CommentStoreComment::newUnsavedComment( $comment );
		$updater->saveRevision( $commentStore, $diagramPage->exists() ? EDIT_UPDATE : EDIT_NEW );

		return $diagramPage;
	}

	/**
	 * @param string $process
	 *
	 * @return Title
	 */
	public function getSvgFilePage( string $process ): Title {
		return $this->titleFactory->newFromText( $process . '.svg', NS_FILE );
	}

	/**
	 * @param string $process
	 * @param RevisionRecord|null $revision
	 *
	 * @return File|null
	 */
	public function getSvgFile( string $process, RevisionRecord $revision = null ): ?File {
		$svgFilePage = $this->getSvgFilePage( $process );

		$options = [];
		if ( $revision && !$revision->isCurrent() ) {
			$meta = $this->getMetaForPage( $this->getDiagramPage( $process ), $revision );
			if ( $meta['cpd-svg-sha1'] ) {
				$options['sha1'] = $meta['cpd-svg-sha1'];
			}
		}

		$file = $this->repoGroup->findFile( $svgFilePage, $options );
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
	 * @param ParserOutput|OutputPage $output
	 * @param string $process
	 *
	 * @return void
	 */
	public function setJsConfigVars(
		ParserOutput|OutputPage $output,
		string $process,
	): void {
		if ( $output instanceof OutputPage ) {
			$output->addJsConfigVars( 'cpdProcess', $process );
		} else {
			$output->appendJsConfigVar( 'cpdProcesses', $process );
		}

		$output->addJsConfigVars( 'cpdProcessNamespace', NS_PROCESS );

		if ( $this->config->has( 'CPDLaneTypes' ) ) {
			$output->addJsConfigVars( 'cpdLaneTypes', $this->config->get( 'CPDLaneTypes' ) );
		}
		if ( $this->config->has( 'CPDDedicatedSubpageTypes' ) ) {
			$output->addJsConfigVars(
				'cpdDedicatedSubpageTypes',
				$this->config->get( 'CPDDedicatedSubpageTypes' )
			);
		}

		$output->addJsConfigVars(
			'cpdReturnToQueryParam',
			ModifyDescriptionPage::RETURN_TO_QUERY_PARAM
		);
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
		$forRevision = $forRevision ?? $this->revisionLookup->getRevisionByTitle( $page->getTitle() );
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
	 * @param WikiPage $diagramPage
	 * @param array $newData
	 *
	 * @return JsonContent
	 */
	private function getUpdatedMetaContent( WikiPage $diagramPage, array $newData ): JsonContent {
		$meta = $this->getMetaForPage( $diagramPage, null );
		$meta = array_merge( $meta, $newData );

		return new JsonContent( json_encode( $meta ) );
	}

}
