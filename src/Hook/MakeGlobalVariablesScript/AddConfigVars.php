<?php

namespace CognitiveProcessDesigner\Hook\MakeGlobalVariablesScript;

use MediaWiki\MediaWikiServices;

class AddConfigVars {

	/**
	 * @param array &$vars
	 * @return bool
	 */
	public static function callback( &$vars ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$vars['wgCPDEntityElementTypes'] = [];
		if ( $config->has( 'CPDEntityElementTypes' ) ) {
			$vars['wgCPDEntityElementTypes'] = $config->get( 'CPDEntityElementTypes' );
		}
		return true;
	}
}
