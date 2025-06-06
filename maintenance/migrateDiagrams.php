<?php

use CognitiveProcessDesigner\CpdElement;
use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdSaveDescriptionPagesUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Content\ContentHandler;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\SlotRecord;
use SMW\DIProperty;
use SMW\StoreFactory;

require_once dirname( __DIR__, 3 ) . '/maintenance/Maintenance.php';

class MigrateDiagrams extends LoggedUpdateMaintenance {

	private const LEGACY_BPMN_ID_PROPERTY = 'Id';

	/** @var WikiPageFactory */
	private WikiPageFactory $wikiPageFactory;

	/** @var array */
	private array $dedicatedSubpageTypes;

	/** @var CpdSaveDescriptionPagesUtil */
	private CpdSaveDescriptionPagesUtil $saveDescriptionPagesUtil;

	/** @var CpdXmlProcessor */
	private CpdXmlProcessor $xmlProcessor;

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/** @var MediaWiki\User\User */
	private MediaWiki\User\User $anonymousUser;

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Migrate BPMN diagrams to new version of Cognitive Process Designer' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'migrate-cognitive-process-designer-diagrams';
	}

	/**
	 * @inheritDoc
	 */
	public function doDBUpdates() {
		if ( defined( 'MW_QUIBBLE_CI' ) ) {
			// This code required Extension:SemanticMediaWiki, which is not available in
			// Wikimedia CI
			return true;
		}
		$services = MediaWikiServices::getInstance();
		$this->diagramPageUtil = $services->getService( 'CpdDiagramPageUtil' );
		$this->xmlProcessor = $services->getService( 'CpdXmlProcessor' );
		$this->saveDescriptionPagesUtil = $services->getService( 'CpdSaveDescriptionPagesUtil' );
		$this->wikiPageFactory = $services->getWikiPageFactory();

		$userFactory = $services->getUserFactory();
		$this->anonymousUser = $userFactory->newAnonymous();

		// Set dedicated subpage types for adding missing labels to elements
		$this->dedicatedSubpageTypes = [];
		$config = $services->getMainConfig();
		if ( $config->has( 'CPDDedicatedSubpageTypes' ) ) {
			$this->dedicatedSubpageTypes = $config->get( 'CPDDedicatedSubpageTypes' );
		}

		$legacyPages = $this->findAllLegacyBpmnPages();
		$pagesCount = count( $legacyPages );
		$this->outputLine( "Start migrating $pagesCount cpd diagrams" );
		foreach ( $legacyPages as $count => $page ) {
			$count += 1;
			$countString = "($count/$pagesCount)";
			$this->outputLine( "$countString [Migrating] $page" );
			try {
				$newDiagramPage = $this->migrateLegacyDiagram( $page );
				$this->makeRedirect( $page, $newDiagramPage );
				$this->outputLine( "$countString [Success] Process page created: $newDiagramPage" );
			} catch ( Exception $e ) {
				$errorMessage = $e->getMessage();
				$this->error( "$countString [Error] $errorMessage" );
			}
		}

		$this->outputLine( "Done migrating cpd diagrams" );
		$this->outputLine( "" );

		return true;
	}

	/**
	 * @param WikiPage $page
	 *
	 * @return WikiPage
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 * @throws CpdXmlProcessingException
	 * @throws MWContentSerializationException
	 * @throws MWUnknownContentModelException
	 * @throws CpdSaveException
	 * @throws Exception
	 */
	private function migrateLegacyDiagram( WikiPage $page ): WikiPage {
		$content = $page->getContent();
		if ( !$content ) {
			throw new CpdInvalidArgumentException( 'No content found' );
		}

		$text = $content->getText();
		$process = $this->extractProcessNameFromContent( $text );

		$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );
		if ( $diagramPage->exists() ) {
			throw new CpdInvalidArgumentException( "Page $diagramPage already exists" );
		}

		$xml = $this->extractXmlFromContent( $text );
		$legacyDescriptionPages = $this->extractDescriptionPagesFromContent( $text );

		$diagramPage = $this->diagramPageUtil->createOrUpdateDiagramPage( $process, $this->anonymousUser, $xml );

		$warnings = $this->migrateLegacyDescriptionPages( $legacyDescriptionPages, $process, $xml );
		foreach ( $warnings as $warning ) {
			$this->outputLine( "[Warning] $warning" );
		}

		return $diagramPage;
	}

	/**
	 * @param array $dbKeys
	 * @param string $process
	 * @param string $xml
	 *
	 * @return array
	 * @throws CpdCreateElementException
	 * @throws CpdXmlProcessingException
	 * @throws MWContentSerializationException
	 * @throws MWUnknownContentModelException
	 */
	private function migrateLegacyDescriptionPages(
		array $dbKeys,
		string $process,
		string $xml
	): array {
		$cpdElements = $this->xmlProcessor->createElements( $process, $xml );
		$elementsForCreatePages = $this->findElementsForCreatePages( $dbKeys, $cpdElements );

		$createWarnings = $this->saveDescriptionPagesUtil->saveDescriptionPages(
			$this->anonymousUser,
			$elementsForCreatePages
		);
		$migrateWarnings = $this->migrateLegacyDescriptionPagesContent( $elementsForCreatePages, $dbKeys );

		return array_merge( $createWarnings, $migrateWarnings );
	}

	/**
	 * @param array $legacyDescriptionPages From smw property has_element
	 * @param CpdElement[] $cpdElements
	 *
	 * @return array
	 */
	private function findElementsForCreatePages( array $legacyDescriptionPages, array $cpdElements ): array {
		$elementsForCreate = [];

		foreach ( $legacyDescriptionPages as $dbKey ) {
			$splitted = explode( "/", $dbKey );
			$bpmnElementId = array_pop( $splitted );

			$cpdElement = null;
			foreach ( $cpdElements as $element ) {
				if ( $element->getId() === $bpmnElementId ) {
					$cpdElement = $element;
					break;
				}
			}
			if ( !$cpdElement ) {
				// Description page has been declared but corresponding element not found in diagram
				continue;
			}

			$elementsForCreate[] = $cpdElement;
		}

		return $elementsForCreate;
	}

	/**
	 * @param CpdElement[] $elementsForCreatePages
	 * @param string[] $legacyPageDbKeys
	 *
	 * @return array
	 * @throws MWContentSerializationException
	 * @throws MWUnknownContentModelException
	 */
	private function migrateLegacyDescriptionPagesContent(
		array $elementsForCreatePages,
		array $legacyPageDbKeys
	): array {
		$warnings = [];
		foreach ( $elementsForCreatePages as $element ) {
			$descriptionPageTitle = $element->getDescriptionPage();
			if ( !$descriptionPageTitle ) {
				continue;
			}
			$descriptionPage = $this->wikiPageFactory->newFromTitle( $descriptionPageTitle );
			if ( !$descriptionPage->exists() ) {
				$warnings[] = 'Description page has not been created: ' . $descriptionPageTitle;
				continue;
			}

			$legacyDbKey = null;
			foreach ( $legacyPageDbKeys as $dbKey ) {
				$splitted = explode( "/", $dbKey );
				$bpmnElementId = array_pop( $splitted );

				if ( $element->getId() === $bpmnElementId ) {
					$legacyDbKey = $dbKey;
					break;
				}
			}
			if ( !$legacyDbKey ) {
				continue;
			}

			$legacyDescriptionPage = $this->wikiPageFactory->newFromTitle( Title::newFromDBkey( $legacyDbKey ) );

			$newContent = $descriptionPage->getContent();
			$legacyContent = $legacyDescriptionPage->getContent();

			if ( !$newContent || !$legacyContent ) {
				continue;
			}

			$newDescription = $newContent->getText() .
				$this->cleanUpLegacyDescriptionPageText( $legacyContent->getText() );

			$successful = $this->saveRevision(
				$descriptionPage,
				$newDescription,
				"Migration: Set redirect to new diagram page"
			);
			if ( !$successful ) {
				$warnings[] = 'Failed to update new description page: ' . $descriptionPageTitle;
			}
		}

		return $warnings;
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	private function cleanUpLegacyDescriptionPageText( string $text ): string {
		// Remove [[Category:BPMN ...]] and {{BPMN_Element ...}}
		$text = preg_replace( '/\[\[Category:BPMN [^\]]+\]\]|\{\{BPMN_Element[^}]+\}\}/', '', $text );

		// Remove {{#set: ...}}
		$text = preg_replace( '/\{\{#set:[^}]+\}\}/', '', $text );

		// Remove <div class="cdp-data">...</div> including content inside
		$text = preg_replace( '/<div class="cdp-data">.*?<\/div>/s', '', $text );

		return $text;
	}

	/**
	 * @return WikiPage[]
	 */
	private function findAllLegacyBpmnPages(): array {
		$smwStore = StoreFactory::getStore();
		$property = new DIProperty( self::LEGACY_BPMN_ID_PROPERTY );
		$pages = [];

		foreach ( $smwStore->getAllPropertySubjects( $property ) as $diWikipage ) {
			$title = $diWikipage->getTitle();

			if ( $title->isRedirect() ) {
				continue;
			}

			// If the page is a subpage, its an element page, not a diagram page
			if ( $title->isSubpage() ) {
				continue;
			}

			$pages[] = $this->wikiPageFactory->newFromTitle( $title );
		}

		return $pages;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 * @throws Exception
	 */
	private function extractXmlFromContent( string $content ): string {
		preg_match( '/<[\w]*:?definitions[\s\S]*<\/[\w]*:?definitions>/', $content, $matches );

		if ( empty( $matches ) ) {
			throw new Exception( 'No valid bpmn data found' );
		}

		$xml = $matches[0];
		$xml = str_replace( "/n", "", $xml );

		$xml = $this->fixMissingElementLabels( $xml );

		return trim( $xml );
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 * @throws Exception
	 */
	private function extractProcessNameFromContent( string $content ): string {
		preg_match( '/\|id=([^|}]*)/', $content, $idMatch );
		if ( empty( $idMatch[1] ) ) {
			throw new Exception( 'No process name found' );
		}

		$process = $idMatch[1];
		$process = str_replace( '/', '_', $process );
		$process = str_replace( '|', '_', $process );
		$process = str_replace( '<', '_', $process );
		$process = str_replace( '>', '_', $process );

		return trim( $process );
	}

	/**
	 * @param string $content
	 *
	 * @return array $dbKeys
	 */
	private function extractDescriptionPagesFromContent( string $content ): array {
		preg_match( '/\|has_element=([^|}]*)/', $content, $matches );

		if ( empty( $matches[1] ) ) {
			return [];
		}

		$pages = explode( ',', $matches[1] );
		$pages = array_map( 'trim', $pages );

		return $pages;
	}

	/**
	 * If no name attribute is set, the name is set to the id
	 *
	 * @param string $xmlString
	 *
	 * @return string
	 * @throws CpdXmlProcessingException
	 */
	private function fixMissingElementLabels( string $xmlString ): string {
		try {
			$xml = new SimpleXMLElement( $xmlString );
		} catch ( Exception $e ) {
			throw new CpdXmlProcessingException( Message::newFromKey( "cpd-error-xml-parse-error" )->text() );
		}

		$xml->registerXPathNamespace( 'bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL' );
		$xmlElements = $xml->xpath( '//bpmn:*' );

		foreach ( $xmlElements as $element ) {
			$attributes = $element->attributes();

			$type = 'bpmn:' . ucfirst( $element->getName() );
			if ( !in_array( $type, $this->dedicatedSubpageTypes ) ) {
				continue;
			}

			if ( isset( $attributes['id'] ) && !isset( $attributes['name'] ) ) {
				$element->addAttribute( 'name', (string)$attributes['id'] );
			}
		}

		return $xml->asXML();
	}

	/**
	 * @param WikiPage $page
	 * @param WikiPage $newDiagramPage
	 *
	 * @return void
	 * @throws Exception
	 */
	private function makeRedirect( WikiPage $page, WikiPage $newDiagramPage ): void {
		$content = $page->getContent();

		if ( !$content ) {
			throw new Exception( 'No content found' );
		}

		$targetDbKey = $newDiagramPage->getTitle()->getPrefixedDBkey();
		$redirectText = "#REDIRECT [[$targetDbKey]]" . $content->getText();

		$success = $this->saveRevision(
			$page,
			$redirectText,
			"Migration: Set redirect to new diagram page"
		);

		if ( !$success ) {
			throw new Exception( 'Failed to make redirect' );
		}
	}

	/**
	 * @param WikiPage $page
	 * @param string $text
	 * @param string $comment
	 *
	 * @return bool
	 * @throws MWContentSerializationException
	 * @throws MWUnknownContentModelException
	 */
	private function saveRevision(
		WikiPage $page,
		string $text,
		string $comment
	): bool {
		$updater = $page->newPageUpdater( $this->anonymousUser );
		$updater->setContent(
			SlotRecord::MAIN,
			ContentHandler::makeContent(
				$text,
				$page->getTitle()
			)
		);
		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment( $comment ),
			EDIT_MINOR
		);

		return $updater->wasSuccessful();
	}

	private function outputLine( string $message ): void {
		$this->output( $message . "\n" );
	}
}

$maintClass = MigrateDiagrams::class;
require_once RUN_MAINTENANCE_IF_MAIN;
