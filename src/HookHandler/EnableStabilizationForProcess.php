<?php

namespace CognitiveProcessDesigner\HookHandler;

use Config;
use MediaWiki\Extension\ContentStabilization\Hook\Interfaces\ContentStabilizationIsStabilizationEnabledHook;
use MediaWiki\Page\PageIdentity;

class EnableStabilizationForProcess implements ContentStabilizationIsStabilizationEnabledHook {

	/** @var Config */
	private Config $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @inheritDoc
	 */
	public function onContentStabilizationIsStabilizationEnabled( PageIdentity $page, bool &$result ): void {
		$namespace = $page->getNamespace();

		if ( !in_array( $namespace, $this->config->get( 'EnabledNamespaces' ) ) ) {
			$result = false;

			return;
		}

		if ( $namespace === NS_PROCESS ) {
			$result = true;
		}
	}
}
