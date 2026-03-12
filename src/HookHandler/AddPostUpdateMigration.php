<?php

namespace CognitiveProcessDesigner\HookHandler;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddPostUpdateMigration extends LoadExtensionSchemaUpdates {

	/**
	 * @inheritDoc
	 */
	protected function doProcess() {
		if ( !defined( 'SMW_VERSION' ) ) {
			return true;
		}
		$this->updater->addPostDatabaseUpdateMaintenance( \MigrateDiagrams::class );

		return true;
	}
}
