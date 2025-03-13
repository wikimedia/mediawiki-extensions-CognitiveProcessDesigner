<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRoleRegistry;

class RegisterProcessMetadataSlot implements MediaWikiServicesHook {

	/**
	 * @param MediaWikiServices $services
	 * @return void
	 */
	public function onMediaWikiServices( $services ) {
		$services->addServiceManipulator(
			'SlotRoleRegistry',
			static function ( SlotRoleRegistry $registry ) {
				if ( $registry->isDefinedRole( CONTENT_SLOT_CPD_PROCESS_META ) ) {
					return;
				}
				$registry->defineRoleWithModel(
					CONTENT_SLOT_CPD_PROCESS_META,
					CONTENT_MODEL_JSON,
					[
						'display' => 'none'
					]
				);
			}
		);
	}
}
