<?php

namespace CognitiveProcessDesigner\Hook\LoadExtensionSchemaUpdates;

use CognitiveProcessDesigner\Maintenance\AddRequiredPages as MaintenanceAddRequiredPages;
use DatabaseUpdater;

class AddRequiredPages {

	/**
	 *
	 * @param DatabaseUpdater $updater
	 * @return bool
	 */
	public static function callback( DatabaseUpdater $updater ) {
		$updater->addPostDatabaseUpdateMaintenance( MaintenanceAddRequiredPages::class );

		return true;
	}
}
