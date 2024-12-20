<?php

use CognitiveProcessDesigner\CpdElementFactory;
use CognitiveProcessDesigner\Process\SvgFile;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use MediaWiki\MediaWikiServices;

return [
	'CpdDiagramPageUtil' => static function ( MediaWikiServices $services ) {
		return new CpdDiagramPageUtil(
			$services->getTitleFactory(),
			$services->getWikiPageFactory(),
			$services->getRepoGroup(),
			$services->getMainConfig(),
			$services->getDBLoadBalancer(),
			$services->getLinkRenderer()
		);
	},
	'CpdDescriptionPageUtil' => static function ( MediaWikiServices $services ) {
		return new CpdDescriptionPageUtil(
			$services->getPageStore(),
			$services->getDBLoadBalancer(),
			$services->getWikiPageFactory(),
			$services->getMainConfig(),
			$services->getService( 'CpdElementConnectionUtil' )
		);
	},
	'CpdElementConnectionUtil' => static function ( MediaWikiServices $services ) {
		return new CpdElementConnectionUtil(
			$services->getDBLoadBalancer()
		);
	},
	'CpdElementFactory' => static function ( MediaWikiServices $services ) {
		return new CpdElementFactory();
	},
	'SvgFile' => static function ( MediaWikiServices $services ) {
		return new SvgFile(
			$services->getMimeAnalyzer(), $services->getRepoGroup()
		);
	},
];
