<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ContentStabilization\Hook\Interfaces\ContentStabilizationIsStabilizationEnabledHook;
use MediaWiki\Page\PageIdentity;

class EnableStabilizationForProcess implements ContentStabilizationIsStabilizationEnabledHook {

	/**
	 * @param Config $config
	 */
	public function __construct( private readonly Config $config ) {
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
