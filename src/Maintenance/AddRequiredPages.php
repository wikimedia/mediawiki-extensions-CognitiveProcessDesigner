<?php

namespace CognitiveProcessDesigner\Maintenance;

use CommentStoreComment;
use Exception;
use LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use Title;
use User;
use WikiPage;

class AddRequiredPages extends LoggedUpdateMaintenance {

	/**
	 *
	 * @var array
	 */
	private $pages = [
		'Property:Bpmn_height' => '[[Has type::Number]]',
		'Property:Bpmn_width' => '[[Has type::Number]]',
		'Property:Bpmn_xBound' => '[[Has type::Number]]',
		'Property:Bpmn_yBound' => '[[Has type::Number]]',
		'Property:Bpmn_Id' => '[[Has type::Text]]',
		'Property:Bpmn_Label' => '[[Has type::Text]]',
		'Property:Bpmn_Incoming' => '[[Has type::Page]]',
		'Property:Bpmn_Outgoing' => '[[Has type::Page]]',
		'Property:Bpmn_SourceEntities' => '[[Has type::Page]]',
		'Property:Bpmn_TargetEntities' => '[[Has type::Page]]',
		'Property:Bpmn_hasElement' => '[[Has type::Page]]',
		'Property:Bpmn_SourceRef' => '[[Has type::Page]]',
		'Property:Bpmn_TargetRef' => '[[Has type::Page]]',
		'Property:Bpmn_isHappyPath' => '[[Has type::Boolean]]',
		'Category:BPMN_Task' => '',
		'Template:BPMN_Process' => <<<HERE
[[Category:BPMN]]
{{#set:Process
 |id={{{id|}}}
 |label={{{label|}}}
 |has_element={{{has_element|}}}|+sep=,
}}
HERE,
		'Template:BPMN_Element' => <<<HERE
{{#if:{{{label|}}}|{{DISPLAYTITLE:{{{label|}}}}}}}
{{#set:Element
 |id={{{id|}}}
 |label={{{label|}}}
 |bpmn_xBound={{{bpmn_xBound|}}}
 |bpmn_yBound={{{bpmn_yBound|}}}
 |bpmn_width={{{bpmn_width|}}}
 |bpmn_height={{{bpmn_height|}}}
 |incoming={{{incoming|}}}|+sep=,
 |outgoing={{{outgoing|}}}|+sep=,
 |sourceEntities={{{sourceEntities|}}}|+sep=,
 |targetEntities={{{targetEntities|}}}|+sep=,
 |sourceRef={{{sourceRef|}}}|+sep=,
 |targetRef={{{targetRef|}}}|+sep=,
 |parent={{{parent|}}}
 |children={{{children|}}}|+sep=,
 |parentLanes={{{parentLanes|}}}|+sep=,
 |loopCharacteristics={{{loopCharacteristics|}}}
}}
[[Category:BPMN {{{loopCharacteristics|}}}]]
HERE
	];

	/**
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
			// MW 1.36+
			$wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
		} else {
			$wikiPageFactory = null;
		}
		$user = User::newSystemUser( 'MediaWiki default' );
		$summary = 'Createy by Cognitive Process Designer';
		foreach ( $this->pages as $pagename => $wikitextContent ) {
			$title = Title::newFromText( $pagename );
			if ( $wikiPageFactory !== null ) {
				// MW 1.36+
				$wikiPage = $wikiPageFactory->newFromTitle( $title );
			} else {
				$wikiPage = WikiPage::factory( $title );
			}
			if ( !$wikiPage->exists() ) {
				$this->output( "Creating page '{$title->getPrefixedDBkey()}'... " );
				$updater = $wikiPage->newPageUpdater( $user );
				$content = $wikiPage->getContentHandler()->makeContent( $wikitextContent, $title );
				$updater->setContent( SlotRecord::MAIN, $content );
				$comment = CommentStoreComment::newUnsavedComment( $summary );
				try {
					$updater->saveRevision( $comment );
				} catch ( Exception $e ) {
					$this->error( "EXCEPTION:\n" . $e->getMessage() );
					return false;
				}
				$statusText = $updater->wasSuccessful() ? 'DONE' : 'FAILED';
				$this->output( "$statusText\n" );
			}
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'cognitive-process-designer-add-required-pages';
	}
}
