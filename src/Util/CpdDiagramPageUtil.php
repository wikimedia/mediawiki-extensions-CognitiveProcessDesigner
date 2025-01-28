<?php

namespace CognitiveProcessDesigner\Util;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\HookHandler\BpmnTag;
use CognitiveProcessDesigner\HookHandler\ModifyDescriptionPage;
use DOMDocument;
use File;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Config\Config;
use MediaWiki\Content\ContentHandler;
use MediaWiki\Content\TextContent;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use MWContentSerializationException;
use MWException;
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
	 */
	public function __construct(
		TitleFactory $titleFactory,
		WikiPageFactory $wikiPageFactory,
		RepoGroup $repoGroup,
		Config $config,
		ILoadBalancer $loadBalancer,
		LinkRenderer $linkRenderer
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
	 * @return string
	 * @throws CpdInvalidNamespaceException
	 */
	public static function getProcessFromTitle( Title $title ): string {
		if ( $title->getNamespace() !== NS_PROCESS ) {
			throw new CpdInvalidNamespaceException( 'Page not in CPD namespace' );
		}

		return explode( '/', $title->getText() )[0];
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
	 * @param string $process
	 * @param User $user
	 * @param string $xml
	 *
	 * @return WikiPage
	 * @throws MWContentSerializationException
	 * @throws MWException
	 */
	public function createOrUpdateDiagramPage( string $process, User $user, string $xml ): WikiPage {
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
	 *
	 * @return File|null
	 */
	public function getSvgFile( string $process ): ?File {
		$svgFilePage = $this->getSvgFilePage( $process );
		$file = $this->repoGroup->findFile( $svgFilePage );

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
	 * @param WikiPage $page
	 *
	 * @return void
	 * @throws CpdInvalidContentException
	 */
	public function validateContent( WikiPage $page ): void {
		if ( !$page->exists() ) {
			throw new CpdInvalidContentException( 'Process page does not exist' );
		}

		$content = $page->getContent();
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
}
