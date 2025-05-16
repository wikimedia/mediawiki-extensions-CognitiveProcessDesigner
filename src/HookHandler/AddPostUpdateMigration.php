<?php

namespace CognitiveProcessDesigner\HookHandler;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;

class AddPostUpdateMigration extends LoadExtensionSchemaUpdates {
	protected function doProcess() {
		$this->updater->addPostDatabaseUpdateMaintenance( \MigrateDiagrams::class );

		return true;
	}
}
