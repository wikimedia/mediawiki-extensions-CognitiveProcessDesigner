<?php

namespace CognitiveProcessDesigner\HookHandler;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddPostUpdateMigration extends LoadExtensionSchemaUpdates {

	/**
	 * @inheritDoc
	 */
	protected function doProcess() {
		if ( !defined( 'SMW_VERSION' ) ) {
			return false;
		}
		$this->updater->addPostDatabaseUpdateMaintenance( \MigrateDiagrams::class );

		return true;
	}
}
