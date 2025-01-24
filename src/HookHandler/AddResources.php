<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Config\Config;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use OutputPage;

class AddResources implements OutputPageBeforeHTMLHook {
	/** @var Config */
	private Config $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @param OutputPage $out
	 * @param string &$text
	 *
	 * @return void
	 */
	public function onOutputPageBeforeHTML( $out, &$text ): void {
		$out->addJsConfigVars( 'cpdCanvasDefaultHeight', $this->config->get( 'CPDCanvasEmbeddedHeight' ) );
	}
}
