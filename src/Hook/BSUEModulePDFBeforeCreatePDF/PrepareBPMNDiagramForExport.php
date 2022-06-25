<?php

namespace CognitiveProcessDesigner\Hook\BSUEModulePDFBeforeCreatePDF;

use BlueSpice\UEModulePDF\Hook\BSUEModulePDFBeforeCreatePDF;
use BsPDFPageProvider;
use DOMElement;
use DOMXPath;
use MediaWiki\MediaWikiServices;
use SMW\DIWikiPage;
use SMW\SemanticData;
use Title;
use WikiPage;

class PrepareBPMNDiagramForExport extends BSUEModulePDFBeforeCreatePDF {

	protected $classesToRemove = [
		'cpd-toolbar',
		'cpd-js-drop-zone'
	];

	protected $bpmnEditButtonClass = 'cpd-edit-bpmn';
	protected $bpmnEditButtonBPMNNameAttribute = 'data-bpmn-name';

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$this->addBPMNEntitySubpages();
		$this->removeCPDToolbar();
		return true;
	}

	private function addBPMNEntitySubpages() {
		$finder = new DOMXPath( $this->DOM );
		$editButtons = $finder->query( "//*[contains(@class, '" . $this->bpmnEditButtonClass . "')]" );
		if ( $editButtons->length < 1 ) {
			return;
		}
		$bodyEl = $this->DOM->getElementsByTagName( 'body' )->item( 0 );

		if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
			// MW 1.36+
			$wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
		} else {
			$wikiPageFactory = null;
		}
		/** @var DOMElement $editButton */
		foreach ( $editButtons as $editButton ) {
			if ( !$editButton->hasAttribute( $this->bpmnEditButtonBPMNNameAttribute ) ) {
				continue;
			}
			$bpmnName = $editButton->getAttribute( $this->bpmnEditButtonBPMNNameAttribute );
			$bpmnTitle = Title::newFromText( $bpmnName );
			if ( $wikiPageFactory !== null ) {
				// MW 1.36+
				$bpmnWikiPage = $wikiPageFactory->newFromID( $bpmnTitle->getArticleID() );
			} else {
				$bpmnWikiPage = WikiPage::newFromID( $bpmnTitle->getArticleID() );
			}
			/** @var SemanticData $smwData */
			$bpmnSMWData = $bpmnWikiPage->getContent()
				->getParserOutput( $bpmnTitle )
				->getExtensionData( 'smwdata' );
			if ( !array_key_exists( 'Has_element', $bpmnSMWData->getProperties() ) ) {
				continue;
			}
			$entities = $bpmnSMWData->getPropertyValues( $bpmnSMWData->getProperties()['Has_element'] );
			if ( !is_array( $entities ) || count( $entities ) < 1 ) {
				continue;
			}
			$cpdEntityElementTypes = [];
			if ( $this->getConfig()->has( 'CPDEntityElementTypes' ) ) {
				$cpdEntityElementTypes = $this->getConfig()->get( 'CPDEntityElementTypes' );
			}
			if ( count( $cpdEntityElementTypes ) < 1 ) {
				continue;
			}

			/** @var DIWikiPage $entity */
			foreach ( $entities as $entity ) {
				if ( !$entity instanceof DIWikiPage ) {
					continue;
				}
				if ( !$this->isEntityElement( $entity->getDBkey(), $cpdEntityElementTypes ) ) {
					continue;
				}

				$editButton->parentNode->nextSibling->nextSibling->removeAttribute( 'class' );
				$content = BsPDFPageProvider::getPage( [
					'title' => $entity->getTitle()->getPrefixedDBkey()
				] );
				$currentPagesBodyEl = $content['dom']->getElementsByTagName( 'body' )->item( 0 );

				$importedPage = $this->DOM->importNode(
					$currentPagesBodyEl->firstChild,
					true
				);
				$bodyEl->appendChild( $importedPage );
			}
		}
	}

	/**
	 * @param string $titleText
	 * @param array $cpdEntityElementTypes
	 * @return bool
	 */
	private function isEntityElement( $titleText, $cpdEntityElementTypes ) {
		foreach ( $cpdEntityElementTypes as $elementType ) {
			if ( strpos( $titleText, $elementType ) !== false ) {
				return true;
			}
		}
		return false;
	}

	private function removeCPDToolbar() {
		$finder = new DOMXPath( $this->DOM );
		foreach ( $this->classesToRemove as $class ) {
			$elements = $finder->query( "//*[contains(@class, '" . $class . "')]" );
			if ( $elements->length > 0 ) {
				foreach ( $elements as $element ) {
					$element->parentNode->removeChild( $element );
				}
			}
		}
	}

}
