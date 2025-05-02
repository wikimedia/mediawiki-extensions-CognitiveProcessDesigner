<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Panel\MainLinkPanel;
use MediaWiki\Config\Config;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @param Config $config
	 */
	public function __construct( private readonly Config $config ) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		if ( $this->config->get( 'CPDMainLinksCognitiveProcessDesigner' ) ) {
			$registry->register(
				'MainLinksPanel',
				[
					'bs-special-cpd' => [
						'factory' => static function () {
							return new MainLinkPanel( 'n-cpd' );
						},
						'position' => 40
					]
				]
			);
		}

		$registry->register(
			'GlobalActionsOverview',
			[
				'bs-special-cpd' => [
					'factory' => static function () {
						return new MainLinkPanel( 'ga-cpd-overview' );
					}
				]
			]
		);
	}
}
