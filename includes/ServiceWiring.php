<?php

use CognitiveProcessDesigner\CpdElementFactory;
use CognitiveProcessDesigner\Process\SvgFile;
use CognitiveProcessDesigner\RevisionLookup\CpdRevisionLookup;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use CognitiveProcessDesigner\Util\CpdSaveDescriptionPagesUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use MediaWiki\MediaWikiServices;

return [
	'CpdDiagramPageUtil' => static function ( MediaWikiServices $services ) {
		return new CpdDiagramPageUtil(
			$services->getTitleFactory(),
			$services->getWikiPageFactory(),
			$services->getRepoGroup(),
			$services->getMainConfig(),
			$services->getDBLoadBalancer(),
			$services->getLinkRenderer(),
			$services->getService( 'CpdRevisionLookup' )
		);
	},
	'CpdDescriptionPageUtil' => static function ( MediaWikiServices $services ) {
		return new CpdDescriptionPageUtil(
			$services->getPageStore(),
			$services->getDBLoadBalancer(),
			$services->getWikiPageFactory(),
			$services->getMainConfig(),
			$services->getService( 'CpdRevisionLookup' )
		);
	},
	'CpdSaveDescriptionPagesUtil' => static function ( MediaWikiServices $services ) {
		return new CpdSaveDescriptionPagesUtil(
			$services->get( 'CpdDiagramPageUtil' ),
			$services->get( 'CpdDescriptionPageUtil' ),
			$services->getLinkRenderer(),
			$services->getWikiPageFactory(),
			$services->getMovePageFactory()
		);
	},
	'CpdElementConnectionUtil' => static function ( MediaWikiServices $services ) {
		return new CpdElementConnectionUtil(
			$services->get( 'CpdDiagramPageUtil' ),
			$services->get( 'CpdXmlProcessor' )
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
	'CpdXmlProcessor' => static function ( MediaWikiServices $services ) {
		return new CpdXmlProcessor(
			$services->getMainConfig(),
			$services->getService( 'CpdElementFactory' )
		);
	},
	'CpdRevisionLookup' => static function ( MediaWikiServices $services ) {
		return new CpdRevisionLookup( $services );
	}
];
