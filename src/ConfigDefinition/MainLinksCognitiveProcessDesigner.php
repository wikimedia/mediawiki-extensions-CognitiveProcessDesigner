<?php

namespace CognitiveProcessDesigner\ConfigDefinition;

use BlueSpice\Bookshelf\ISettingPaths;
use BlueSpice\ConfigDefinition\BooleanSetting;
use BlueSpice\ConfigDefinition\IOverwriteGlobal;
use MediaWiki\Registration\ExtensionRegistry;

class MainLinksCognitiveProcessDesigner extends BooleanSetting implements ISettingPaths, IOverwriteGlobal {

	private const EXTENSION_NAME = 'CognitiveProcessDesigner';

	/**
	 * @return array
	 */
	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_SKINNING . '/' . self::EXTENSION_NAME,
			static::MAIN_PATH_EXTENSION . '/' . self::EXTENSION_NAME . '/' . static::FEATURE_SKINNING,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_FREE . '/' . self::EXTENSION_NAME,
		];
	}

	/**
	 * @return string
	 */
	public function getVariableName() {
		return 'wg' . $this->getName();
	}

	/**
	 * @return string
	 */
	public function getLabelMessageKey() {
		return 'bs-config-mainlinks-cpd-label';
	}

	/**
	 * @return string
	 */
	public function getHelpMessageKey() {
		return 'bs-config-mainlinks-cpd-help';
	}

	/**
	 * @return bool
	 */
	public function isHidden() {
		return !ExtensionRegistry::getInstance()->isLoaded( 'BlueSpiceDiscovery' );
	}

	/**
	 * @return string
	 */
	public function getGlobalName() {
		return 'wg' . $this->getName();
	}
}
