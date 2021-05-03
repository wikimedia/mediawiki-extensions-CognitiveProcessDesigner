<?php

use CognitiveProcessDesigner\Utility\BPMNHeaderFooterRenderer;
use MediaWiki\MediaWikiServices;

return [
	'BPMNHeaderFooterRenderer' => static function ( MediaWikiServices $services ) {
		$cpdEntityElementTypes = null;
		if ( $services->getMainConfig()->has( 'CPDEntityElementTypes' ) ) {
			$cpdEntityElementTypes = $services->getMainConfig()->get( 'CPDEntityElementTypes' );
		}
		$happyPathSMWPropertyName = null;
		if ( $services->getMainConfig()->has( 'CPDHappyPathSMWPropertyName' ) ) {
			$happyPathSMWPropertyName = $services->getMainConfig()->get( 'CPDHappyPathSMWPropertyName' );
		}

		return new BPMNHeaderFooterRenderer(
			$cpdEntityElementTypes,
			$happyPathSMWPropertyName
		);
	},
];
