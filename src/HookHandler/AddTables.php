<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class AddTables implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable(
			'cpd_orphaned_description_pages', __DIR__ . '/../../db/cpd_orphaned_description_pages.sql'
		);
		$updater->addExtensionTable(
			'cpd_element_connections', __DIR__ . '/../../db/cpd_element_connections.sql'
		);
	}
}
