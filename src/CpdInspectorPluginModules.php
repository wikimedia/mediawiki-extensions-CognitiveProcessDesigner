<?php

namespace CognitiveProcessDesigner;

use MWStake\MediaWiki\Component\ManifestRegistry\ManifestAttributeBasedRegistry;

class CpdInspectorPluginModules {

	/**
	 * @return array
	 */
	public static function getPluginModules() {
		$registry = new ManifestAttributeBasedRegistry(
			'CognitiveProcessDesignerInspectorPluginModules'
		);

		$pluginModules = [];
		foreach ( $registry->getAllKeys() as $key ) {
			$moduleName = $registry->getValue( $key );
			$pluginModules[] = $moduleName;
		}

		return $pluginModules;
	}
}
