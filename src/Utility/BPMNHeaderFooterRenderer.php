<?php
namespace CognitiveProcessDesigner\Utility;

use Html;
use MediaWiki\MediaWikiServices;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMWDIBoolean;
use Title;
use WikiPage;

class BPMNHeaderFooterRenderer {

	/**
	 * List of elements that represents BPMN entity (for example, Activity)
	 * @var array
	 */
	private $cpdEntityElementTypes = [];

	/**
	 * SMW property which indicates is current BPMN element marked as HappyPath
	 * @var null|string
	 */
	private $happyPathSMWPropertyName = null;

	/**
	 * BPMNHeaderFooter constructor.
	 * @param array $cpdEntityElementTypes
	 * @param null|string $happyPathSMWPropertyName
	 */
	public function __construct(
		$cpdEntityElementTypes = [],
		$happyPathSMWPropertyName = null
	) {
		$this->cpdEntityElementTypes = $cpdEntityElementTypes;
		$this->happyPathSMWPropertyName = $happyPathSMWPropertyName;
	}

	/**
	 * @param Title $title
	 * @return string
	 */
	public function getHeader( Title $title ) {
		$wikiPage = WikiPage::newFromID( $title->getArticleID() );
		/** @var SemanticData $smwData */
		$smwData = $wikiPage->getContent()->getParserOutput( $title )->getExtensionData( 'smwdata' );
		if ( !$smwData instanceof SemanticData ) {
			return '';
		}
		$headerItems = [];
		$isHappyPath = false;
		if ( $this->happyPathSMWPropertyName !== null ) {
			$isHappyPath = $this->isHappyPath(
				$smwData,
				ucfirst( $this->happyPathSMWPropertyName )
			);
		}
		$headerItems = array_merge(
			$headerItems,
			$this->getLinksFromSMWData( $smwData, 'SourceEntities' )
		);
		if ( count( $headerItems ) > 0 ) {
			return $this->generateHeaderHTML( $headerItems, $isHappyPath );
		}
		return '';
	}

	/**
	 * @param string[] $links
	 * @param bool $isHappyPath
	 * @return string
	 */
	private function generateHeaderHTML( array $links, $isHappyPath ) {
		$separator = Html::element( 'span', [ 'class' => 'cpd-separator' ] );
		$classes = [ 'cpd-entity-header' ];
		if ( $isHappyPath ) {
			$classes[] = 'happy-path';
		}

		$html = Html::openElement( 'div', [
			'class' => implode( ' ', $classes )
		] );
		if ( count( $links ) > 0 ) {
			$html .= implode( $separator, $links );
			$html .= Html::element( 'div', [ 'class' => 'cpd-arrow' ] );
		}
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * @param Title $title
	 * @return string
	 */
	public function getFooter( Title $title ) {
		$wikiPage = WikiPage::newFromID( $title->getArticleID() );
		/** @var SemanticData $smwData */
		$smwData = $wikiPage->getContent()->getParserOutput( $title )->getExtensionData( 'smwdata' );
		if ( !$smwData instanceof SemanticData ) {
			return '';
		}

		$isHappyPath = false;
		if ( $this->happyPathSMWPropertyName !== null ) {
			$isHappyPath = $this->isHappyPath(
				$smwData,
				ucfirst( $this->happyPathSMWPropertyName )
			);
		}
		$footerLinks = $this->getLinksFromSMWData( $smwData,  'TargetEntities' );
		if ( count( $footerLinks ) > 0 ) {
			return $this->generateFooterHTML( $footerLinks, $isHappyPath );
		}
		return '';
	}

	/**
	 * @param string[] $links
	 * @param bool $isHappyPath
	 * @return string
	 */
	private function generateFooterHTML( array $links, $isHappyPath ) {
		$separator = Html::element( 'span', [ 'class' => 'cpd-separator' ] );
		$classes = [ 'cpd-entity-footer' ];
		if ( $isHappyPath ) {
			$classes[] = 'happy-path';
		}

		$html = Html::openElement( 'div', [
			'class' => implode( ' ', $classes )
		] );
		$html .= Html::element( 'div', [ 'class' => 'cpd-arrow' ], '' );
		$html .= implode( $separator, $links );
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * @param SemanticData $smwData
	 * @param string $happyPathPropertyName
	 * @return bool
	 */
	private function isHappyPath( $smwData, $happyPathPropertyName ) {
		if ( isset( $smwData->getProperties()[$happyPathPropertyName] ) ) {
			$entities = $smwData->getPropertyValues( $smwData->getProperties()[$happyPathPropertyName] );
			if ( !is_array( $entities ) || count( $entities ) < 1 ) {
				return false;
			}
			/** @var SMWDIBoolean $entity */
			foreach ( $entities as $entity ) {
				if ( $entity instanceof SMWDIBoolean ) {
					return $entity->getBoolean();
				}

			}
		}
		return false;
	}

	/**
	 * @param SemanticData $smwData
	 * @param string $propKey
	 * @return string[]
	 */
	private function getLinksFromSMWData( $smwData, $propKey ) {
		$links = [];
		if ( !isset( $smwData->getProperties()[$propKey] ) ) {
			return $links;
		}
		$entities = $smwData->getPropertyValues( $smwData->getProperties()[$propKey] );
		if ( !is_array( $entities ) || count( $entities ) < 1 ) {
			return $links;
		}

		$linkeRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		/** @var DIWikiPage $entity */
		foreach ( $entities as $entity ) {
			if ( $entity instanceof DIWikiPage ) {
				$title = $entity->getTitle();
				$wikiPage = WikiPage::factory( $title );
				$displayTitle = $title->getPrefixedText();
				$content = $wikiPage->getContent();
				if ( $content !== null ) {
					$displayTitle = $content->getParserOutput( $title )->getDisplayTitle();
				}

				$classes = [ 'cpd-entity-link' ];
				/** @var SemanticData $smwData */
				$linkSMWData = $wikiPage->getContent()->getParserOutput( $title )
					->getExtensionData( 'smwdata' );
				if ( $this->isHappyPath( $linkSMWData, $this->happyPathSMWPropertyName ) ) {
					$classes[] = 'happy-path';
				}
				$linkAttribs = [ 'class' => implode( ' ', $classes ) ];
				$links[] = $linkeRenderer->makeLink(
					$entity->getTitle(), $displayTitle, $linkAttribs
				);
			}
		}
		return $links;
	}
}
