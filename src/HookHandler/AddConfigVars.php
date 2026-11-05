<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Config\Config;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;

class AddConfigVars implements ResourceLoaderGetConfigVarsHook {

	/**
	 * @inheritDoc
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$vars['cpdProcessNamespace'] = NS_PROCESS;
	}
}
