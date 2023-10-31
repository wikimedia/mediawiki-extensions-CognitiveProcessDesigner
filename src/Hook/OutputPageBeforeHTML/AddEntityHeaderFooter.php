<?php
namespace CognitiveProcessDesigner\Hook\OutputPageBeforeHTML;

use CognitiveProcessDesigner\Utility\BPMNHeaderFooterRenderer;
use Content;
use MediaWiki\MediaWikiServices;
use OutputPage;
use Title;

/**
 * This hook adds header & footer to BPMN Element pages
 * Header consists of SourceEntities (entry BPMN elements)
 * Footer consists of TargetEntities (exit points BPMN elements)
 * Class AddHeaderFooter
 * @package CognitiveProcessDesigner\Hook\OutputPageBeforeHTML
 */
class AddEntityHeaderFooter {

	/**
	 * @var OutputPage
	 */
	private $out = null;

	/**
	 * @var string
	 */
	private $text = '';

	/**
	 *
	 * @param \OutputPage &$out
	 * @param string &$text
	 * @return bool
	 */
	public static function callback( &$out, &$text ) {
		$className = static::class;
		$hookHandler = new $className(
			$out,
			$text
		);
		return $hookHandler->doProcess();
	}

	/**
	 *
	 * @param OutputPage &$out
	 * @param string &$text
	 */
	public function __construct( &$out, &$text ) {
		$this->out =& $out;
		$this->text =& $text;
	}

	/**
	 * @return bool
	 */
	public function doProcess() {
		if ( $this->skipProcessing() ) {
			return true;
		}
		/** @var BPMNHeaderFooterRenderer $renderer */
		$renderer = MediaWikiServices::getInstance()->getService( 'BPMNHeaderFooterRenderer' );
		$header = $renderer->getHeader( $this->out->getTitle() );
		$footer = $renderer->getFooter( $this->out->getTitle() );
		if ( empty( $header ) && empty( $footer ) ) {
			return true;
		}

		$this->out->addModuleStyles( 'ext.cpd.entity' );
		$this->text = $header . $this->text . $footer;
		return true;
	}

	/**
	 * @return bool
	 */
	private function skipProcessing() {
		$request = $this->out->getRequest();
		$title = $this->out->getTitle();

		if ( $request->getVal( 'action', 'view' ) !== 'view' ) {
			return true;
		}
		if ( !$title instanceof Title ) {
			return true;
		}
		if ( $title->isSpecialPage() ) {
			return true;
		}
		$wikiPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		if ( !$wikiPage->getContent() instanceof Content ) {
			return true;
		}
		return false;
	}
}
