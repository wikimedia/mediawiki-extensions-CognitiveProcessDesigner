<?php

namespace CognitiveProcessDesigner\HookHandler;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddPostUpdateMigration extends LoadExtensionSchemaUpdates {

	/**
	 * @inheritDoc
	 */
	protected function doProcess() {
		$this->updater->addPostDatabaseUpdateMaintenance( \MigrateDiagrams::class );

		return true;
	}
}
