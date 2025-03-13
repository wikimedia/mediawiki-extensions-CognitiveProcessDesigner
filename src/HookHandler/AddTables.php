<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class AddTables implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$db = $updater->getDB();
		$dbType = $db->getType();
		$dir = dirname( __DIR__, 2 ) . '/sql';
		$updater->addExtensionTable( 'cpd_orphaned_description_pages', "$dir/$dbType/tables-generated.sql" );
	}
}
