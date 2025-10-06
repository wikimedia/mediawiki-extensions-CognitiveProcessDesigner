<?php

namespace CognitiveProcessDesigner\HookHandler;

use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;
use BlueSpice\Discovery\ITemplateDataProvider;

class DiscoverySkin implements BlueSpiceDiscoveryTemplateDataProviderAfterInit {

	/**
	 * @param ITemplateDataProvider $registry
	 * @return void
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->register( 'panel/edit', 'ca-editxml' );
		$registry->register( 'panel/create', 'ca-cpd-create-process' );
		$registry->register( 'actions_primary', 'ca-cpd-create-new-process' );
		$registry->unregister( 'actioncollection/actions', 'ca-cpd-create-process' );
	}
}
